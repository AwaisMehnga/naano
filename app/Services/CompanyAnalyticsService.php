<?php

namespace App\Services;

use App\Enums\CampaignStatus;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Models\Campaign;
use App\Models\Collaboration;
use App\Models\Company;
use App\Models\Lead;
use App\Models\Media;
use App\Models\Post;
use App\Models\PostMetric;
use App\Models\TrackingClick;
use App\Support\PublicDisk;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CompanyAnalyticsService
{
    public function __construct(
        private PostMetricIngestService $metrics,
        private CompanyLeadService $leads,
        private MediaService $media,
        private CompanyWalletService $wallets,
        private CompanyCollaborationService $collaborations,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(Company $company, ?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        [$from, $to] = $this->range($from, $to);
        $rollup = $this->workspaceRollup($company);
        $series = $this->workspaceSeries($company, $from, $to);

        $previousFrom = $from->copy()->subDays($from->diffInDays($to) + 1)->startOfDay();
        $previousTo = $from->copy()->subDay()->endOfDay();
        $previousSeries = $this->workspaceSeries($company, $previousFrom, $previousTo);

        $periodClicks = (int) array_sum(array_column($series, 'clicks'));
        $periodUnique = (int) array_sum(array_column($series, 'unique_clicks'));
        $periodLeads = (int) array_sum(array_column($series, 'leads'));
        $periodSpend = (int) array_sum(array_column($series, 'spend_cents'));
        $periodPipeline = $this->pipelineCentsForCompany($company, $from, $to);

        $previousClicks = (int) array_sum(array_column($previousSeries, 'clicks'));
        $previousLeads = (int) array_sum(array_column($previousSeries, 'leads'));
        $previousSpend = (int) array_sum(array_column($previousSeries, 'spend_cents'));

        $statusCounts = Collaboration::query()
            ->whereHas('campaign', fn ($query) => $query->where('company_id', $company->id))
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        $wallet = $this->wallets->show($company);
        $campaignsCount = $company->campaigns()->count();
        $liveCampaignsCount = $company->campaigns()
            ->where('status', CampaignStatus::Active)
            ->count();

        $cplCents = $periodLeads > 0
            ? (int) round($periodSpend / $periodLeads)
            : null;

        return [
            ...$rollup,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'leads_count' => (int) Lead::query()
                ->where('company_id', $company->id)
                ->count(),
            'pipeline_cents' => $this->pipelineCentsForCompany($company),
            'spend_cents' => $this->spendCents($company),
            'series' => $series,
            'comparison' => $this->comparisonSeries($series, $previousSeries),
            'period' => [
                'clicks' => $periodClicks,
                'unique_clicks' => $periodUnique,
                'leads_count' => $periodLeads,
                'spend_cents' => $periodSpend,
                'pipeline_cents' => $periodPipeline,
            ],
            'growth' => [
                'clicks' => $this->growthPercent($periodClicks, $previousClicks),
                'leads' => $this->growthPercent($periodLeads, $previousLeads),
                'spend' => $this->growthPercent($periodSpend, $previousSpend),
            ],
            'campaigns_count' => $campaignsCount,
            'live_campaigns_count' => $liveCampaignsCount,
            'collab_counts' => $this->collaborations->countsFromStatuses($statusCounts),
            'wallet' => [
                'available_cents' => $wallet['available_cents'],
                'held_cents' => $wallet['held_cents'],
            ],
            'cpl_cents' => $cplCents,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function campaign(Company $company, Campaign $campaign): array
    {
        $this->ensureCampaignOwned($company, $campaign);

        $posts = $campaign->posts()->with(['metrics', 'media', 'collaboration.creatorProfile'])->get();
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
                'collaboration_id' => $post->collaboration_id,
                'status' => $post->status->value,
                'body' => $post->body === null ? null : Str::limit($post->body, 180),
                'published_url' => $post->published_url,
                'submitted_at' => $post->submitted_at?->toIso8601String(),
                'media' => $post->media
                    ->map(fn (Media $item): array => $this->media->payload($item))
                    ->values()
                    ->all(),
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
     * @return array<string, mixed>
     */
    private function workspaceRollup(Company $company): array
    {
        $row = PostMetric::query()
            ->toBase()
            ->join('posts', 'posts.id', '=', 'post_metrics.post_id')
            ->join('collaborations', 'collaborations.id', '=', 'posts.collaboration_id')
            ->join('campaigns', 'campaigns.id', '=', 'collaborations.campaign_id')
            ->where('campaigns.company_id', $company->id)
            ->whereNull('post_metrics.deleted_at')
            ->whereNull('posts.deleted_at')
            ->whereNull('collaborations.deleted_at')
            ->whereNull('campaigns.deleted_at')
            ->selectRaw('coalesce(sum(post_metrics.impressions), 0) as impressions')
            ->selectRaw('coalesce(sum(post_metrics.likes), 0) as likes')
            ->selectRaw('coalesce(sum(post_metrics.comments), 0) as comments')
            ->selectRaw('coalesce(sum(post_metrics.clicks), 0) as clicks')
            ->selectRaw('coalesce(sum(post_metrics.unique_clicks), 0) as unique_clicks')
            ->selectRaw('coalesce(sum(post_metrics.qualified_clicks), 0) as qualified_clicks')
            ->first();

        $impressions = (int) ($row->impressions ?? 0);
        $clicks = (int) ($row->clicks ?? 0);
        $uniqueClicks = (int) ($row->unique_clicks ?? 0);

        return [
            'impressions' => $impressions,
            'likes' => (int) ($row->likes ?? 0),
            'comments' => (int) ($row->comments ?? 0),
            'clicks' => $clicks,
            'unique_clicks' => $uniqueClicks,
            'qualified_clicks' => (int) ($row->qualified_clicks ?? 0),
            'ctr' => $this->metrics->ctr($uniqueClicks, $impressions),
            'ctr_total' => $this->metrics->ctr($clicks, $impressions),
        ];
    }

    /**
     * @return list<array{day: string, clicks: int, unique_clicks: int, leads: int, spend_cents: int}>
     */
    private function workspaceSeries(Company $company, CarbonInterface $from, CarbonInterface $to): array
    {
        $clickRows = $this->clickSeriesQuery($company)
            ->whereBetween('tracking_clicks.occurred_at', [$from, $to])
            ->selectRaw('date(tracking_clicks.occurred_at) as day')
            ->selectRaw('count(*) as clicks')
            ->selectRaw('count(distinct tracking_clicks.visitor_key) as unique_clicks')
            ->groupByRaw('date(tracking_clicks.occurred_at)')
            ->get()
            ->keyBy('day');

        $leadRows = Lead::query()
            ->toBase()
            ->where('company_id', $company->id)
            ->whereBetween('occurred_at', [$from, $to])
            ->selectRaw('date(occurred_at) as day')
            ->selectRaw('count(*) as leads')
            ->groupByRaw('date(occurred_at)')
            ->get()
            ->keyBy('day');

        $spendRows = $this->spendSeriesQuery($company, $from, $to);

        $series = [];
        $cursor = $from->toMutable()->startOfDay();
        $end = $to->toMutable()->startOfDay();

        while ($cursor->lte($end)) {
            $day = $cursor->toDateString();
            $clicks = $clickRows->get($day);
            $leads = $leadRows->get($day);
            $spend = $spendRows->get($day);

            $series[] = [
                'day' => $day,
                'clicks' => (int) ($clicks->clicks ?? 0),
                'unique_clicks' => (int) ($clicks->unique_clicks ?? 0),
                'leads' => (int) ($leads->leads ?? 0),
                'spend_cents' => (int) ($spend->spend_cents ?? 0),
            ];

            $cursor->addDay();
        }

        return $series;
    }

    private function clickSeriesQuery(Company $company): Builder
    {
        return TrackingClick::query()
            ->toBase()
            ->join('posts', 'posts.id', '=', 'tracking_clicks.post_id')
            ->join('collaborations', 'collaborations.id', '=', 'posts.collaboration_id')
            ->join('campaigns', 'campaigns.id', '=', 'collaborations.campaign_id')
            ->where('campaigns.company_id', $company->id)
            ->whereNull('posts.deleted_at')
            ->whereNull('collaborations.deleted_at')
            ->whereNull('campaigns.deleted_at');
    }

    /**
     * @return Collection<string, object>
     */
    private function spendSeriesQuery(Company $company, CarbonInterface $from, CarbonInterface $to): Collection
    {
        $wallet = $company->wallet;

        if ($wallet === null) {
            return collect();
        }

        return $wallet->transactions()
            ->toBase()
            ->where('type', WalletTransactionType::Capture)
            ->where('status', WalletTransactionStatus::Posted)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('date(created_at) as day')
            ->selectRaw('coalesce(sum(amount_cents), 0) as spend_cents')
            ->groupByRaw('date(created_at)')
            ->get()
            ->keyBy('day');
    }

    private function pipelineCentsForCompany(
        Company $company,
        ?CarbonInterface $from = null,
        ?CarbonInterface $to = null,
    ): int {
        $query = Lead::query()->where('company_id', $company->id)->toBase();

        if ($from !== null && $to !== null) {
            $query->whereBetween('occurred_at', [$from, $to]);
        }

        $pipelineCents = $query->getGrammar()->wrap('payload->pipeline_cents');

        return (int) $query
            ->selectRaw("coalesce(sum(cast({$pipelineCents} as bigint)), 0) as aggregate")
            ->value('aggregate');
    }

    /**
     * @param  list<array{day: string, clicks: int, unique_clicks: int, leads: int, spend_cents: int}>  $current
     * @param  list<array{day: string, clicks: int, unique_clicks: int, leads: int, spend_cents: int}>  $previous
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

    private function growthPercent(int $current, int $previous): ?float
    {
        if ($previous < 1) {
            return $current > 0 ? 100.0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
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
        $from = now()->subDays(11)->startOfDay();
        $days = [];

        for ($offset = 11; $offset >= 0; $offset--) {
            $day = now()->subDays($offset)->toDateString();
            $days[$day] = 0;
        }

        if ($postIds !== []) {
            TrackingClick::query()
                ->toBase()
                ->whereIn('post_id', $postIds)
                ->where('occurred_at', '>=', $from)
                ->selectRaw('date(occurred_at) as day')
                ->selectRaw('count(*) as clicks')
                ->groupByRaw('date(occurred_at)')
                ->get()
                ->each(function (object $row) use (&$days): void {
                    $day = (string) $row->day;

                    if (array_key_exists($day, $days)) {
                        $days[$day] = (int) $row->clicks;
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
