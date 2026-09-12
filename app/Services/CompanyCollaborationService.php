<?php

namespace App\Services;

use App\Enums\CollaborationSource;
use App\Enums\CollaborationStatus;
use App\Enums\CreatorVettingStatus;
use App\Models\Campaign;
use App\Models\Collaboration;
use App\Models\Company;
use App\Models\CreatorProfile;
use App\Models\User;
use App\Support\PublicDisk;
use Illuminate\Validation\ValidationException;

class CompanyCollaborationService
{
    /**
     * @var array<string, list<CollaborationStatus>|null>
     */
    public const PIPELINES = [
        'all' => null,
        'active' => [CollaborationStatus::Booked],
        'invitations_received' => [CollaborationStatus::Applied],
        'invitations_sent' => [CollaborationStatus::Invited],
        'todo' => [CollaborationStatus::Selected, CollaborationStatus::Outreach],
        'completed' => [CollaborationStatus::Completed],
    ];

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    public function index(Company $company, Campaign $campaign, array $filters): array
    {
        $this->ensureCampaignOwned($company, $campaign);

        $query = $campaign->collaborations()
            ->with($this->creatorRelations())
            ->orderByDesc('id');

        $pipeline = $filters['pipeline'] ?? null;
        $status = $filters['status'] ?? null;

        if (is_string($pipeline) && $pipeline !== '' && $pipeline !== 'all') {
            $statuses = self::PIPELINES[$pipeline] ?? null;

            if ($statuses !== null) {
                $query->whereIn('status', $statuses);
            }
        } elseif (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        if ($pipeline === 'all' || ($pipeline === null && $status === null)) {
            $query->whereNot('status', CollaborationStatus::Cancelled);
        }

        return $query->get()
            ->map(fn (Collaboration $collaboration): array => $this->payload($collaboration))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function invite(Company $company, User $actor, Campaign $campaign, int $creatorProfileId): array
    {
        $this->ensureCampaignOwned($company, $campaign);

        $profile = CreatorProfile::query()->find($creatorProfileId);

        if ($profile === null || ! $this->isListed($profile)) {
            throw ValidationException::withMessages([
                'creator_profile_id' => 'This creator cannot be invited.',
            ]);
        }

        $exists = $campaign->collaborations()
            ->where('creator_profile_id', $profile->id)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'creator_profile_id' => 'This creator is already on the campaign.',
            ]);
        }

        $collaboration = $campaign->collaborations()->create([
            'creator_profile_id' => $profile->id,
            'source' => CollaborationSource::Invite,
            'status' => CollaborationStatus::Invited,
            'invited_by_user_id' => $actor->id,
        ]);

        $collaboration->load($this->creatorRelations());

        return $this->payload($collaboration);
    }

    /**
     * @return array<string, mixed>
     */
    public function select(Company $company, Collaboration $collaboration): array
    {
        $this->ensureCollaborationOwned($company, $collaboration);

        $allowed = [
            CollaborationStatus::Invited,
            CollaborationStatus::Applied,
            CollaborationStatus::Outreach,
        ];

        if (! in_array($collaboration->status, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => 'This collaboration cannot be selected.',
            ]);
        }

        $collaboration->status = CollaborationStatus::Selected;
        $collaboration->accepted_at = $collaboration->accepted_at ?? now();
        $collaboration->save();
        $collaboration->load($this->creatorRelations());

        return $this->payload($collaboration);
    }

    /**
     * @return array<string, mixed>
     */
    public function cancel(Company $company, Collaboration $collaboration): array
    {
        $this->ensureCollaborationOwned($company, $collaboration);

        if (in_array($collaboration->status, [CollaborationStatus::Cancelled, CollaborationStatus::Completed], true)) {
            throw ValidationException::withMessages([
                'status' => 'This collaboration cannot be cancelled.',
            ]);
        }

        $collaboration->status = CollaborationStatus::Cancelled;
        $collaboration->cancelled_at = now();
        $collaboration->save();
        $collaboration->load($this->creatorRelations());

        return $this->payload($collaboration);
    }

    /**
     * @param  array<string, int|string>  $statusCounts
     * @return array{all: int, active: int, invitations_received: int, invitations_sent: int, todo: int, completed: int}
     */
    public function countsFromStatuses(array $statusCounts): array
    {
        $value = function (CollaborationStatus $status) use ($statusCounts): int {
            return (int) ($statusCounts[$status->value] ?? 0);
        };

        $cancelled = $value(CollaborationStatus::Cancelled);
        $total = array_sum(array_map(intval(...), $statusCounts));

        return [
            'all' => $total - $cancelled,
            'active' => $value(CollaborationStatus::Booked),
            'invitations_received' => $value(CollaborationStatus::Applied),
            'invitations_sent' => $value(CollaborationStatus::Invited),
            'todo' => $value(CollaborationStatus::Selected) + $value(CollaborationStatus::Outreach),
            'completed' => $value(CollaborationStatus::Completed),
        ];
    }

    private function isListed(CreatorProfile $profile): bool
    {
        return $profile->vetting_status === CreatorVettingStatus::Vetted
            && $profile->onboarded_at !== null;
    }

    private function ensureCampaignOwned(Company $company, Campaign $campaign): void
    {
        if ($campaign->company_id !== $company->id) {
            abort(404);
        }
    }

    private function ensureCollaborationOwned(Company $company, Collaboration $collaboration): void
    {
        $collaboration->loadMissing('campaign');

        if ($collaboration->campaign->company_id !== $company->id) {
            abort(404);
        }
    }

    /**
     * @return array<string, \Closure>
     */
    private function creatorRelations(): array
    {
        return [
            'creatorProfile.offers' => fn ($query) => $query
                ->where('is_active', true)
                ->orderBy('price_cents')
                ->orderBy('id'),
            'creatorProfile.audienceProfiles' => fn ($query) => $query
                ->orderByDesc('captured_at')
                ->orderByDesc('id'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Collaboration $collaboration): array
    {
        $profile = $collaboration->creatorProfile;
        $fromPrice = $profile->offers->min('price_cents') ?? $profile->price_cents;

        return [
            'id' => $collaboration->id,
            'source' => $collaboration->source->value,
            'status' => $collaboration->status->value,
            'booked_price_cents' => $collaboration->booked_price_cents,
            'booked_posts_count' => $collaboration->booked_posts_count,
            'creator' => [
                'id' => $profile->id,
                'display_name' => $profile->display_name,
                'headline' => $profile->headline,
                'photo_url' => PublicDisk::url($profile->photo_path),
                'country' => $profile->country,
                'from_price_cents' => $fromPrice,
            ],
        ];
    }
}
