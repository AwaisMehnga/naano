<?php

namespace App\Services;

use App\Enums\CampaignStatus;
use App\Enums\CollaborationStatus;
use App\Models\Campaign;
use App\Models\Collaboration;
use App\Models\Company;
use App\Models\CompanyIcp;
use App\Models\Post;
use App\Models\User;
use App\Support\PublicDisk;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CompanyCampaignService
{
    public function __construct(private CompanyCollaborationService $collaborations) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{data: list<array<string, mixed>>, current_page: int, last_page: int, per_page: int, total: int, from: int|null, to: int|null}
     */
    public function index(Company $company, array $filters): array
    {
        $q = isset($filters['q']) ? trim((string) $filters['q']) : '';
        $perPage = min(50, max(1, (int) ($filters['per_page'] ?? 25)));

        $query = $company->campaigns()
            ->withCount('collaborations')
            ->orderByDesc('id');

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        } else {
            $query->whereNot('status', CampaignStatus::Cancelled);
        }

        if (isset($filters['type']) && $filters['type'] !== '') {
            $query->where('type', $filters['type']);
        }

        if ($q !== '') {
            $query->where('name', 'like', '%'.$q.'%');
        }

        $page = $query->paginate($perPage);

        return [
            'data' => $page->getCollection()
                ->map(fn (Campaign $campaign): array => $this->listPayload($campaign))
                ->values()
                ->all(),
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'per_page' => $page->perPage(),
            'total' => $page->total(),
            'from' => $page->firstItem(),
            'to' => $page->lastItem(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function store(Company $company, User $actor, array $data): array
    {
        $icpId = $data['company_icp_id'] ?? null;

        if ($icpId !== null) {
            $this->ownedIcp($company, (int) $icpId);
        }

        $campaign = $company->campaigns()->create([
            'name' => $data['name'],
            'type' => $data['type'],
            'objective' => $data['objective'],
            'status' => CampaignStatus::Draft,
            'budget_cents' => $data['budget_cents'] ?? null,
            'company_icp_id' => $icpId,
            'created_by_user_id' => $actor->id,
        ]);

        return $this->show($company, $campaign);
    }

    /**
     * @return array<string, mixed>
     */
    public function show(Company $company, Campaign $campaign): array
    {
        $this->ensureOwned($company, $campaign);

        $campaign->loadCount('collaborations');
        $campaign->load([
            'companyIcp',
            'collaborations' => fn ($query) => $query
                ->where('status', CollaborationStatus::Selected)
                ->orderByDesc('id'),
            'collaborations.creatorProfile.offers' => fn ($query) => $query
                ->where('is_active', true)
                ->orderBy('price_cents')
                ->orderBy('id'),
            'collaborations.creatorProfile.audienceProfiles' => fn ($query) => $query
                ->orderByDesc('captured_at')
                ->orderByDesc('id'),
            'posts.collaboration.creatorProfile',
        ]);

        $statusCounts = $campaign->collaborations()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            ...$this->detailPayload($campaign),
            'collab_counts' => $this->collaborations->countsFromStatuses($statusCounts->all()),
            'shortlisted' => $campaign->collaborations
                ->map(fn (Collaboration $collaboration): array => $this->shortlistedPayload($collaboration))
                ->values()
                ->all(),
            'leads_count' => $campaign->leads()->count(),
            'posts' => $campaign->posts
                ->sortByDesc('id')
                ->values()
                ->map(fn (Post $post): array => $this->postPayload($post))
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(Company $company, Campaign $campaign, array $data): array
    {
        $this->ensureOwned($company, $campaign);

        if (in_array($campaign->status, [CampaignStatus::Completed, CampaignStatus::Cancelled], true)) {
            throw ValidationException::withMessages([
                'status' => 'This campaign cannot be edited.',
            ]);
        }

        if ($campaign->status !== CampaignStatus::Draft) {
            foreach (['type', 'objective'] as $field) {
                if (array_key_exists($field, $data)) {
                    throw ValidationException::withMessages([
                        $field => 'This field cannot be changed after launch.',
                    ]);
                }
            }
        }

        if (array_key_exists('company_icp_id', $data) && $data['company_icp_id'] !== null) {
            $this->ownedIcp($company, (int) $data['company_icp_id']);
        }

        if (array_key_exists('brief', $data) && is_array($data['brief'])) {
            $data = [...$data, ...$this->flattenBrief($data['brief'])];
        }

        $campaign->fill($data);
        $campaign->save();

        return $this->show($company, $campaign->refresh());
    }

    /**
     * @return array<string, mixed>
     */
    public function launch(Company $company, Campaign $campaign): array
    {
        return $this->transition($company, $campaign, CampaignStatus::Active, [CampaignStatus::Draft]);
    }

    /**
     * @return array<string, mixed>
     */
    public function pause(Company $company, Campaign $campaign): array
    {
        return $this->transition($company, $campaign, CampaignStatus::Paused, [CampaignStatus::Active]);
    }

    /**
     * @return array<string, mixed>
     */
    public function resume(Company $company, Campaign $campaign): array
    {
        return $this->transition($company, $campaign, CampaignStatus::Active, [CampaignStatus::Paused]);
    }

    /**
     * @return array<string, mixed>
     */
    public function reopen(Company $company, Campaign $campaign): array
    {
        return $this->transition($company, $campaign, CampaignStatus::Active, [
            CampaignStatus::Completed,
            CampaignStatus::Cancelled,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function complete(Company $company, Campaign $campaign): array
    {
        return $this->transition($company, $campaign, CampaignStatus::Completed, [
            CampaignStatus::Active,
            CampaignStatus::Paused,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function cancel(Company $company, Campaign $campaign): array
    {
        return $this->transition($company, $campaign, CampaignStatus::Cancelled, [
            CampaignStatus::Draft,
            CampaignStatus::Active,
            CampaignStatus::Paused,
        ]);
    }

    /**
     * @param  list<CampaignStatus>  $from
     * @return array<string, mixed>
     */
    private function transition(Company $company, Campaign $campaign, CampaignStatus $to, array $from): array
    {
        $this->ensureOwned($company, $campaign);

        if (! in_array($campaign->status, $from, true)) {
            throw ValidationException::withMessages([
                'status' => 'This campaign cannot move to '.$to->value.' from '.$campaign->status->value.'.',
            ]);
        }

        $campaign->status = $to;
        $campaign->save();

        return $this->show($company, $campaign->refresh());
    }

    private function ensureOwned(Company $company, Campaign $campaign): void
    {
        if ($campaign->company_id !== $company->id) {
            abort(404);
        }
    }

    private function ownedIcp(Company $company, int $icpId): CompanyIcp
    {
        $icp = CompanyIcp::query()->find($icpId);

        if ($icp === null || $icp->company_id !== $company->id) {
            throw ValidationException::withMessages([
                'company_icp_id' => 'The selected ICP is invalid.',
            ]);
        }

        return $icp;
    }

    /**
     * @param  array<string, mixed>  $brief
     * @return array{goal: string|null, key_messages: list<string>, guidelines: string|null}
     */
    private function flattenBrief(array $brief): array
    {
        $context = isset($brief['context']) ? trim((string) $brief['context']) : '';
        $keyMessage = isset($brief['key_message']) ? trim((string) $brief['key_message']) : '';
        $differentiators = array_values(array_filter(
            array_map(fn (mixed $item): string => trim((string) $item), $brief['differentiators'] ?? []),
            fn (string $item): bool => $item !== '',
        ));

        $messages = $differentiators;

        if ($keyMessage !== '') {
            $messages[] = $keyMessage;
        }

        $editorial = $brief['editorial'] ?? [];
        $do = array_values(array_filter(
            array_map(fn (mixed $item): string => trim((string) $item), $editorial['do'] ?? []),
            fn (string $item): bool => $item !== '',
        ));
        $avoid = array_values(array_filter(
            array_map(fn (mixed $item): string => trim((string) $item), $editorial['avoid'] ?? []),
            fn (string $item): bool => $item !== '',
        ));

        $guidelineLines = [];

        foreach ($do as $item) {
            $guidelineLines[] = 'Do: '.$item;
        }

        foreach ($avoid as $item) {
            $guidelineLines[] = 'Avoid: '.$item;
        }

        return [
            'goal' => $context !== '' ? $context : null,
            'key_messages' => $messages === [] ? null : $messages,
            'guidelines' => $guidelineLines === [] ? null : implode("\n", $guidelineLines),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function listPayload(Campaign $campaign): array
    {
        return [
            'id' => $campaign->id,
            'name' => $campaign->name,
            'type' => $campaign->type->value,
            'objective' => $campaign->objective->value,
            'status' => $campaign->status->value,
            'budget_cents' => $campaign->budget_cents,
            'start_at' => $campaign->start_at?->toIso8601String(),
            'end_at' => $campaign->end_at?->toIso8601String(),
            'collab_count' => (int) ($campaign->collaborations_count ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detailPayload(Campaign $campaign): array
    {
        $icp = $campaign->companyIcp;

        return [
            ...$this->listPayload($campaign),
            'company_icp_id' => $campaign->company_icp_id,
            'company_icp' => $icp === null ? null : [
                'id' => $icp->id,
                'title' => $icp->title,
            ],
            'goal' => $campaign->goal,
            'key_messages' => $campaign->key_messages,
            'guidelines' => $campaign->guidelines,
            'brief' => $campaign->brief,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function shortlistedPayload(Collaboration $collaboration): array
    {
        $profile = $collaboration->creatorProfile;
        $fromPrice = $profile->offers->min('price_cents') ?? $profile->price_cents;

        return [
            'id' => $profile->id,
            'collaboration_id' => $collaboration->id,
            'status' => $collaboration->status->value,
            'display_name' => $profile->display_name,
            'headline' => $profile->headline,
            'photo_url' => PublicDisk::url($profile->photo_path),
            'country' => $profile->country,
            'from_price_cents' => $fromPrice,
            'followers_count' => $profile->audienceProfiles->first()?->followers_count,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function postPayload(Post $post): array
    {
        $profile = $post->collaboration->creatorProfile;
        $excerpt = $post->body === null ? null : Str::limit($post->body, 180);

        return [
            'id' => $post->id,
            'collaboration_id' => $post->collaboration_id,
            'status' => $post->status->value,
            'body' => $excerpt,
            'published_url' => $post->published_url,
            'submitted_at' => $post->submitted_at?->toIso8601String(),
            'creator' => [
                'id' => $profile->id,
                'display_name' => $profile->display_name,
                'photo_url' => PublicDisk::url($profile->photo_path),
            ],
        ];
    }
}
