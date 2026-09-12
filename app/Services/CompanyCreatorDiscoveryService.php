<?php

namespace App\Services;

use App\Enums\CreatorVettingStatus;
use App\Models\CreatorAudienceProfile;
use App\Models\CreatorOffer;
use App\Models\CreatorProfile;
use App\Models\Niche;
use App\Support\PublicDisk;
use Illuminate\Database\Eloquent\Builder;

class CompanyCreatorDiscoveryService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{items: list<array<string, mixed>>, current_page: int, last_page: int, total: int}
     */
    public function index(array $filters): array
    {
        $query = $this->marketplaceQuery();

        $q = isset($filters['q']) ? trim((string) $filters['q']) : '';

        if ($q !== '') {
            $like = '%'.$q.'%';
            $query->where(function (Builder $builder) use ($like): void {
                $builder->where('display_name', 'like', $like)
                    ->orWhere('headline', 'like', $like)
                    ->orWhere('bio', 'like', $like);
            });
        }

        if (isset($filters['niche_id'])) {
            $query->whereHas('niches', function (Builder $builder) use ($filters): void {
                $builder->where('niches.id', (int) $filters['niche_id']);
            });
        }

        if (isset($filters['country'])) {
            $query->where('country', $filters['country']);
        }

        $this->constrainListedPrice($query, $filters);
        $this->constrainFollowers($query, $filters);

        $page = $query
            ->with($this->listRelations())
            ->orderBy('id')
            ->paginate(24);

        return [
            'items' => $page->getCollection()
                ->map(fn (CreatorProfile $profile): array => $this->listPayload($profile))
                ->values()
                ->all(),
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'total' => $page->total(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function show(CreatorProfile $profile): array
    {
        abort_unless($this->isListed($profile), 404);

        $profile->load($this->listRelations());

        $latestAudience = $profile->audienceProfiles->first();

        return [
            ...$this->listPayload($profile),
            'bio' => $profile->bio,
            'linkedin_url' => $profile->linkedin_url,
            'audience_mix' => $latestAudience?->audience_mix ?? [],
            'captured_at' => $latestAudience?->captured_at?->toIso8601String(),
            'offers' => $profile->offers
                ->map(fn (CreatorOffer $offer): array => [
                    'id' => $offer->id,
                    'label' => $offer->label->value,
                    'posts_count' => $offer->posts_count,
                    'price_cents' => $offer->price_cents,
                ])
                ->values()
                ->all(),
            'recent_metrics' => [],
        ];
    }

    /**
     * @return Builder<CreatorProfile>
     */
    private function marketplaceQuery(): Builder
    {
        return CreatorProfile::query()
            ->where('vetting_status', CreatorVettingStatus::Vetted)
            ->whereNotNull('onboarded_at');
    }

    private function isListed(CreatorProfile $profile): bool
    {
        return $profile->vetting_status === CreatorVettingStatus::Vetted
            && $profile->onboarded_at !== null;
    }

    /**
     * @return array<string, \Closure>
     */
    private function listRelations(): array
    {
        return [
            'niches' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            'offers' => fn ($query) => $query->where('is_active', true)->orderBy('price_cents')->orderBy('id'),
            'audienceProfiles' => fn ($query) => $query->orderByDesc('captured_at')->orderByDesc('id'),
        ];
    }

    /**
     * @param  Builder<CreatorProfile>  $query
     * @param  array<string, mixed>  $filters
     */
    private function constrainListedPrice(Builder $query, array $filters): void
    {
        $min = $filters['min_price_cents'] ?? null;
        $max = $filters['max_price_cents'] ?? null;

        if ($min === null && $max === null) {
            return;
        }

        [$sql, $bindings] = $this->listedPriceExpression();

        if ($min !== null) {
            $query->whereRaw($sql.' >= ?', [...$bindings, (int) $min]);
        }

        if ($max !== null) {
            $query->whereRaw($sql.' <= ?', [...$bindings, (int) $max]);
        }
    }

    /**
     * @param  Builder<CreatorProfile>  $query
     * @param  array<string, mixed>  $filters
     */
    private function constrainFollowers(Builder $query, array $filters): void
    {
        $min = $filters['min_followers'] ?? null;
        $max = $filters['max_followers'] ?? null;

        if ($min === null && $max === null) {
            return;
        }

        [$sql, $bindings] = $this->latestFollowersExpression();

        if ($min !== null) {
            $query->whereRaw($sql.' >= ?', [...$bindings, (int) $min]);
        }

        if ($max !== null) {
            $query->whereRaw($sql.' <= ?', [...$bindings, (int) $max]);
        }
    }

    /**
     * @return array{0: string, 1: list<mixed>}
     */
    private function listedPriceExpression(): array
    {
        // ponytail: offer table is empty until offer CRUD; onboarding rate still lives on the profile
        $offers = CreatorOffer::query()
            ->selectRaw('min(price_cents)')
            ->whereColumn('creator_profile_id', 'creator_profiles.id')
            ->where('is_active', true);

        return ['coalesce(('.$offers->toSql().'), creator_profiles.price_cents)', $offers->getBindings()];
    }

    /**
     * @return array{0: string, 1: list<mixed>}
     */
    private function latestFollowersExpression(): array
    {
        $followers = CreatorAudienceProfile::query()
            ->select('followers_count')
            ->whereColumn('creator_profile_id', 'creator_profiles.id')
            ->orderByDesc('captured_at')
            ->orderByDesc('id')
            ->limit(1);

        return ['('.$followers->toSql().')', $followers->getBindings()];
    }

    /**
     * @return array<string, mixed>
     */
    private function listPayload(CreatorProfile $profile): array
    {
        $fromPrice = $profile->offers->min('price_cents') ?? $profile->price_cents;

        return [
            'id' => $profile->id,
            'display_name' => $profile->display_name,
            'headline' => $profile->headline,
            'photo_url' => PublicDisk::url($profile->photo_path),
            'country' => $profile->country,
            'niches' => $profile->niches->map(fn (Niche $niche): array => [
                'id' => $niche->id,
                'name' => $niche->name,
                'slug' => $niche->slug,
            ])->values()->all(),
            'followers_count' => $profile->audienceProfiles->first()?->followers_count,
            'from_price_cents' => $fromPrice,
            'linkedin_url' => $profile->linkedin_url,
        ];
    }
}
