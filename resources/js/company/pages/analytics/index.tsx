import { useEffect, useMemo, useState } from 'react';
import { AppLink } from '@/components/app-link';
import {
    ActivityBarChart,
    MetricStat,
    ProgressRow,
    RevenueAreaChart,
    SoftCard,
    SpendLineChart,
} from '@/components/ds';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { euros } from '@/company/pages/creators/format';
import { ApiError, companyApi, http } from '@/lib/api';

type RangeDays = 7 | 30 | 90;

type SeriesPoint = {
    day: string;
    clicks: number;
    unique_clicks: number;
    leads: number;
    spend_cents: number;
};

type ComparisonPoint = {
    day: string;
    current: number;
    previous: number;
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
    spend_cents: number;
    pipeline_cents: number;
    from: string;
    to: string;
    series: SeriesPoint[];
    comparison: ComparisonPoint[];
    period: {
        clicks: number;
        unique_clicks: number;
        leads_count: number;
        spend_cents: number;
        pipeline_cents: number;
    };
    growth: {
        clicks: number | null;
        leads: number | null;
        spend: number | null;
    };
    campaigns_count: number;
    live_campaigns_count: number;
    collab_counts: {
        active: number;
        todo: number;
        completed: number;
    };
    wallet: {
        available_cents: number;
        held_cents: number;
    };
    cpl_cents: number | null;
};

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

function growthLabel(value: number | null): string | undefined {
    if (value === null) {
        return undefined;
    }

    const prefix = value > 0 ? '+' : '';

    return `${prefix}${value}%`;
}

function shareOf(part: number, whole: number): number {
    if (whole < 1) {
        return 0;
    }

    return Math.round((part / whole) * 100);
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

export default function CompanyAnalyticsPage() {
    const [days, setDays] = useState<RangeDays>(30);
    const [overview, setOverview] = useState<Overview | null>(null);
    const [error, setError] = useState<string | null>(null);

    const query = useMemo(() => dateRange(days), [days]);

    useEffect(() => {
        let cancelled = false;

        http.get<Overview>(companyApi.analyticsOverview(query))
            .then(({ data }) => {
                if (!cancelled) {
                    setOverview(data);
                    setError(null);
                }
            })
            .catch((caught: unknown) => {
                if (!cancelled) {
                    setError(
                        caught instanceof ApiError
                            ? caught.message
                            : 'Could not load analytics.',
                    );
                }
            });

        return () => {
            cancelled = true;
        };
    }, [query]);

    const series = overview?.series ?? [];
    const maxClicks = Math.max(0, ...series.map((point) => point.clicks));

    const activityData = series.map((point) => ({
        day: formatDayLabel(point.day, days),
        value: point.clicks,
        highlight: point.clicks > 0 && point.clicks === maxClicks,
    }));

    const spendData = series.map((point) => ({
        day: formatDayLabel(point.day, days),
        value: point.spend_cents / 100,
    }));

    const comparisonData = (overview?.comparison ?? []).map((point) => ({
        day: formatDayLabel(point.day, days),
        current: point.current,
        previous: point.previous,
    }));

    const impressions = overview?.impressions ?? 0;
    const conversionRows = overview
        ? [
              {
                  label: 'Unique clicks',
                  value: shareOf(overview.unique_clicks, impressions),
              },
              {
                  label: 'Qualified',
                  value: shareOf(overview.qualified_clicks, impressions),
              },
              {
                  label: 'Leads',
                  value: shareOf(overview.leads_count, impressions),
              },
          ]
        : [];

    return (
        <div className="flex w-full flex-1 flex-col gap-8">
            <div className="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                <div className="space-y-2">
                    <p className="text-sm text-muted-foreground">Company</p>
                    <h1 className="text-heading font-medium tracking-tight">
                        Dashboard
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Spend, pipeline, and collaboration health for this
                        workspace.
                    </p>
                </div>

                <div className="flex flex-wrap items-center gap-2 sm:gap-3">
                    {ranges.map((range) => (
                        <Button
                            key={range.days}
                            type="button"
                            size="sm"
                            variant={
                                days === range.days ? 'default' : 'outline'
                            }
                            className="rounded-pill"
                            onClick={() => setDays(range.days)}
                        >
                            {range.label}
                        </Button>
                    ))}
                </div>
            </div>

            <InputError message={error ?? undefined} />

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <SoftCard>
                    <MetricStat
                        value={formatNumber(overview?.impressions)}
                        label="Reach"
                    />
                </SoftCard>
                <SoftCard>
                    <MetricStat
                        value={formatNumber(overview?.unique_clicks)}
                        label="Unique clicks"
                    />
                </SoftCard>
                <SoftCard>
                    <MetricStat
                        value={formatNumber(overview?.leads_count)}
                        label="Leads"
                    />
                </SoftCard>
                <SoftCard className="border-transparent bg-accent">
                    <MetricStat
                        value={
                            overview
                                ? euros(overview.pipeline_cents)
                                : '—'
                        }
                        label="Pipeline"
                        className="text-accent-foreground [&_p]:text-accent-foreground"
                    />
                </SoftCard>
            </div>

            <div className="grid grid-cols-1 gap-5 xl:grid-cols-12">
                <div className="xl:col-span-4">
                    <ActivityBarChart
                        title="Clicks"
                        metric={formatNumber(overview?.period.clicks)}
                        metricLabel="In selected range"
                        data={activityData}
                        callout={
                            overview
                                ? formatNumber(overview.period.unique_clicks)
                                : undefined
                        }
                    />
                </div>

                <div className="xl:col-span-5">
                    <RevenueAreaChart
                        title="Clicks vs last period"
                        metric={formatNumber(overview?.period.clicks)}
                        metricLabel="This range"
                        data={comparisonData}
                        growth={growthLabel(overview?.growth.clicks ?? null)}
                    />
                </div>

                <div className="xl:col-span-3">
                    <SoftCard title="Conversion" className="h-full">
                        <MetricStat
                            value={
                                overview?.ctr != null
                                    ? `${(overview.ctr * 100).toFixed(1)}%`
                                    : '—'
                            }
                            label="CTR"
                            hint={
                                <span>
                                    {formatNumber(overview?.leads_count)} leads
                                    from {formatNumber(overview?.impressions)}{' '}
                                    reach
                                </span>
                            }
                            className="mb-5"
                        />
                        <div className="space-y-3">
                            {conversionRows.map((row) => (
                                <ProgressRow
                                    key={row.label}
                                    label={row.label}
                                    value={row.value}
                                />
                            ))}
                        </div>
                    </SoftCard>
                </div>

                <div className="xl:col-span-5">
                    <SpendLineChart
                        title="Spend"
                        metric={
                            overview
                                ? euros(overview.period.spend_cents)
                                : '—'
                        }
                        compare={
                            overview
                                ? `${euros(overview.spend_cents)} all time`
                                : undefined
                        }
                        sideStats={[
                            {
                                value: formatNumber(
                                    overview?.live_campaigns_count,
                                ),
                                label: 'Live campaigns',
                            },
                            {
                                value: formatNumber(
                                    overview?.collab_counts.active,
                                ),
                                label: 'Active collabs',
                            },
                        ]}
                        data={spendData}
                        callout={growthLabel(overview?.growth.spend ?? null)}
                    />
                </div>

                <div className="xl:col-span-4">
                    <SoftCard title="Pipeline" className="h-full">
                        <MetricStat
                            value={
                                overview
                                    ? euros(overview.period.pipeline_cents)
                                    : '—'
                            }
                            label="Value in range"
                            hint={
                                <span>
                                    {formatNumber(
                                        overview?.period.leads_count,
                                    )}{' '}
                                    leads
                                    {overview?.cpl_cents != null
                                        ? ` · CPL ${euros(overview.cpl_cents)}`
                                        : ''}
                                </span>
                            }
                            className="mb-5"
                        />
                        <div className="space-y-3 text-sm">
                            <div className="flex items-center justify-between gap-3">
                                <p className="text-muted-foreground">
                                    All-time pipeline
                                </p>
                                <span className="font-medium tabular-nums">
                                    {overview
                                        ? euros(overview.pipeline_cents)
                                        : '—'}
                                </span>
                            </div>
                            <div className="flex items-center justify-between gap-3">
                                <p className="text-muted-foreground">
                                    Campaigns
                                </p>
                                <span className="font-medium tabular-nums">
                                    {formatNumber(overview?.campaigns_count)}
                                </span>
                            </div>
                        </div>
                        <div className="mt-5">
                            <Button variant="ghost" size="sm" asChild>
                                <AppLink href="/campaigns">
                                    Open campaigns →
                                </AppLink>
                            </Button>
                        </div>
                    </SoftCard>
                </div>

                <div className="xl:col-span-3">
                    <SoftCard title="Operations" className="h-full">
                        <div className="space-y-4">
                            <div className="flex items-center justify-between gap-3">
                                <p className="text-sm text-muted-foreground">
                                    Available
                                </p>
                                <Badge variant="accent">
                                    {overview
                                        ? euros(overview.wallet.available_cents)
                                        : '—'}
                                </Badge>
                            </div>
                            <div className="flex items-center justify-between gap-3">
                                <p className="text-sm text-muted-foreground">
                                    Held
                                </p>
                                <span className="text-sm font-medium tabular-nums">
                                    {overview
                                        ? euros(overview.wallet.held_cents)
                                        : '—'}
                                </span>
                            </div>
                            <div className="flex items-center justify-between gap-3">
                                <p className="text-sm text-muted-foreground">
                                    Todo
                                </p>
                                <span className="text-sm font-medium tabular-nums">
                                    {formatNumber(overview?.collab_counts.todo)}
                                </span>
                            </div>
                            <div className="flex items-center justify-between gap-3">
                                <p className="text-sm text-muted-foreground">
                                    Active
                                </p>
                                <span className="text-sm font-medium tabular-nums">
                                    {formatNumber(
                                        overview?.collab_counts.active,
                                    )}
                                </span>
                            </div>
                            <div className="flex items-center justify-between gap-3">
                                <p className="text-sm text-muted-foreground">
                                    Completed
                                </p>
                                <span className="text-sm font-medium tabular-nums">
                                    {formatNumber(
                                        overview?.collab_counts.completed,
                                    )}
                                </span>
                            </div>
                        </div>
                        <div className="mt-6 flex flex-col gap-2">
                            <Button variant="default" size="sm" asChild>
                                <AppLink href="/wallet">Open wallet</AppLink>
                            </Button>
                            <Button variant="outline" size="sm" asChild>
                                <AppLink href="/collaboration">
                                    Collaborations
                                </AppLink>
                            </Button>
                            <Button variant="ghost" size="sm" asChild>
                                <AppLink href="/creators">Find creators</AppLink>
                            </Button>
                        </div>
                    </SoftCard>
                </div>
            </div>
        </div>
    );
}
