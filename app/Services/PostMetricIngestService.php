<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostMetric;

class PostMetricIngestService
{
    public function __construct(private TrackingLinkService $tracking) {}

    /**
     * @param  array{impressions?: int, likes?: int, comments?: int}  $data
     * @return array<string, mixed>
     */
    public function ingest(Post $post, array $data): array
    {
        $metrics = $this->tracking->metricsFor($post->id);

        if (array_key_exists('impressions', $data)) {
            $metrics->impressions = max(0, (int) $data['impressions']);
        }

        if (array_key_exists('likes', $data)) {
            $metrics->likes = max(0, (int) $data['likes']);
        }

        if (array_key_exists('comments', $data)) {
            $metrics->comments = max(0, (int) $data['comments']);
        }

        $metrics->captured_at = now();
        $metrics->save();

        return $this->snapshot($metrics->fresh());
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(?PostMetric $metrics): array
    {
        $impressions = (int) ($metrics?->impressions ?? 0);
        $clicks = (int) ($metrics?->clicks ?? 0);
        $uniqueClicks = (int) ($metrics?->unique_clicks ?? 0);

        return [
            'impressions' => $impressions,
            'likes' => (int) ($metrics?->likes ?? 0),
            'comments' => (int) ($metrics?->comments ?? 0),
            'clicks' => $clicks,
            'unique_clicks' => $uniqueClicks,
            'qualified_clicks' => (int) ($metrics?->qualified_clicks ?? 0),
            'leads_count' => (int) ($metrics?->leads_count ?? 0),
            'cpm_cents' => $metrics?->cpm_cents,
            'ctr' => $this->ctr($uniqueClicks, $impressions),
            'ctr_total' => $this->ctr($clicks, $impressions),
            'captured_at' => $metrics?->captured_at?->toIso8601String(),
        ];
    }

    /**
     * @param  list<PostMetric>  $rows
     * @return array<string, mixed>
     */
    public function rollup(iterable $rows): array
    {
        $impressions = 0;
        $likes = 0;
        $comments = 0;
        $clicks = 0;
        $uniqueClicks = 0;
        $qualified = 0;
        $leads = 0;

        foreach ($rows as $metrics) {
            $impressions += (int) $metrics->impressions;
            $likes += (int) $metrics->likes;
            $comments += (int) $metrics->comments;
            $clicks += (int) $metrics->clicks;
            $uniqueClicks += (int) $metrics->unique_clicks;
            $qualified += (int) $metrics->qualified_clicks;
            $leads += (int) $metrics->leads_count;
        }

        return [
            'impressions' => $impressions,
            'likes' => $likes,
            'comments' => $comments,
            'clicks' => $clicks,
            'unique_clicks' => $uniqueClicks,
            'qualified_clicks' => $qualified,
            'leads_count' => $leads,
            'ctr' => $this->ctr($uniqueClicks, $impressions),
            'ctr_total' => $this->ctr($clicks, $impressions),
        ];
    }

    public function ctr(int $clicks, int $impressions): ?float
    {
        if ($impressions < 1) {
            return null;
        }

        return round($clicks / $impressions, 4);
    }
}
