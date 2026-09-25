import { useEffect, useMemo, useState } from 'react';
import { AppLink } from '@/components/app-link';
import {
    ActivityBarChart,
    MetricStat,
    ProgressRow,
    SoftCard,
    SpendLineChart,
} from '@/components/ds';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { compact, euros } from '@/company/pages/creators/format';
import type { Deal } from '@/creator/pages/deals/types';
import { ApiError, creatorApi, http } from '@/lib/api';

type RangeDays = 7 | 30 | 90;

type SeriesPoint = {
    day: string;
    clicks: number;
    unique_clicks: number;
    earnings_cents: number;
};

type Overview = {
    impressions: number;
    likes: number;
    comments: number;
    clicks: number;
    unique_clicks: number;
    qualified_clicks: number;
    leads_count: number;
    ctr: number | null;
    engagement: number;
    public_posts_count: number;
    followers_count: number | null;
    earnings_cents: number;
    active_deals_count: number;
    completed_deals_count: number;
    from: string;
    to: string;
    series: SeriesPoint[];
    growth: {
        clicks: number | null;
        earnings: number | null;
    };
    period: {
        clicks: number;
        unique_clicks: number;
        earnings_cents: number;
    };
};

type DealMetrics = {
    impressions: number;
    unique_clicks: number;
    qualified_clicks: number;
    leads_count: number;
    ctr: number | null;
};

type DealRow = Deal & {
    metrics: DealMetrics;
};

const scoredStatuses = new Set(['booked', 'completed']);

const ranges: Array<{ days: RangeDays; label: string }> = [
    { days: 7, label: '7 days' },
    { days: 30, label: '30 days' },
    { days: 90, label: '90 days' },
];

function formatNumber(value: number | undefined | null): string {
    if (value === undefined || value === null) {
        return '—';
    }

    return new Intl.NumberFormat('en-GB', {
        notation: value >= 10_000 ? 'compact' : 'standard',
        maximumFractionDigits: 1,
    }).format(value);
}

function formatDayLabel(day: string, spanDays: number): string {
    const date = new Date(`${day}T12:00:00`);

    if (Number.isNaN(date.getTime())) {
        return day;
    }

    if (spanDays <= 7) {
        return date.toLocaleDateString('en-GB', { weekday: 'short' });
    }

    return date.toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
    });
}

function shareOf(part: number, whole: number): number {
    if (whole < 1) {
        return 0;
    }

    return Math.round((part / whole) * 100);
}

function pct(value: number | null): string {
    if (value === null) {
        return '—';
    }

    return `${(value * 100).toFixed(1)}%`;
}

function growthLabel(value: number | null): string | undefined {
    if (value === null) {
        return undefined;
    }

    const prefix = value > 0 ? '+' : '';

    return `${prefix}${value}%`;
}

function dateRange(days: RangeDays): { from: string; to: string } {
    const to = new Date();
    const from = new Date();
    from.setDate(to.getDate() - (days - 1));

    return {
        from: from.toISOString().slice(0, 10),
        to: to.toISOString().slice(0, 10),
    };
}

export default function CreatorMetricsPage() {
    const [days, setDays] = useState<RangeDays>(30);
    const [data, setData] = useState<Overview | null>(null);
    const [deals, setDeals] = useState<DealRow[]>([]);
    const [error, setError] = useState<string | null>(null);

    const query = useMemo(() => dateRange(days), [days]);

    useEffect(() => {
        let cancelled = false;

        Promise.all([
            http.get<Overview>(creatorApi.analyticsOverview(query)),
            http.get<DealRow[]>(creatorApi.collaborations()),
        ])
            .then(([{ data: overview }, { data: collaborations }]) => {
                if (!cancelled) {
                    setData(overview);
                    setDeals(
                        collaborations.filter(
                            (deal): deal is DealRow =>
                                scoredStatuses.has(deal.status) &&
                                deal.metrics !== undefined,
                        ),
                    );
                    setError(null);
                }
            })
            .catch((caught: unknown) => {
                if (!cancelled) {
                    setError(
                        caught instanceof ApiError
                            ? caught.message
                            : 'Could not load metrics.',
                    );
                }
            });

        return () => {
            cancelled = true;
        };
    }, [query]);

    const series = data?.series ?? [];
    const maxClicks = Math.max(0, ...series.map((point) => point.clicks));

    const activityData = series.map((point) => ({
        day: formatDayLabel(point.day, days),
        value: point.clicks,
        highlight: point.clicks > 0 && point.clicks === maxClicks,
    }));

    const earningsData = series.map((point) => ({
        day: formatDayLabel(point.day, days),
        value: point.earnings_cents / 100,
    }));

    const impressions = data?.impressions ?? 0;
    const conversionRows = data
        ? [
              {
                  label: 'Reach',
                  count: data.impressions,
                  value: 100,
              },
              {
                  label: 'Unique',
                  count: data.unique_clicks,
                  value: shareOf(data.unique_clicks, impressions),
              },
              {
                  label: 'Qualified',
                  count: data.qualified_clicks,
                  value: shareOf(data.qualified_clicks, impressions),
              },
              {
                  label: 'Leads',
                  count: data.leads_count,
                  value: shareOf(data.leads_count, impressions),
              },
          ]
        : [];

    return (
        <div className="flex w-full flex-1 flex-col gap-8">
            <div className="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                <div className="space-y-2">
                    <p className="text-sm text-muted-foreground">Performance</p>
                    <h1 className="text-heading font-medium tracking-tight">
                        Metrics
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        How your live posts convert from reach to leads.
                    </p>
                </div>

                <div className="flex flex-wrap items-center gap-2 sm:gap-3">
                    {data ? (
                        <Badge variant="accent">
                            {formatNumber(data.public_posts_count)} live posts
                        </Badge>
                    ) : null}
                    {ranges.map((range) => (
                        <Button
                            key={range.days}
                            type="button"
                            size="sm"
                            variant={days === range.days ? 'default' : 'outline'}
                            onClick={() => setDays(range.days)}
                        >
                            {range.label}
                        </Button>
                    ))}
                </div>
            </div>

            <InputError message={error ?? undefined} />

            {data === null && error === null ? (
                <SoftCard>
                    <p className="text-sm text-muted-foreground">
                        Loading metrics…
                    </p>
                </SoftCard>
            ) : null}

            {data ? (
                <>
                    <div className="grid gap-5 lg:grid-cols-12">
                        <SoftCard className="border-transparent bg-lime-soft lg:col-span-5">
                            <MetricStat
                                value={formatNumber(data.impressions)}
                                label="Total reach"
                                hint={
                                    <span className="text-lime-soft-foreground">
                                        Across {formatNumber(data.public_posts_count)}{' '}
                                        live posts
                                    </span>
                                }
                            />
                        </SoftCard>
                        <SoftCard className="lg:col-span-3">
                            <MetricStat
                                value={pct(data.ctr)}
                                label="CTR"
                                hint={`${formatNumber(data.unique_clicks)} unique clicks`}
                            />
                        </SoftCard>
                        <SoftCard className="lg:col-span-4">
                            <MetricStat
                                value={euros(data.earnings_cents)}
                                label="Earnings"
                                hint={
                                    <Badge variant="soft" className="mt-1">
                                        {formatNumber(data.leads_count)} leads
                                    </Badge>
                                }
                            />
                        </SoftCard>
                    </div>

                    <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                        <SoftCard>
                            <MetricStat
                                value={formatNumber(data.likes)}
                                label="Reactions"
                            />
                        </SoftCard>
                        <SoftCard>
                            <MetricStat
                                value={formatNumber(data.comments)}
                                label="Comments"
                            />
                        </SoftCard>
                        <SoftCard>
                            <MetricStat
                                value={formatNumber(data.engagement)}
                                label="Engagement"
                            />
                        </SoftCard>
                        <SoftCard>
                            <MetricStat
                                value={compact(data.followers_count)}
                                label="Followers"
                            />
                        </SoftCard>
                    </div>

                    <div className="grid grid-cols-1 gap-5 xl:grid-cols-12">
                        <div className="xl:col-span-5">
                            <SoftCard title="Conversion funnel" className="h-full">
                                <MetricStat
                                    value={pct(data.ctr)}
                                    label="Click-through rate"
                                    hint={
                                        <span>
                                            {formatNumber(data.leads_count)} leads
                                            from {formatNumber(data.impressions)}{' '}
                                            reach
                                        </span>
                                    }
                                    className="mb-6"
                                />
                                <div className="space-y-3">
                                    {conversionRows.map((row) => (
                                        <div
                                            key={row.label}
                                            className="flex items-center gap-3"
                                        >
                                            <div className="min-w-0 flex-1">
                                                <ProgressRow
                                                    label={row.label}
                                                    value={row.value}
                                                />
                                            </div>
                                            <span className="shrink-0 text-sm font-medium tabular-nums text-muted-foreground">
                                                {formatNumber(row.count)}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            </SoftCard>
                        </div>

                        <div className="xl:col-span-4">
                            <ActivityBarChart
                                title="Clicks"
                                metric={formatNumber(data.period.clicks)}
                                metricLabel="In selected range"
                                data={activityData}
                                callout={formatNumber(data.period.unique_clicks)}
                            />
                        </div>

                        <div className="xl:col-span-3">
                            <SpendLineChart
                                title="Earnings"
                                metric={euros(data.period.earnings_cents)}
                                compare={`${euros(data.earnings_cents)} all time`}
                                sideStats={[
                                    {
                                        value: formatNumber(data.active_deals_count),
                                        label: 'Active',
                                    },
                                    {
                                        value: formatNumber(
                                            data.completed_deals_count,
                                        ),
                                        label: 'Done',
                                    },
                                ]}
                                data={earningsData}
                                callout={growthLabel(data.growth.earnings)}
                            />
                        </div>
                    </div>

                    <section className="flex flex-col gap-5">
                        <div className="flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <h2 className="text-title font-medium tracking-tight">
                                    By deal
                                </h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Booked and completed collaborations.
                                </p>
                            </div>
                            <Button variant="outline" size="sm" asChild>
                                <AppLink href="/deals">Open deals</AppLink>
                            </Button>
                        </div>

                        {deals.length === 0 ? (
                            <SoftCard className="bg-muted">
                                <p className="text-sm text-muted-foreground">
                                    Metrics appear here after a deal is booked.{' '}
                                    <AppLink
                                        href="/deals"
                                        className="font-medium text-foreground underline-offset-4 hover:underline"
                                    >
                                        Open deals
                                    </AppLink>
                                </p>
                            </SoftCard>
                        ) : (
                            <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                                {deals.map((deal) => (
                                    <SoftCard
                                        key={deal.id}
                                        action={
                                            <Badge
                                                variant={
                                                    deal.status === 'completed'
                                                        ? 'accent'
                                                        : 'soft'
                                                }
                                            >
                                                {deal.status}
                                            </Badge>
                                        }
                                    >
                                        <div className="mb-5">
                                            <p className="text-lg font-medium tracking-tight">
                                                {deal.campaign.name}
                                            </p>
                                            <p className="mt-1 text-sm text-muted-foreground">
                                                {deal.company.name ?? 'Company'}
                                            </p>
                                        </div>

                                        <div className="mb-5 flex flex-wrap gap-2">
                                            <span className="inline-flex items-center gap-1.5 rounded-pill bg-muted px-3 py-1.5 text-sm">
                                                <span className="font-medium tabular-nums">
                                                    {formatNumber(
                                                        deal.metrics.impressions,
                                                    )}
                                                </span>
                                                <span className="text-muted-foreground">
                                                    reach
                                                </span>
                                            </span>
                                            <span className="inline-flex items-center gap-1.5 rounded-pill bg-muted px-3 py-1.5 text-sm">
                                                <span className="font-medium tabular-nums">
                                                    {formatNumber(
                                                        deal.metrics
                                                            .unique_clicks,
                                                    )}
                                                </span>
                                                <span className="text-muted-foreground">
                                                    unique
                                                </span>
                                            </span>
                                            <span className="inline-flex items-center gap-1.5 rounded-pill bg-lime-soft px-3 py-1.5 text-sm text-lime-soft-foreground">
                                                <span className="font-medium tabular-nums">
                                                    {formatNumber(
                                                        deal.metrics.leads_count,
                                                    )}
                                                </span>
                                                <span>leads</span>
                                            </span>
                                            <Badge variant="accent">
                                                {pct(deal.metrics.ctr)} CTR
                                            </Badge>
                                        </div>

                                        <div className="mb-5 space-y-3">
                                            <ProgressRow
                                                label="Qualified"
                                                value={shareOf(
                                                    deal.metrics
                                                        .qualified_clicks,
                                                    Math.max(
                                                        deal.metrics
                                                            .impressions,
                                                        1,
                                                    ),
                                                )}
                                            />
                                        </div>

                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            asChild
                                        >
                                            <AppLink href={`/deals/${deal.id}`}>
                                                Open deal →
                                            </AppLink>
                                        </Button>
                                    </SoftCard>
                                ))}
                            </div>
                        )}
                    </section>
                </>
            ) : null}
        </div>
    );
}
