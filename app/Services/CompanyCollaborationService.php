<?php

namespace App\Services;

use App\Enums\CollaborationEventType;
use App\Enums\CollaborationSource;
use App\Enums\CollaborationStatus;
use App\Enums\CreatorVettingStatus;
use App\Exceptions\WalletUnderfundedException;
use App\Models\Campaign;
use App\Models\Collaboration;
use App\Models\CollaborationEvent;
use App\Models\Company;
use App\Models\Contract;
use App\Models\CreatorOffer;
use App\Models\CreatorProfile;
use App\Models\Post;
use App\Models\User;
use App\Support\CompanyAccess;
use App\Support\PublicDisk;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CompanyCollaborationService
{
    public function __construct(
        private CompanyWalletService $wallets,
        private ContractService $contracts,
    ) {}

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

        $this->recordEvent(
            $collaboration,
            CollaborationEventType::StatusChange,
            $actor,
            null,
            ['from' => null, 'to' => CollaborationStatus::Invited->value],
        );

        return $this->payload($collaboration);
    }

    /**
     * @return array<string, mixed>
     */
    public function source(Company $company, User $actor, Campaign $campaign, int $creatorProfileId): array
    {
        $this->ensureCampaignOwned($company, $campaign);

        $profile = CreatorProfile::query()->find($creatorProfileId);

        if ($profile === null || ! $this->isListed($profile)) {
            throw ValidationException::withMessages([
                'creator_profile_id' => 'This creator cannot be sourced.',
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
            'source' => CollaborationSource::Sourced,
            'status' => CollaborationStatus::Outreach,
            'invited_by_user_id' => $actor->id,
        ]);

        $collaboration->load($this->creatorRelations());

        $this->recordEvent(
            $collaboration,
            CollaborationEventType::StatusChange,
            $actor,
            null,
            ['from' => null, 'to' => CollaborationStatus::Outreach->value],
        );

        return $this->payload($collaboration);
    }

    /**
     * @return array<string, mixed>
     */
    public function select(Company $company, User $actor, Collaboration $collaboration): array
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

        $from = $collaboration->status;
        $collaboration->status = CollaborationStatus::Selected;
        $collaboration->save();
        $collaboration->load($this->creatorRelations());

        $this->recordEvent(
            $collaboration,
            CollaborationEventType::StatusChange,
            $actor,
            null,
            ['from' => $from->value, 'to' => CollaborationStatus::Selected->value],
        );

        return $this->payload($collaboration);
    }

    /**
     * @return array<string, mixed>
     */
    public function book(Company $company, User $actor, Collaboration $collaboration, ?int $offerId = null): array
    {
        $this->ensureCollaborationOwned($company, $collaboration);
        CompanyAccess::ensureCanManageMoney($actor, $company);

        if ($collaboration->status !== CollaborationStatus::Selected) {
            throw ValidationException::withMessages([
                'status' => 'Only shortlisted collaborations can be booked.',
            ]);
        }

        $offer = $this->resolveOffer($collaboration, $offerId);
        $wallet = $this->wallets->forCompany($company);

        if ($wallet->available_cents < $offer['price_cents']) {
            $shortfall = $offer['price_cents'] - $wallet->available_cents;
            $amount = max((int) config('wallet.topup_min_cents'), $shortfall);
            $topup = $this->wallets->startTopup($company, $actor, $amount);

            throw new WalletUnderfundedException([
                'checkout_url' => $topup['checkout_url'],
                'stripe_session_id' => $topup['stripe_session_id'],
                'available_cents' => $wallet->available_cents,
                'required_cents' => $offer['price_cents'],
                'shortfall_cents' => $shortfall,
            ]);
        }

        return DB::transaction(function () use ($company, $actor, $collaboration, $offer): array {
            $this->wallets->hold($company, $collaboration, $offer['price_cents']);

            $collaboration->status = CollaborationStatus::Booked;
            $collaboration->creator_offer_id = $offer['id'];
            $collaboration->booked_price_cents = $offer['price_cents'];
            $collaboration->booked_posts_count = $offer['posts_count'];
            $collaboration->booked_at = now();
            $collaboration->save();
            $collaboration->load(['campaign', 'creatorProfile']);

            $this->contracts->generate($company, $collaboration);
            $collaboration->load($this->creatorRelations());

            $this->recordEvent(
                $collaboration,
                CollaborationEventType::StatusChange,
                $actor,
                null,
                ['from' => CollaborationStatus::Selected->value, 'to' => CollaborationStatus::Booked->value],
            );

            return $this->payload($collaboration);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function cancel(Company $company, User $actor, Collaboration $collaboration): array
    {
        $this->ensureCollaborationOwned($company, $collaboration);

        if (in_array($collaboration->status, [CollaborationStatus::Cancelled, CollaborationStatus::Completed], true)) {
            throw ValidationException::withMessages([
                'status' => 'This collaboration cannot be cancelled.',
            ]);
        }

        $from = $collaboration->status;

        if ($from === CollaborationStatus::Booked) {
            CompanyAccess::ensureCanManageMoney($actor, $company);
            $this->wallets->releaseHold($company, $collaboration);
            $this->contracts->void($collaboration);
        }

        $collaboration->status = CollaborationStatus::Cancelled;
        $collaboration->cancelled_at = now();
        $collaboration->save();
        $collaboration->load($this->creatorRelations());

        $this->recordEvent(
            $collaboration,
            CollaborationEventType::StatusChange,
            $actor,
            null,
            ['from' => $from->value, 'to' => CollaborationStatus::Cancelled->value],
        );

        return $this->payload($collaboration);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    public function indexForCompany(Company $company, array $filters): array
    {
        $query = Collaboration::query()
            ->whereHas('campaign', fn ($campaign) => $campaign->where('company_id', $company->id))
            ->with($this->creatorRelations())
            ->with('campaign')
            ->orderByDesc('id');

        $status = $filters['status'] ?? null;

        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        } else {
            $query->whereNot('status', CollaborationStatus::Cancelled);
        }

        return $query->get()
            ->map(fn (Collaboration $collaboration): array => [
                ...$this->payload($collaboration),
                'campaign' => [
                    'id' => $collaboration->campaign->id,
                    'name' => $collaboration->campaign->name,
                ],
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function show(Company $company, Collaboration $collaboration): array
    {
        $this->ensureCollaborationOwned($company, $collaboration);

        $collaboration->load([
            ...$this->creatorRelations(),
            'campaign',
            'contract',
            'posts' => fn ($query) => $query->orderByDesc('id'),
            'events' => fn ($query) => $query->orderByDesc('occurred_at')->orderByDesc('id'),
            'events.actor',
        ]);

        return [
            ...$this->payload($collaboration),
            'campaign' => [
                'id' => $collaboration->campaign->id,
                'name' => $collaboration->campaign->name,
            ],
            'accepted_at' => $collaboration->accepted_at?->toIso8601String(),
            'booked_at' => $collaboration->booked_at?->toIso8601String(),
            'cancelled_at' => $collaboration->cancelled_at?->toIso8601String(),
            'contract' => $collaboration->contract instanceof Contract
                ? [
                    'id' => $collaboration->contract->id,
                    'status' => $collaboration->contract->status->value,
                ]
                : null,
            'events' => $collaboration->events
                ->map(fn (CollaborationEvent $event): array => $this->eventPayload($event))
                ->all(),
            'posts' => $collaboration->posts
                ->map(fn (Post $post): array => $this->postPayload($post))
                ->all(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function events(Company $company, Collaboration $collaboration): array
    {
        $this->ensureCollaborationOwned($company, $collaboration);

        return $collaboration->events()
            ->with('actor')
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (CollaborationEvent $event): array => $this->eventPayload($event))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function followUp(Company $company, User $actor, Collaboration $collaboration, string $type, string $body): array
    {
        $this->ensureCollaborationOwned($company, $collaboration);

        $eventType = $type === CollaborationEventType::Note->value
            ? CollaborationEventType::Note
            : CollaborationEventType::FollowUp;

        $this->recordEvent($collaboration, $eventType, $actor, $body);

        $event = $collaboration->events()->with('actor')->orderByDesc('id')->first();

        if (! $event instanceof CollaborationEvent) {
            abort(500, 'Could not record the follow-up.');
        }

        return $this->eventPayload($event);
    }

    /**
     * @return array<string, mixed>
     */
    public function contract(Company $company, Collaboration $collaboration): array
    {
        $this->ensureCollaborationOwned($company, $collaboration);

        $contract = $collaboration->contract;

        if (! $contract instanceof Contract) {
            abort(404);
        }

        return $this->contracts->show($contract);
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

    /**
     * @return array{id: int|null, price_cents: int, posts_count: int}
     */
    private function resolveOffer(Collaboration $collaboration, ?int $offerId): array
    {
        $collaboration->loadMissing($this->creatorRelations());
        $profile = $collaboration->creatorProfile;

        if ($offerId !== null) {
            $offer = $profile->offers->firstWhere('id', $offerId);

            if (! $offer instanceof CreatorOffer) {
                throw ValidationException::withMessages([
                    'creator_offer_id' => 'The selected offer is invalid.',
                ]);
            }

            return [
                'id' => $offer->id,
                'price_cents' => $offer->price_cents,
                'posts_count' => $offer->posts_count,
            ];
        }

        $offer = $profile->offers->sortBy('price_cents')->first();

        if ($offer instanceof CreatorOffer) {
            return [
                'id' => $offer->id,
                'price_cents' => $offer->price_cents,
                'posts_count' => $offer->posts_count,
            ];
        }

        if ($profile->price_cents === null) {
            throw ValidationException::withMessages([
                'price' => 'This creator has no bookable price.',
            ]);
        }

        return [
            'id' => null,
            'price_cents' => $profile->price_cents,
            'posts_count' => 1,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $meta
     */
    public function recordEvent(
        Collaboration $collaboration,
        CollaborationEventType $type,
        ?User $actor,
        ?string $body = null,
        ?array $meta = null,
    ): void {
        $collaboration->events()->create([
            'actor_user_id' => $actor?->id,
            'type' => $type,
            'body' => $body,
            'meta' => $meta,
            'occurred_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function eventPayload(CollaborationEvent $event): array
    {
        return [
            'id' => $event->id,
            'type' => $event->type->value,
            'body' => $event->body,
            'meta' => $event->meta,
            'occurred_at' => $event->occurred_at->toIso8601String(),
            'actor' => $event->actor === null ? null : [
                'id' => $event->actor->id,
                'name' => $event->actor->name,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function postPayload(Post $post): array
    {
        $excerpt = $post->body === null ? null : Str::limit($post->body, 180);

        return [
            'id' => $post->id,
            'status' => $post->status->value,
            'body' => $excerpt,
            'published_url' => $post->published_url,
            'submitted_at' => $post->submitted_at?->toIso8601String(),
        ];
    }
}
