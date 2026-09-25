<?php

namespace App\Services\LinkedIn;

use App\Jobs\SyncLinkedInPostsJob;
use App\Models\CreatorAudienceProfile;
use App\Models\CreatorProfile;
use App\Services\Apify\ApifyClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class LinkedInSyncService
{
    public function __construct(
        private ApifyClient $apify,
        private LinkedInProfileNormalizer $profileNormalizer,
        private LinkedInPostsNormalizer $postsNormalizer,
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

        $this->syncAudienceFollowers($profile, $normalized['follower_count'] ?? null);
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

        $this->syncAudienceFollowers($profile, $normalized['follower_count'] ?? null);

        SyncLinkedInPostsJob::dispatch($profile->id);

        return app(LinkedInProfilePresenter::class)->present($profile->fresh() ?? $profile);
    }

    public function syncPosts(CreatorProfile $profile): void
    {
        if (! is_string($profile->linkedin_url) || $profile->linkedin_url === '') {
            return;
        }

        $actor = (string) config('services.apify.posts_actor');
        $maxPosts = max(1, (int) config('services.apify.max_posts', 50));
        $scrapeComments = (bool) config('services.apify.scrape_comments', false);
        $maxComments = max(0, (int) config('services.apify.max_comments', 20));

        $input = [
            'targetUrls' => [$profile->linkedin_url],
            'maxPosts' => $maxPosts,
        ];

        if ($scrapeComments && $maxComments > 0) {
            $input['scrapeComments'] = true;
            $input['maxComments'] = $maxComments;
        }

        $items = $this->apify->runActor($actor, $input);
        $posts = $this->postsNormalizer->normalize($items, $maxPosts);
        $engagers = $this->postsNormalizer->engagersSummary($posts);

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

    private function syncAudienceFollowers(CreatorProfile $profile, ?int $followersCount): void
    {
        if ($followersCount === null) {
            return;
        }

        $snapshot = $profile->audienceProfiles()
            ->where('network', 'linkedin')
            ->orderByDesc('captured_at')
            ->orderByDesc('id')
            ->first();

        if ($snapshot instanceof CreatorAudienceProfile) {
            $snapshot->update([
                'followers_count' => $followersCount,
                'captured_at' => now(),
            ]);

            return;
        }

        $profile->audienceProfiles()->create([
            'network' => 'linkedin',
            'followers_count' => $followersCount,
            'audience_mix' => [],
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
