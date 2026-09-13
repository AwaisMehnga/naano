<?php

namespace App\Services;

use App\Enums\PostStatus;
use App\Models\Collaboration;
use App\Models\Post;
use App\Models\PostMetric;
use App\Models\User;

class CreatorAnalyticsService
{
    public function __construct(
        private PostMetricIngestService $metrics,
        private CreatorWalletService $wallets,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(User $user): array
    {
        $postIds = $this->postIds($user);
        $rollup = $this->metrics->rollup(
            PostMetric::query()->whereIn('post_id', $postIds)->get(),
        );

        $profile = $user->creatorProfile;

        if ($profile === null) {
            abort(404);
        }

        $followers = $profile->audienceProfiles()
            ->orderByDesc('captured_at')
            ->orderByDesc('id')
            ->value('followers_count');

        return [
            ...$rollup,
            'engagement' => (int) $rollup['likes'] + (int) $rollup['comments'],
            'public_posts_count' => Post::query()
                ->whereIn('id', $postIds)
                ->where('status', PostStatus::Published)
                ->count(),
            'followers_count' => $followers === null ? null : (int) $followers,
            'earnings_cents' => $this->wallets->capturedCents($profile),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(Collaboration $collaboration): array
    {
        $collaboration->loadMissing('posts.metrics');

        return $this->metrics->rollup($collaboration->posts->map->metrics->filter());
    }

    /**
     * @return array<string, mixed>
     */
    public function collaboration(User $user, Collaboration $collaboration): array
    {
        $this->ensureOwned($user, $collaboration);

        $posts = $collaboration->posts()->with('metrics')->orderBy('id')->get();
        $rollup = $this->metrics->rollup($posts->map->metrics->filter());

        return [
            ...$rollup,
            'posts' => $posts->map(fn (Post $post): array => [
                'id' => $post->id,
                'status' => $post->status->value,
                'published_url' => $post->published_url,
                'metrics' => $this->metrics->snapshot($post->metrics),
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function post(User $user, Post $post): array
    {
        $post->loadMissing('collaboration', 'metrics');
        $this->ensureOwned($user, $post->collaboration);

        return $this->metrics->snapshot($post->metrics);
    }

    /**
     * @return list<int>
     */
    private function postIds(User $user): array
    {
        $profile = $user->creatorProfile;

        if ($profile === null) {
            abort(404);
        }

        return Post::query()
            ->whereHas('collaboration', fn ($query) => $query->where('creator_profile_id', $profile->id))
            ->pluck('id')
            ->all();
    }

    private function ensureOwned(User $user, Collaboration $collaboration): void
    {
        $profile = $user->creatorProfile;

        if ($profile === null || $collaboration->creator_profile_id !== $profile->id) {
            abort(404);
        }
    }
}
