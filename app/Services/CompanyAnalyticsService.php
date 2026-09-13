<?php

namespace App\Services;

use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Models\Campaign;
use App\Models\Collaboration;
use App\Models\Company;
use App\Models\Lead;
use App\Models\Post;
use App\Models\PostMetric;
use App\Models\TrackingClick;
use App\Support\PublicDisk;
use Illuminate\Support\Carbon;

class CompanyAnalyticsService
{
    public function __construct(
        private PostMetricIngestService $metrics,
        private CompanyLeadService $leads,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(Company $company): array
    {
        $postIds = $this->postIds($company);
        $rollup = $this->metrics->rollup(
            PostMetric::query()->whereIn('post_id', $postIds)->get(),
        );
        $leads = Lead::query()->where('company_id', $company->id)->get();

        return [
            ...$rollup,
            'leads_count' => $leads->count(),
            'pipeline_cents' => $this->pipelineCents($leads),
            'spend_cents' => $this->spendCents($company),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function campaign(Company $company, Campaign $campaign): array
    {
        $this->ensureCampaignOwned($company, $campaign);

        $posts = $campaign->posts()->with(['metrics', 'collaboration.creatorProfile'])->get();
        $rollup = $this->metrics->rollup($posts->map->metrics->filter());
        $leads = $campaign->leads()->orderByDesc('id')->get();

        return [
            ...$rollup,
            'leads_count' => $leads->count(),
            'pipeline_cents' => $this->pipelineCents($leads),
            'spend_cents' => $this->spendCents($company, $campaign->id),
            'daily_clicks' => $this->dailyClicks($posts->pluck('id')->all()),
            'posts' => $posts->map(fn (Post $post): array => [
                'id' => $post->id,
                'status' => $post->status->value,
                'published_url' => $post->published_url,
                'creator' => [
                    'id' => $post->collaboration->creatorProfile->id,
                    'display_name' => $post->collaboration->creatorProfile->display_name,
                    'photo_url' => PublicDisk::url($post->collaboration->creatorProfile->photo_path),
                ],
                'metrics' => $this->metrics->snapshot($post->metrics),
            ])->values()->all(),
            'leads' => $leads->map(fn (Lead $lead): array => $this->leads->payload($lead))->values()->all(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function creators(Company $company, Campaign $campaign): array
    {
        $this->ensureCampaignOwned($company, $campaign);

        return array_values($campaign->collaborations()
            ->with(['creatorProfile', 'posts.metrics'])
            ->get()
            ->map(function (Collaboration $collaboration) use ($campaign): array {
                $posts = $collaboration->posts;
                $rollup = $this->metrics->rollup($posts->map->metrics->filter());
                $postIds = $posts->pluck('id')->all();
                $leads = $postIds === []
                    ? collect()
                    : Lead::query()
                        ->where('campaign_id', $campaign->id)
                        ->whereIn('post_id', $postIds)
                        ->get();

                return [
                    'creator' => [
                        'id' => $collaboration->creatorProfile->id,
                        'display_name' => $collaboration->creatorProfile->display_name,
                        'photo_url' => PublicDisk::url($collaboration->creatorProfile->photo_path),
                    ],
                    'collaboration_id' => $collaboration->id,
                    'status' => $collaboration->status->value,
                    ...$rollup,
                    'leads_count' => $leads->count(),
                    'pipeline_cents' => $this->pipelineCents($leads),
                ];
            })
            ->all());
    }

    /**
     * @return array<string, mixed>
     */
    public function post(Company $company, Post $post): array
    {
        $post->loadMissing('collaboration.campaign', 'metrics');
        $this->ensureCampaignOwned($company, $post->collaboration->campaign);

        return $this->metrics->snapshot($post->metrics);
    }

    /**
     * @return array<string, mixed>
     */
    public function report(Company $company, Campaign $campaign): array
    {
        return [
            ...$this->campaign($company, $campaign),
            'creators' => $this->creators($company, $campaign),
        ];
    }

    /**
     * @return list<int>
     */
    private function postIds(Company $company): array
    {
        return Post::query()
            ->whereHas('collaboration.campaign', fn ($query) => $query->where('company_id', $company->id))
            ->pluck('id')
            ->all();
    }

    /**
     * @param  iterable<int, Lead>  $leads
     */
    private function pipelineCents(iterable $leads): int
    {
        $total = 0;

        foreach ($leads as $lead) {
            $value = $lead->payload['pipeline_cents'] ?? null;

            if (is_numeric($value)) {
                $total += (int) $value;
            }
        }

        return $total;
    }

    private function spendCents(Company $company, ?int $campaignId = null): int
    {
        $wallet = $company->wallet;

        if ($wallet === null) {
            return 0;
        }

        $query = $wallet->transactions()
            ->where('type', WalletTransactionType::Capture)
            ->where('status', WalletTransactionStatus::Posted);

        if ($campaignId !== null) {
            $query->where('campaign_id', $campaignId);
        }

        return (int) $query->sum('amount_cents');
    }

    /**
     * @param  list<int>  $postIds
     * @return list<array{day: string, clicks: int}>
     */
    private function dailyClicks(array $postIds): array
    {
        $days = [];

        for ($offset = 11; $offset >= 0; $offset--) {
            $day = Carbon::now()->subDays($offset)->toDateString();
            $days[$day] = 0;
        }

        if ($postIds !== []) {
            TrackingClick::query()
                ->whereIn('post_id', $postIds)
                ->where('occurred_at', '>=', Carbon::now()->subDays(11)->startOfDay())
                ->get()
                ->each(function (TrackingClick $click) use (&$days): void {
                    $day = $click->occurred_at?->toDateString();

                    if (is_string($day) && array_key_exists($day, $days)) {
                        $days[$day]++;
                    }
                });
        }

        return array_values(array_map(
            fn (string $day, int $clicks): array => ['day' => $day, 'clicks' => $clicks],
            array_keys($days),
            $days,
        ));
    }

    private function ensureCampaignOwned(Company $company, Campaign $campaign): void
    {
        if ($campaign->company_id !== $company->id) {
            abort(404);
        }
    }
}
