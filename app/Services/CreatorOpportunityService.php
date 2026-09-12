<?php

namespace App\Services;

use App\Enums\CampaignStatus;
use App\Enums\CollaborationEventType;
use App\Enums\CollaborationSource;
use App\Enums\CollaborationStatus;
use App\Enums\CreatorVettingStatus;
use App\Models\Campaign;
use App\Models\Collaboration;
use App\Models\Contract;
use App\Models\CreatorProfile;
use App\Models\Post;
use App\Models\User;
use App\Support\PublicDisk;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreatorOpportunityService
{
    public function __construct(
        private CompanyCollaborationService $collaborations,
        private ContractService $contracts,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function opportunities(User $user): array
    {
        $profile = $this->profile($user);

        if (! $this->isListed($profile)) {
            return [];
        }

        return Campaign::query()
            ->where('status', CampaignStatus::Active)
            ->whereDoesntHave(
                'collaborations',
                fn ($query) => $query->where('creator_profile_id', $profile->id),
            )
            ->with('company')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Campaign $campaign): array => $this->opportunityPayload($campaign))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function showOpportunity(User $user, Campaign $campaign): array
    {
        $profile = $this->profile($user);
        $this->ensureEligible($profile, $campaign);

        $campaign->load('company');

        return [
            ...$this->opportunityPayload($campaign),
            'brief' => $campaign->brief,
            'goal' => $campaign->goal,
            'key_messages' => $campaign->key_messages,
            'guidelines' => $campaign->guidelines,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function apply(User $user, Campaign $campaign): array
    {
        $profile = $this->profile($user);
        $this->ensureEligible($profile, $campaign);

        $collaboration = $campaign->collaborations()->create([
            'creator_profile_id' => $profile->id,
            'source' => CollaborationSource::Apply,
            'status' => CollaborationStatus::Applied,
        ]);

        $this->collaborations->recordEvent(
            $collaboration,
            CollaborationEventType::StatusChange,
            $user,
            null,
            ['from' => null, 'to' => CollaborationStatus::Applied->value],
        );

        $collaboration->load(['campaign.company', 'creatorProfile']);

        return $this->dealPayload($collaboration);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    public function collaborations(User $user, array $filters): array
    {
        $profile = $this->profile($user);

        $query = Collaboration::query()
            ->where('creator_profile_id', $profile->id)
            ->with(['campaign.company', 'creatorProfile'])
            ->orderByDesc('id');

        $status = $filters['status'] ?? null;

        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        return $query->get()
            ->map(fn (Collaboration $collaboration): array => $this->dealPayload($collaboration))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function showCollaboration(User $user, Collaboration $collaboration): array
    {
        $this->ensureOwned($user, $collaboration);

        $collaboration->load([
            'campaign.company',
            'creatorProfile',
            'contract',
            'posts' => fn ($query) => $query->orderByDesc('id'),
        ]);

        $campaign = $collaboration->campaign;

        return [
            ...$this->dealPayload($collaboration),
            'brief' => $campaign->brief,
            'goal' => $campaign->goal,
            'key_messages' => $campaign->key_messages,
            'guidelines' => $campaign->guidelines,
            'contract' => $collaboration->contract instanceof Contract
                ? [
                    'id' => $collaboration->contract->id,
                    'status' => $collaboration->contract->status->value,
                ]
                : null,
            'posts' => $collaboration->posts
                ->map(fn (Post $post): array => [
                    'id' => $post->id,
                    'status' => $post->status->value,
                    'body' => $post->body === null ? null : Str::limit($post->body, 180),
                    'published_url' => $post->published_url,
                ])
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function accept(User $user, Collaboration $collaboration): array
    {
        $this->ensureOwned($user, $collaboration);

        if ($collaboration->status !== CollaborationStatus::Invited) {
            throw ValidationException::withMessages([
                'status' => 'Only invited collaborations can be accepted.',
            ]);
        }

        $collaboration->status = CollaborationStatus::Selected;
        $collaboration->accepted_at = now();
        $collaboration->save();

        $this->collaborations->recordEvent(
            $collaboration,
            CollaborationEventType::StatusChange,
            $user,
            null,
            ['from' => CollaborationStatus::Invited->value, 'to' => CollaborationStatus::Selected->value],
        );

        $collaboration->load(['campaign.company', 'creatorProfile']);

        return $this->dealPayload($collaboration);
    }

    /**
     * @return array<string, mixed>
     */
    public function decline(User $user, Collaboration $collaboration): array
    {
        $this->ensureOwned($user, $collaboration);

        $allowed = [CollaborationStatus::Invited, CollaborationStatus::Applied];

        if (! in_array($collaboration->status, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => 'This collaboration cannot be declined.',
            ]);
        }

        $from = $collaboration->status;
        $collaboration->status = CollaborationStatus::Declined;
        $collaboration->save();

        $this->collaborations->recordEvent(
            $collaboration,
            CollaborationEventType::StatusChange,
            $user,
            null,
            ['from' => $from->value, 'to' => CollaborationStatus::Declined->value],
        );

        $collaboration->load(['campaign.company', 'creatorProfile']);

        return $this->dealPayload($collaboration);
    }

    /**
     * @return array<string, mixed>
     */
    public function contract(User $user, Collaboration $collaboration): array
    {
        $this->ensureOwned($user, $collaboration);

        $contract = $collaboration->contract;

        if (! $contract instanceof Contract) {
            abort(404);
        }

        return $this->contracts->show($contract);
    }

    private function profile(User $user): CreatorProfile
    {
        $profile = $user->creatorProfile;

        if (! $profile instanceof CreatorProfile) {
            abort(403, 'No creator profile.');
        }

        return $profile;
    }

    private function isListed(CreatorProfile $profile): bool
    {
        return $profile->vetting_status === CreatorVettingStatus::Vetted
            && $profile->onboarded_at !== null;
    }

    private function ensureEligible(CreatorProfile $profile, Campaign $campaign): void
    {
        if (! $this->isListed($profile) || $campaign->status !== CampaignStatus::Active) {
            abort(404);
        }

        $exists = $campaign->collaborations()
            ->where('creator_profile_id', $profile->id)
            ->exists();

        if ($exists) {
            abort(404);
        }
    }

    private function ensureOwned(User $user, Collaboration $collaboration): void
    {
        $profile = $this->profile($user);

        if ($collaboration->creator_profile_id !== $profile->id) {
            abort(404);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function opportunityPayload(Campaign $campaign): array
    {
        $company = $campaign->company;

        return [
            'id' => $campaign->id,
            'name' => $campaign->name,
            'type' => $campaign->type->value,
            'objective' => $campaign->objective->value,
            'status' => $campaign->status->value,
            'start_at' => $campaign->start_at?->toIso8601String(),
            'end_at' => $campaign->end_at?->toIso8601String(),
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'logo_url' => PublicDisk::url($company->logo_path),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dealPayload(Collaboration $collaboration): array
    {
        $campaign = $collaboration->campaign;
        $company = $campaign->company;

        return [
            'id' => $collaboration->id,
            'source' => $collaboration->source->value,
            'status' => $collaboration->status->value,
            'booked_price_cents' => $collaboration->booked_price_cents,
            'booked_posts_count' => $collaboration->booked_posts_count,
            'accepted_at' => $collaboration->accepted_at?->toIso8601String(),
            'booked_at' => $collaboration->booked_at?->toIso8601String(),
            'campaign' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'type' => $campaign->type->value,
                'objective' => $campaign->objective->value,
            ],
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'logo_url' => PublicDisk::url($company->logo_path),
            ],
        ];
    }
}
