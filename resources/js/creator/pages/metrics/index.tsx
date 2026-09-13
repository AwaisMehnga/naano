import { useEffect, useState } from 'react';
import { AppLink } from '@/components/app-link';
import InputError from '@/components/input-error';
import { compact, euros } from '@/company/pages/creators/format';
import type { Deal } from '@/creator/pages/deals/types';
import { ApiError, creatorApi, http } from '@/lib/api';
import { cn } from '@/lib/utils';

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

export default function CreatorMetricsPage() {
    const [data, setData] = useState<Overview | null>(null);
    const [deals, setDeals] = useState<DealRow[]>([]);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        let cancelled = false;

        Promise.all([
            http.get<Overview>(creatorApi.analyticsOverview),
            http.get<Deal[]>(creatorApi.collaborations()),
        ])
            .then(async ([{ data: overview }, { data: collaborations }]) => {
                const scored = collaborations.filter((deal) =>
                    scoredStatuses.has(deal.status),
                );
                const rows = await Promise.all(
                    scored.map(async (deal) => {
                        const { data: metrics } = await http.get<DealMetrics>(
                            creatorApi.collaborationMetrics(deal.id),
                        );

                        return { ...deal, metrics };
                    }),
                );

                if (!cancelled) {
                    setData(overview);
                    setDeals(rows);
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
    }, []);

    return (
        <div className="flex w-full flex-1 flex-col gap-10">
            <div className="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Metrics
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        How your live posts convert from reach to leads.
                    </p>
                </div>
                {data && (
                    <p className="text-muted-foreground text-sm">
                        {formatNumber(data.public_posts_count)} live posts
                    </p>
                )}
            </div>
            <InputError message={error ?? undefined} />
            {data === null && error === null ? (
                <p className="text-muted-foreground text-sm">
                    Loading metrics…
                </p>
            ) : null}
            {data && (
                <>
                    <section className="border-border grid gap-8 border-b pb-10 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                        <div>
                            <p className="text-muted-foreground text-sm">
                                Reach
                            </p>
                            <p className="mt-2 text-5xl font-semibold tracking-tight tabular-nums sm:text-6xl">
                                {formatNumber(data.impressions)}
                            </p>
                        </div>
                        <dl className="grid grid-cols-2 gap-x-8 gap-y-4 sm:grid-cols-3">
                            <Fact
                                label="Unique clicks"
                                value={formatNumber(data.unique_clicks)}
                            />
                            <Fact label="CTR" value={pct(data.ctr)} />
                            <Fact
                                label="Earnings"
                                value={euros(data.earnings_cents)}
                            />
                        </dl>
                    </section>
                    <ConversionLadder
                        impressions={data.impressions}
                        uniqueClicks={data.unique_clicks}
                        qualified={data.qualified_clicks}
                        leads={data.leads_count}
                    />
                    <section className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        <Fact
                            label="Reactions"
                            value={formatNumber(data.likes)}
                        />
                        <Fact
                            label="Comments"
                            value={formatNumber(data.comments)}
                        />
                        <Fact
                            label="Engagement"
                            value={formatNumber(data.engagement)}
                        />
                        <Fact
                            label="Followers"
                            value={compact(data.followers_count)}
                        />
                    </section>
                    <section className="flex flex-col gap-4">
                        <div>
                            <h2 className="text-lg font-semibold">By deal</h2>
                            <p className="text-muted-foreground text-sm">
                                Booked and completed collaborations.
                            </p>
                        </div>
                        {deals.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                Metrics appear here after a deal is booked.{' '}
                                <AppLink
                                    href="/deals"
                                    className="text-foreground underline-offset-4 hover:underline"
                                >
                                    Open deals
                                </AppLink>
                            </p>
                        ) : (
                            <div className="border-border overflow-x-auto rounded-lg border">
                                <table className="w-full min-w-160 text-left text-sm">
                                    <thead className="bg-muted/40 text-muted-foreground">
                                        <tr>
                                            <th className="px-4 py-3 font-medium">
                                                Campaign
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Reach
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Unique
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Qualified
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Leads
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                CTR
                                            </th>
                                            <th className="px-4 py-3" />
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {deals.map((deal) => (
                                            <tr
                                                key={deal.id}
                                                className="border-border border-t"
                                            >
                                                <td className="px-4 py-3">
                                                    <p className="font-medium">
                                                        {deal.campaign.name}
                                                    </p>
                                                    <p className="text-muted-foreground text-xs">
                                                        {deal.company.name ??
                                                            'Company'}
                                                    </p>
                                                </td>
                                                <td className="px-4 py-3 tabular-nums">
                                                    {formatNumber(
                                                        deal.metrics
                                                            .impressions,
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 tabular-nums">
                                                    {formatNumber(
                                                        deal.metrics
                                                            .unique_clicks,
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 tabular-nums">
                                                    {formatNumber(
                                                        deal.metrics
                                                            .qualified_clicks,
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 tabular-nums">
                                                    {formatNumber(
                                                        deal.metrics
                                                            .leads_count,
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 tabular-nums">
                                                    {pct(deal.metrics.ctr)}
                                                </td>
                                                <td className="px-4 py-3 text-right">
                                                    <AppLink
                                                        href={`/deals/${deal.id}`}
                                                        className="underline-offset-4 hover:underline"
                                                    >
                                                        Open
                                                    </AppLink>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </section>
                </>
            )}
        </div>
    );
}

function ConversionLadder({
    impressions,
    uniqueClicks,
    qualified,
    leads,
}: {
    impressions: number;
    uniqueClicks: number;
    qualified: number;
    leads: number;
}) {
    const max = Math.max(impressions, 1);
    const steps = [
        { label: 'Reach', value: impressions, tone: 'bg-primary' },
        {
            label: 'Unique clicks',
            value: uniqueClicks,
            tone: 'bg-primary/70',
        },
        { label: 'Qualified', value: qualified, tone: 'bg-primary/45' },
        { label: 'Leads', value: leads, tone: 'bg-primary/25' },
    ];

    return (
        <section className="flex flex-col gap-4">
            <div>
                <h2 className="text-lg font-semibold">Conversion</h2>
                <p className="text-muted-foreground text-sm">
                    Each step is a share of total reach.
                </p>
            </div>
            <div className="flex flex-col gap-3">
                {steps.map((step) => (
                    <div
                        key={step.label}
                        className="grid items-center gap-3 sm:grid-cols-[8rem_1fr_4.5rem]"
                    >
                        <p className="text-sm">{step.label}</p>
                        <div className="bg-muted h-3 overflow-hidden rounded-full">
                            <div
                                className={cn('h-full rounded-full', step.tone)}
                                style={{ width: barWidth(step.value, max) }}
                            />
                        </div>
                        <p className="text-right text-sm tabular-nums">
                            {formatNumber(step.value)}
                        </p>
                    </div>
                ))}
            </div>
        </section>
    );
}

function Fact({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-muted-foreground text-sm">{label}</dt>
            <dd className="mt-1 text-xl font-semibold tabular-nums">{value}</dd>
        </div>
    );
}

function barWidth(value: number, max: number): string {
    if (value <= 0) {
        return '0%';
    }

    return `${Math.max((value / max) * 100, 2)}%`;
}

function formatNumber(value: number): string {
    return new Intl.NumberFormat('en-GB').format(value);
}

function pct(value: number | null): string {
    if (value === null) {
        return '—';
    }

    return `${(value * 100).toFixed(1)}%`;
}
