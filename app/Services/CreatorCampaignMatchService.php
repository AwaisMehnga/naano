<?php

namespace App\Services;

use App\Ai\Agents\CampaignFitAgent;
use App\Models\Campaign;
use App\Models\CreatorMatchScore;
use App\Models\CreatorProfile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Throwable;

class CreatorCampaignMatchService
{
    public const MIN_FIT_SCORE = 30;

    public function score(CreatorProfile $profile, Campaign $campaign): ?CreatorMatchScore
    {
        $profile->loadMissing(['niches', 'audienceProfiles']);
        $campaign->loadMissing(['company', 'companyIcp']);

        if (! $this->passesHardFilter($profile, $campaign)) {
            return null;
        }

        $cached = $this->freshScore($profile, $campaign);

        if ($cached instanceof CreatorMatchScore) {
            return $this->aboveThreshold($cached);
        }

        try {
            return $this->aboveThreshold($this->compute($profile, $campaign));
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  Collection<int, Campaign>  $campaigns
     * @return Collection<int, CreatorMatchScore>
     */
    public function cachedByCampaign(CreatorProfile $profile, Collection $campaigns): Collection
    {
        if ($campaigns->isEmpty()) {
            return collect();
        }

        return CreatorMatchScore::query()
            ->where('creator_profile_id', $profile->id)
            ->whereIn('campaign_id', $campaigns->modelKeys())
            ->get()
            ->keyBy('campaign_id');
    }

    public function forListing(
        CreatorProfile $profile,
        Campaign $campaign,
        ?CreatorMatchScore $cached,
    ): ?CreatorMatchScore {
        if (! $this->passesHardFilter($profile, $campaign)) {
            return null;
        }

        if ($cached instanceof CreatorMatchScore && ! $this->isStale($profile, $campaign, $cached)) {
            return $this->aboveThreshold($cached);
        }

        return $this->provisional($profile, $campaign);
    }

    private function aboveThreshold(CreatorMatchScore $score): ?CreatorMatchScore
    {
        if ($score->fit_score < self::MIN_FIT_SCORE) {
            return null;
        }

        return $score;
    }

    private function passesHardFilter(CreatorProfile $profile, Campaign $campaign): bool
    {
        $icp = $campaign->companyIcp;

        if ($icp === null) {
            return true;
        }

        $industries = $this->tokens(array_merge($icp->industries ?? [], $icp->tags ?? []));

        if ($industries === []) {
            return true;
        }

        return $this->overlapsNiches($profile, $industries);
    }

    /**
     * @param  list<string>  $needles
     */
    private function overlapsNiches(CreatorProfile $profile, array $needles): bool
    {
        $haystack = $this->tokens(
            $profile->niches
                ->flatMap(fn ($niche): array => [$niche->name, $niche->slug])
                ->all(),
        );

        foreach ($needles as $needle) {
            if (in_array($needle, $haystack, true)) {
                return true;
            }
        }

        return false;
    }

    private function freshScore(CreatorProfile $profile, Campaign $campaign): ?CreatorMatchScore
    {
        $score = CreatorMatchScore::query()
            ->where('campaign_id', $campaign->id)
            ->where('creator_profile_id', $profile->id)
            ->first();

        if (! $score instanceof CreatorMatchScore || $this->isStale($profile, $campaign, $score)) {
            return null;
        }

        return $score;
    }

    private function isStale(
        CreatorProfile $profile,
        Campaign $campaign,
        CreatorMatchScore $score,
    ): bool {
        $freshAfter = collect([
            $profile->updated_at,
            $campaign->updated_at,
            $campaign->companyIcp?->updated_at,
            $profile->niches->max(fn ($niche) => $niche->pivot->updated_at),
        ])->filter()->max();

        return $freshAfter instanceof Carbon && $score->computed_at?->lt($freshAfter);
    }

    private function provisional(CreatorProfile $profile, Campaign $campaign): CreatorMatchScore
    {
        return new CreatorMatchScore([
            'campaign_id' => $campaign->id,
            'creator_profile_id' => $profile->id,
            'fit_score' => 50,
            'audience_relevance' => null,
            'reasons' => [],
            'computed_at' => now(),
        ]);
    }

    private function compute(CreatorProfile $profile, Campaign $campaign): CreatorMatchScore
    {
        $audience = $profile->audienceProfiles
            ->sortByDesc('captured_at')
            ->first();

        $response = (new CampaignFitAgent)->prompt(
            implode("\n\n", [
                'Score this creator against this campaign. Return only the structured fields.',
                'Creator: '.json_encode([
                    'display_name' => $profile->display_name,
                    'headline' => $profile->headline,
                    'bio' => $profile->bio,
                    'country' => $profile->country,
                    'niches' => $profile->niches->pluck('name')->values()->all(),
                    'followers_count' => $audience?->followers_count,
                    'audience_mix' => $audience?->audience_mix ?? [],
                ]),
                'Campaign: '.json_encode([
                    'name' => $campaign->name,
                    'type' => $campaign->type->value,
                    'objective' => $campaign->objective->value,
                    'goal' => $campaign->goal,
                    'key_messages' => $campaign->key_messages,
                    'company' => $campaign->company->name,
                    'company_country' => $campaign->company->country,
                    'icp' => $campaign->companyIcp === null ? null : [
                        'title' => $campaign->companyIcp->title,
                        'industries' => $campaign->companyIcp->industries,
                        'regions' => $campaign->companyIcp->regions,
                        'tags' => $campaign->companyIcp->tags,
                    ],
                ]),
            ]),
            provider: Lab::DeepSeek,
            timeout: 60,
        );

        $data = $response instanceof StructuredAgentResponse
            ? $response->toArray()
            : [];

        $reasons = collect($data['reasons'] ?? [])
            ->filter(fn (mixed $reason): bool => is_string($reason) && $reason !== '')
            ->take(5)
            ->values()
            ->all();

        $attributes = [
            'fit_score' => $this->clampScore($data['fit_score'] ?? 0),
            'audience_relevance' => $this->clampScore($data['audience_relevance'] ?? 0),
            'reasons' => $reasons,
            'computed_at' => now(),
        ];

        $score = CreatorMatchScore::query()
            ->where('campaign_id', $campaign->id)
            ->where('creator_profile_id', $profile->id)
            ->first();

        if ($score instanceof CreatorMatchScore) {
            $score->fill($attributes);
            $score->save();

            return $score;
        }

        return CreatorMatchScore::query()->create([
            'campaign_id' => $campaign->id,
            'creator_profile_id' => $profile->id,
            ...$attributes,
        ]);
    }

    private function clampScore(mixed $value): int
    {
        return max(0, min(100, (int) $value));
    }

    /**
     * @param  list<mixed>  $values
     * @return list<string>
     */
    private function tokens(array $values): array
    {
        return collect($values)
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->map(fn (string $value): string => Str::lower(trim($value)))
            ->unique()
            ->values()
            ->all();
    }
}
