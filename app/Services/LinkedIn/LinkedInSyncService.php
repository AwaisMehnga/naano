<?php

namespace App\Services\LinkedIn;

use App\Jobs\SyncLinkedInPostsJob;
use App\Models\CreatorAudienceProfile;
use App\Models\CreatorProfile;
use App\Services\Apify\ApifyClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class LinkedInSyncService
{
    public function __construct(
        private ApifyClient $apify,
        private LinkedInProfileNormalizer $profileNormalizer,
        private LinkedInPostsNormalizer $postsNormalizer,
        private AudienceMixBuilder $audienceMix,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function scrapeProfile(string $linkedinUrl): array
    {
        $actor = (string) config('services.apify.profile_actor');
        $items = $this->apify->runActor($actor, [
            'urls' => [
                ['url' => $linkedinUrl],
            ],
        ]);

        $raw = $items[0] ?? null;

        if (! is_array($raw)) {
            throw new RuntimeException('LinkedIn profile scrape returned no data.');
        }

        return $this->profileNormalizer->normalize($raw);
    }

    /**
     * @param  array<string, mixed>  $normalized
     */
    public function storeVerifiedProfile(CreatorProfile $profile, array $normalized): void
    {
        $displayName = trim(implode(' ', array_filter([
            $normalized['first_name'] ?? null,
            $normalized['last_name'] ?? null,
        ])));

        $photoPath = $this->storeLinkedInPhoto($profile, $normalized['picture_url'] ?? null)
            ?? $profile->photo_path;

        $profile->update([
            'linkedin_profile' => $normalized,
            'linkedin_verified_at' => now(),
            'linkedin_synced_at' => now(),
            'headline' => $this->stripVerifyCode(
                (string) ($normalized['headline'] ?? $profile->headline ?? ''),
                $profile->linkedin_verify_code,
            ) ?: ($normalized['headline'] ?? $profile->headline),
            'display_name' => $displayName !== '' ? $displayName : $profile->display_name,
            'country' => $normalized['country_code'] ?? $profile->country,
            'photo_path' => $photoPath,
            'bio' => $normalized['summary'] ?? $profile->bio,
        ]);

        $this->syncAudienceSnapshot(
            $profile,
            $normalized['follower_count'] ?? null,
            $normalized['connections_count'] ?? null,
            is_string($normalized['country_code'] ?? null) && $normalized['country_code'] !== ''
                ? ['geo' => [$normalized['country_code'] => 100]]
                : null,
        );
    }

    /**
     * Re-scrape profile and enqueue posts when already verified.
     *
     * @return array<string, mixed>
     */
    public function refresh(CreatorProfile $profile): array
    {
        if (! $profile->isLinkedInVerified()) {
            throw ValidationException::withMessages([
                'linkedin' => 'Verify your LinkedIn profile first.',
            ]);
        }

        if (! is_string($profile->linkedin_url) || $profile->linkedin_url === '') {
            throw ValidationException::withMessages([
                'linkedin_url' => 'LinkedIn URL is missing.',
            ]);
        }

        $cooldown = max(1, (int) config('services.apify.refresh_cooldown_minutes', 60));

        if ($profile->linkedin_synced_at instanceof Carbon
            && $profile->linkedin_synced_at->gt(now()->subMinutes($cooldown))) {
            throw ValidationException::withMessages([
                'linkedin' => "You can refresh again after {$cooldown} minutes.",
            ]);
        }

        $normalized = $this->scrapeProfile($profile->linkedin_url);
        $existingEngagers = is_array($profile->linkedin_profile['engagers'] ?? null)
            ? $profile->linkedin_profile['engagers']
            : null;
        $normalized['engagers'] = $existingEngagers;

        $displayName = trim(implode(' ', array_filter([
            $normalized['first_name'] ?? null,
            $normalized['last_name'] ?? null,
        ])));

        $photoPath = $this->storeLinkedInPhoto($profile, $normalized['picture_url'] ?? null)
            ?? $profile->photo_path;

        $profile->update([
            'linkedin_profile' => $normalized,
            'linkedin_synced_at' => now(),
            'headline' => $normalized['headline'] ?? $profile->headline,
            'display_name' => $displayName !== '' ? $displayName : $profile->display_name,
            'country' => $normalized['country_code'] ?? $profile->country,
            'photo_path' => $photoPath,
            'bio' => $normalized['summary'] ?? $profile->bio,
        ]);

        $this->syncAudienceSnapshot(
            $profile,
            $normalized['follower_count'] ?? null,
            $normalized['connections_count'] ?? null,
        );

        $fresh = $profile->fresh() ?? $profile;

        try {
            $this->syncPosts($fresh);
        } catch (\Throwable $e) {
            Log::warning('LinkedIn posts sync failed during refresh', [
                'creator_profile_id' => $fresh->id,
                'message' => $e->getMessage(),
            ]);

            SyncLinkedInPostsJob::dispatch($fresh->id);
        }

        return app(LinkedInProfilePresenter::class)->present($fresh->fresh() ?? $fresh);
    }

    public function syncPosts(CreatorProfile $profile): void
    {
        if (! is_string($profile->linkedin_url) || $profile->linkedin_url === '') {
            return;
        }

        set_time_limit(0);

        $actor = (string) config('services.apify.posts_actor');
        $maxPosts = max(1, (int) config('services.apify.max_posts', 50));
        $scrapeComments = filter_var(config('services.apify.scrape_comments', true), FILTER_VALIDATE_BOOLEAN);
        $maxComments = max(0, (int) config('services.apify.max_comments', 20));

        $input = [
            'targetUrls' => [$profile->linkedin_url],
            'maxPosts' => $maxPosts,
            'includeQuotePosts' => true,
            'includeReposts' => false,
        ];

        if ($scrapeComments && $maxComments > 0) {
            $input['scrapeComments'] = true;
            $input['maxComments'] = $maxComments;
            $input['commentsPostedLimit'] = $maxComments;
        }

        $items = $this->apify->runActor($actor, $input);
        $posts = $this->postsNormalizer->normalize($items, $maxPosts);
        $engagers = $this->postsNormalizer->engagersSummary($posts);

        $profile->refresh();
        $profileData = is_array($profile->linkedin_profile) ? $profile->linkedin_profile : [];
        $profileData['engagers'] = $engagers;
        $profileData['captured_at'] = $profileData['captured_at'] ?? now()->toIso8601String();

        $storedPosts = array_map(function (array $post): array {
            unset($post['commenters']);

            return $post;
        }, $posts);

        $profile->update([
            'linkedin_posts' => $storedPosts,
            'linkedin_profile' => $profileData,
            'linkedin_synced_at' => now(),
        ]);

        $mix = $this->audienceMix->fromEngagers($engagers);

        if ($mix === [] && is_string($profileData['country_code'] ?? null) && $profileData['country_code'] !== '') {
            $mix = ['geo' => [$profileData['country_code'] => 100]];
        }

        $this->syncAudienceSnapshot(
            $profile->fresh() ?? $profile,
            is_int($profileData['follower_count'] ?? null) ? $profileData['follower_count'] : null,
            is_int($profileData['connections_count'] ?? null) ? $profileData['connections_count'] : null,
            $mix !== [] ? $mix : null,
        );
    }

    private function storeLinkedInPhoto(CreatorProfile $profile, ?string $pictureUrl): ?string
    {
        if ($pictureUrl === null || $pictureUrl === '') {
            return null;
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders(['User-Agent' => 'NaanoLinkedInSync/1.0'])
                ->get($pictureUrl);

            if (! $response->successful()) {
                return null;
            }

            $body = $response->body();

            if ($body === '') {
                return null;
            }

            $extension = match (true) {
                str_contains((string) $response->header('Content-Type'), 'png') => 'png',
                str_contains((string) $response->header('Content-Type'), 'webp') => 'webp',
                default => 'jpg',
            };

            $path = 'creators/'.$profile->id.'/linkedin-photo.'.$extension;

            Storage::disk('public')->put($path, $body);

            return $path;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, array<string, int>>|null  $audienceMix
     */
    private function syncAudienceSnapshot(
        CreatorProfile $profile,
        ?int $followersCount,
        ?int $connectionsCount,
        ?array $audienceMix = null,
    ): void {
        if ($followersCount === null && $connectionsCount === null && ($audienceMix === null || $audienceMix === [])) {
            return;
        }

        $snapshot = $profile->audienceProfiles()
            ->where('network', 'linkedin')
            ->orderByDesc('captured_at')
            ->orderByDesc('id')
            ->first();

        $attributes = [
            'captured_at' => now(),
        ];

        if ($followersCount !== null) {
            $attributes['followers_count'] = $followersCount;
        }

        if ($connectionsCount !== null) {
            $attributes['connections_count'] = $connectionsCount;
        }

        if ($audienceMix !== null && $audienceMix !== []) {
            $attributes['audience_mix'] = $audienceMix;
        }

        if ($snapshot instanceof CreatorAudienceProfile) {
            $snapshot->update($attributes);

            return;
        }

        $profile->audienceProfiles()->create([
            'network' => 'linkedin',
            'followers_count' => $followersCount,
            'connections_count' => $connectionsCount,
            'audience_mix' => $audienceMix ?? [],
            'captured_at' => now(),
        ]);
    }

    private function stripVerifyCode(string $headline, ?string $code): string
    {
        if ($code === null || $code === '') {
            return trim($headline);
        }

        return trim(str_ireplace($code, '', $headline));
    }
}
