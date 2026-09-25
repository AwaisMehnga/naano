<?php

namespace App\Services\LinkedIn;

use App\Models\CreatorProfile;
use App\Support\PublicDisk;
use Illuminate\Support\Carbon;

class LinkedInProfilePresenter
{
    /**
     * @return array<string, mixed>
     */
    public function present(CreatorProfile $profile): array
    {
        $linkedinProfile = is_array($profile->linkedin_profile) ? $profile->linkedin_profile : null;
        $posts = is_array($profile->linkedin_posts) ? $profile->linkedin_posts : [];

        $name = trim(implode(' ', array_filter([
            $linkedinProfile['first_name'] ?? null,
            $linkedinProfile['last_name'] ?? null,
        ])));

        if ($name === '') {
            $name = $profile->display_name ?? $profile->user?->name;
        }

        $pictureUrl = $linkedinProfile['picture_url'] ?? null;
        $storedPhoto = PublicDisk::url($profile->photo_path);

        return [
            'verified' => $profile->isLinkedInVerified(),
            'verified_at' => $profile->linkedin_verified_at?->toIso8601String(),
            'synced_at' => $profile->linkedin_synced_at?->toIso8601String(),
            'verify_code' => $profile->isLinkedInVerified() ? null : $profile->linkedin_verify_code,
            'linkedin_url' => $profile->linkedin_url,
            'header' => [
                'name' => $name,
                'headline' => $linkedinProfile['headline'] ?? $profile->headline,
                'job_title' => $linkedinProfile['job_title'] ?? null,
                'location' => $linkedinProfile['location'] ?? null,
                'company' => $linkedinProfile['current_company'] ?? null,
                'picture_url' => $storedPhoto ?? (is_string($pictureUrl) ? $pictureUrl : null),
                'captured_at' => $linkedinProfile['captured_at'] ?? $profile->linkedin_synced_at?->toIso8601String(),
            ],
            'stats' => $this->stats($profile, $linkedinProfile, $posts),
            'engagement_series' => $this->engagementSeries($posts),
            'top_posts' => $this->topPosts($posts),
            'recent_posts' => $this->recentPosts($posts),
            'engagers' => $linkedinProfile['engagers'] ?? null,
            'background' => [
                'positions' => $linkedinProfile['positions'] ?? [],
                'educations' => $linkedinProfile['educations'] ?? [],
                'skills' => $linkedinProfile['skills'] ?? [],
                'summary' => $linkedinProfile['summary'] ?? $profile->bio,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $linkedinProfile
     * @param  list<array<string, mixed>>  $posts
     * @return array<string, mixed>
     */
    private function stats(CreatorProfile $profile, ?array $linkedinProfile, array $posts): array
    {
        $likes = array_map(fn (array $post): int => (int) ($post['likes'] ?? 0), $posts);
        $comments = array_map(fn (array $post): int => (int) ($post['comments'] ?? 0), $posts);
        $count = count($posts);

        $lastPosted = null;

        foreach ($posts as $post) {
            $postedAt = $post['posted_at'] ?? null;

            if (! is_string($postedAt) || $postedAt === '') {
                continue;
            }

            try {
                $carbon = Carbon::parse($postedAt);

                if ($lastPosted === null || $carbon->gt($lastPosted)) {
                    $lastPosted = $carbon;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return [
            'followers' => $linkedinProfile['follower_count'] ?? null,
            'connections' => $linkedinProfile['connections_count'] ?? null,
            'posts_count' => $count,
            'last_posted_at' => $lastPosted?->toIso8601String(),
            'avg_reactions' => $count > 0 ? (int) round(array_sum($likes) / $count) : null,
            'avg_comments' => $count > 0 ? round(array_sum($comments) / $count, 1) : null,
            'asking_rate_cents' => $profile->price_cents,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $posts
     * @return list<array{label: string, reactions: int, comments: int, posted_at: string|null}>
     */
    private function engagementSeries(array $posts): array
    {
        $sorted = $this->sortedByDate($posts);
        $series = [];

        foreach (array_slice($sorted, -24) as $index => $post) {
            $label = '#'.($index + 1);
            $postedAt = $post['posted_at'] ?? null;

            if (is_string($postedAt) && $postedAt !== '') {
                try {
                    $label = Carbon::parse($postedAt)->format('M j');
                } catch (\Throwable) {
                    // keep ordinal label
                }
            }

            $series[] = [
                'label' => $label,
                'reactions' => (int) ($post['likes'] ?? 0),
                'comments' => (int) ($post['comments'] ?? 0),
                'posted_at' => is_string($postedAt) ? $postedAt : null,
            ];
        }

        return $series;
    }

    /**
     * @param  list<array<string, mixed>>  $posts
     * @return list<array<string, mixed>>
     */
    private function topPosts(array $posts): array
    {
        $sorted = $posts;
        usort($sorted, function (array $a, array $b): int {
            $scoreA = ((int) ($a['likes'] ?? 0)) + ((int) ($a['comments'] ?? 0));
            $scoreB = ((int) ($b['likes'] ?? 0)) + ((int) ($b['comments'] ?? 0));

            return $scoreB <=> $scoreA;
        });

        return array_values(array_map(
            fn (array $post): array => $this->postPayload($post),
            array_slice($sorted, 0, 6),
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $posts
     * @return list<array<string, mixed>>
     */
    private function recentPosts(array $posts): array
    {
        $sorted = array_reverse($this->sortedByDate($posts));

        return array_values(array_map(
            fn (array $post): array => $this->postPayload($post),
            array_slice($sorted, 0, 20),
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $posts
     * @return list<array<string, mixed>>
     */
    private function sortedByDate(array $posts): array
    {
        $sorted = $posts;
        usort($sorted, function (array $a, array $b): int {
            return strcmp((string) ($a['posted_at'] ?? ''), (string) ($b['posted_at'] ?? ''));
        });

        return $sorted;
    }

    /**
     * @param  array<string, mixed>  $post
     * @return array<string, mixed>
     */
    private function postPayload(array $post): array
    {
        return [
            'id' => $post['id'] ?? null,
            'linkedin_url' => $post['linkedin_url'] ?? null,
            'content' => $post['content'] ?? null,
            'posted_at' => $post['posted_at'] ?? null,
            'likes' => (int) ($post['likes'] ?? 0),
            'comments' => (int) ($post['comments'] ?? 0),
            'shares' => (int) ($post['shares'] ?? 0),
        ];
    }
}
