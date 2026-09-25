<?php

namespace App\Services;

use App\Enums\CollaborationStatus;
use App\Enums\PostStatus;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Models\Collaboration;
use App\Models\CreatorProfile;
use App\Models\Post;
use App\Models\PostMetric;
use App\Models\TrackingClick;
use App\Models\User;
use App\Models\WalletTransaction;
use Carbon\CarbonInterface;

class CreatorAnalyticsService
{
    public function __construct(
        private PostMetricIngestService $metrics,
        private CreatorWalletService $wallets,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(User $user, ?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        [$from, $to] = $this->range($from, $to);

        $profile = $user->creatorProfile;

        if ($profile === null) {
            abort(404);
        }

        $postIds = $this->postIds($user);
        $rollup = $this->metrics->rollup(
            PostMetric::query()->whereIn('post_id', $postIds)->get(),
        );

        $audience = $profile->audienceProfiles()
            ->orderByDesc('captured_at')
            ->orderByDesc('id')
            ->first();

        $followers = $audience?->followers_count;
        $connections = $audience?->connections_count;
        $mix = is_array($audience?->audience_mix) ? $audience->audience_mix : [];

        $series = $this->series($postIds, $profile, $from, $to);
        $previousFrom = $from->copy()->subDays($from->diffInDays($to) + 1)->startOfDay();
        $previousTo = $from->copy()->subDay()->endOfDay();
        $previousSeries = $this->series($postIds, $profile, $previousFrom, $previousTo);

        $periodClicks = array_sum(array_column($series, 'clicks'));
        $previousClicks = array_sum(array_column($previousSeries, 'clicks'));
        $periodEarnings = array_sum(array_column($series, 'earnings_cents'));
        $previousEarnings = array_sum(array_column($previousSeries, 'earnings_cents'));

        return [
            ...$rollup,
            'engagement' => (int) $rollup['likes'] + (int) $rollup['comments'],
            'public_posts_count' => Post::query()
                ->whereIn('id', $postIds)
                ->where('status', PostStatus::Published)
                ->count(),
            'followers_count' => $followers === null ? null : (int) $followers,
            'connections_count' => $connections === null ? null : (int) $connections,
            'earnings_cents' => $this->wallets->capturedCents($profile),
            'active_deals_count' => Collaboration::query()
                ->where('creator_profile_id', $profile->id)
                ->whereIn('status', [CollaborationStatus::Booked, CollaborationStatus::Selected])
                ->count(),
            'completed_deals_count' => Collaboration::query()
                ->where('creator_profile_id', $profile->id)
                ->where('status', CollaborationStatus::Completed)
                ->count(),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'series' => $series,
            'comparison' => $this->comparisonSeries($series, $previousSeries),
            'audience_segments' => $this->audienceSegments($mix),
            'growth' => [
                'clicks' => $this->growthPercent($periodClicks, $previousClicks),
                'earnings' => $this->growthPercent($periodEarnings, $previousEarnings),
            ],
            'period' => [
                'clicks' => $periodClicks,
                'unique_clicks' => array_sum(array_column($series, 'unique_clicks')),
                'earnings_cents' => $periodEarnings,
            ],
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
     * @param  list<int>  $postIds
     * @return list<array{day: string, clicks: int, unique_clicks: int, earnings_cents: int}>
     */
    private function series(array $postIds, CreatorProfile $profile, CarbonInterface $from, CarbonInterface $to): array
    {
        $clickRows = collect();

        if ($postIds !== []) {
            $clickRows = TrackingClick::query()
                ->toBase()
                ->whereIn('post_id', $postIds)
                ->whereBetween('occurred_at', [$from, $to])
                ->selectRaw('date(occurred_at) as day')
                ->selectRaw('count(*) as clicks')
                ->selectRaw('count(distinct visitor_key) as unique_clicks')
                ->groupByRaw('date(occurred_at)')
                ->get()
                ->keyBy('day');
        }

        $earningsRows = WalletTransaction::query()
            ->toBase()
            ->whereIn('collaboration_id', $this->collaborationIds($profile))
            ->where('type', WalletTransactionType::Capture)
            ->where('status', WalletTransactionStatus::Posted)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('date(created_at) as day')
            ->selectRaw('coalesce(sum(amount_cents), 0) as earnings_cents')
            ->groupByRaw('date(created_at)')
            ->get()
            ->keyBy('day');

        $series = [];
        $cursor = $from->toMutable()->startOfDay();
        $end = $to->toMutable()->startOfDay();

        while ($cursor->lte($end)) {
            $day = $cursor->toDateString();
            $clicks = $clickRows->get($day);
            $earnings = $earningsRows->get($day);

            $series[] = [
                'day' => $day,
                'clicks' => (int) ($clicks->clicks ?? 0),
                'unique_clicks' => (int) ($clicks->unique_clicks ?? 0),
                'earnings_cents' => (int) ($earnings->earnings_cents ?? 0),
            ];

            $cursor->addDay();
        }

        return $series;
    }

    /**
     * @param  list<array{day: string, clicks: int, unique_clicks: int, earnings_cents: int}>  $current
     * @param  list<array{day: string, clicks: int, unique_clicks: int, earnings_cents: int}>  $previous
     * @return list<array{day: string, current: int, previous: int}>
     */
    private function comparisonSeries(array $current, array $previous): array
    {
        $previousValues = array_column($previous, 'clicks');

        return array_values(array_map(
            function (array $point, int $index) use ($previousValues): array {
                return [
                    'day' => $point['day'],
                    'current' => $point['clicks'],
                    'previous' => (int) ($previousValues[$index] ?? 0),
                ];
            },
            $current,
            array_keys($current),
        ));
    }

    /**
     * @param  array<string, mixed>  $mix
     * @return list<array{label: string, value: int}>
     */
    private function audienceSegments(array $mix): array
    {
        $source = null;

        foreach (['seniority', 'job_title', 'geo'] as $key) {
            if (isset($mix[$key]) && is_array($mix[$key]) && $mix[$key] !== []) {
                $source = $mix[$key];
                break;
            }
        }

        if ($source === null) {
            return [];
        }

        arsort($source);

        $segments = [];

        foreach (array_slice($source, 0, 4, true) as $label => $value) {
            if (! is_string($label) || ! is_numeric($value)) {
                continue;
            }

            $segments[] = [
                'label' => $label,
                'value' => max(0, min(100, (int) $value)),
            ];
        }

        return $segments;
    }

    private function growthPercent(int $current, int $previous): ?float
    {
        if ($previous < 1) {
            return $current > 0 ? 100.0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * @return list<int>
     */
    private function collaborationIds(CreatorProfile $profile): array
    {
        return Collaboration::query()
            ->where('creator_profile_id', $profile->id)
            ->pluck('id')
            ->all();
    }

    /**
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    private function range(?CarbonInterface $from, ?CarbonInterface $to): array
    {
        $to ??= now()->endOfDay();
        $from ??= $to->copy()->subDays(29)->startOfDay();

        return [$from->copy()->startOfDay(), $to->copy()->endOfDay()];
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
