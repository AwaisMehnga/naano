import { useEffect, useMemo, useState } from 'react';
import { AppLink } from '@/components/app-link';
import {
    ActivityBarChart,
    DateRangePills,
    MetricStat,
    ProgressRow,
    RevenueAreaChart,
    SoftCard,
    SpendLineChart,
} from '@/components/ds';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { compact, euros } from '@/company/pages/creators/format';
import { ApiError, creatorApi, http } from '@/lib/api';

type RangeDays = 7 | 30 | 90;

type SeriesPoint = {
    day: string;
    clicks: number;
    unique_clicks: number;
    earnings_cents: number;
};

type ComparisonPoint = {
    day: string;
    current: number;
    previous: number;
};

type AudienceSegment = {
    label: string;
    value: number;
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
    connections_count: number | null;
    earnings_cents: number;
    active_deals_count: number;
    completed_deals_count: number;
    from: string;
    to: string;
    series: SeriesPoint[];
    comparison: ComparisonPoint[];
    audience_segments: AudienceSegment[];
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

function formatPillDate(day: string): string {
    const date = new Date(`${day}T12:00:00`);

    if (Number.isNaN(date.getTime())) {
        return day;
    }

    return date.toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'numeric',
        year: 'numeric',
    }).replace(/\//g, ' ');
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

export default function CreatorDashboardPage() {
    const [days, setDays] = useState<RangeDays>(30);
    const [overview, setOverview] = useState<Overview | null>(null);
    const [error, setError] = useState<string | null>(null);

    const query = useMemo(() => dateRange(days), [days]);

    useEffect(() => {
        let cancelled = false;

        http.get<Overview>(creatorApi.analyticsOverview(query))
            .then(({ data }) => {
                if (!cancelled) {
                    setOverview(data);
                    setError(null);
                }
            })
            .catch((reason) => {
                if (!cancelled) {
                    setError(
                        reason instanceof ApiError
                            ? reason.message
                            : 'Could not load your dashboard.',
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

    const earningsData = series.map((point) => ({
        day: formatDayLabel(point.day, days),
        value: point.earnings_cents / 100,
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
                    <p className="text-sm text-muted-foreground">Creator</p>
                    <h1 className="text-heading font-medium tracking-tight">
                        Dashboard
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Reach, bookings, and earnings from your live posts.
                    </p>
                </div>

                <div className="flex flex-wrap items-center gap-2 sm:gap-3">
                    {ranges.map((range) => (
                        <Button
                            key={range.days}
                            type="button"
                            size="sm"
                            variant={days === range.days ? 'default' : 'outline'}
                            className="rounded-pill"
                            onClick={() => setDays(range.days)}
                        >
                            {range.label}
                        </Button>
                    ))}
                    {/* {overview ? (
                        <DateRangePills
                            start={formatPillDate(overview.from)}
                            end={formatPillDate(overview.to)}
                        />
                    ) : null} */}
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
                        value={formatNumber(overview?.engagement)}
                        label="Engagement"
                    />
                </SoftCard>
                <SoftCard>
                    <MetricStat
                        value={
                            overview
                                ? euros(overview.earnings_cents)
                                : '—'
                        }
                        label="Earnings"
                    />
                </SoftCard>
                <SoftCard>
                    <MetricStat
                        value={compact(overview?.followers_count)}
                        label="Followers"
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
                    <SoftCard title="Audience" className="h-full">
                        <MetricStat
                            value={compact(overview?.followers_count)}
                            label="Followers"
                            hint={
                                <span>
                                    {compact(overview?.connections_count)}{' '}
                                    connections
                                </span>
                            }
                            className="mb-5"
                        />
                        {overview && overview.audience_segments.length > 0 ? (
                            <div className="space-y-3">
                                {overview.audience_segments.map((segment) => (
                                    <ProgressRow
                                        key={segment.label}
                                        label={segment.label}
                                        value={segment.value}
                                    />
                                ))}
                            </div>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                Audience mix appears after LinkedIn sync.
                            </p>
                        )}
                        <div className="mt-5">
                            <Button variant="ghost" size="sm" asChild>
                                <AppLink href="/setting/audience">
                                    Open audience →
                                </AppLink>
                            </Button>
                        </div>
                    </SoftCard>
                </div>

                <div className="xl:col-span-5">
                    <SpendLineChart
                        title="Earnings"
                        metric={
                            overview
                                ? euros(overview.period.earnings_cents)
                                : '—'
                        }
                        compare={
                            overview
                                ? `${euros(overview.earnings_cents)} all time`
                                : undefined
                        }
                        sideStats={[
                            {
                                value: formatNumber(
                                    overview?.public_posts_count,
                                ),
                                label: 'Live posts',
                            },
                            {
                                value: formatNumber(
                                    overview?.active_deals_count,
                                ),
                                label: 'Active deals',
                            },
                        ]}
                        data={earningsData}
                        callout={growthLabel(overview?.growth.earnings ?? null)}
                    />
                </div>

                <div className="xl:col-span-4">
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
                        <div className="mt-5">
                            <Button variant="ghost" size="sm" asChild>
                                <AppLink href="/metrics">
                                    Open metrics →
                                </AppLink>
                            </Button>
                        </div>
                    </SoftCard>
                </div>

                <div className="xl:col-span-3">
                    <SoftCard title="Bookings" className="h-full">
                        <div className="space-y-4">
                            <div className="flex items-center justify-between gap-3">
                                <p className="text-sm text-muted-foreground">
                                    Active
                                </p>
                                <Badge variant="accent">
                                    {formatNumber(overview?.active_deals_count)}
                                </Badge>
                            </div>
                            <div className="flex items-center justify-between gap-3">
                                <p className="text-sm text-muted-foreground">
                                    Completed
                                </p>
                                <span className="text-sm font-medium tabular-nums">
                                    {formatNumber(
                                        overview?.completed_deals_count,
                                    )}
                                </span>
                            </div>
                            <div className="flex items-center justify-between gap-3">
                                <p className="text-sm text-muted-foreground">
                                    Live posts
                                </p>
                                <span className="text-sm font-medium tabular-nums">
                                    {formatNumber(
                                        overview?.public_posts_count,
                                    )}
                                </span>
                            </div>
                        </div>
                        <div className="mt-6 flex flex-col gap-2">
                            <Button variant="default" size="sm" asChild>
                                <AppLink href="/opportunities">
                                    Find campaigns
                                </AppLink>
                            </Button>
                            <Button variant="outline" size="sm" asChild>
                                <AppLink href="/deals">Open deals</AppLink>
                            </Button>
                        </div>
                    </SoftCard>
                </div>
            </div>
        </div>
    );
}
