import { useEffect, useMemo, useState, type ReactNode } from 'react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Line,
    LineChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import { AppLink } from '@/components/app-link';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
};

const ranges: Array<{ days: RangeDays; label: string }> = [
    { days: 7, label: '7 days' },
    { days: 30, label: '30 days' },
    { days: 90, label: '90 days' },
];

export default function CompanyAnalyticsPage() {
    const [days, setDays] = useState<RangeDays>(30);
    const [data, setData] = useState<Overview | null>(null);
    const [error, setError] = useState<string | null>(null);

    const query = useMemo(() => dateRange(days), [days]);

    useEffect(() => {
        let cancelled = false;

        http.get<Overview>(companyApi.analyticsOverview(query))
            .then(({ data: next }) => {
                if (!cancelled) {
                    setData(next);
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

    const chartData = (data?.series ?? []).map((point) => ({
        ...point,
        label: formatDay(point.day),
        spend: point.spend_cents / 100,
    }));

    return (
        <div className="flex w-full flex-1 flex-col gap-6">
            <div className="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Dashboard
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Workspace totals and daily clicks, leads, and spend.
                    </p>
                </div>
                <div className="flex flex-wrap items-center gap-2">
                    {ranges.map((range) => (
                        <Button
                            key={range.days}
                            type="button"
                            size="sm"
                            variant={
                                days === range.days ? 'default' : 'outline'
                            }
                            onClick={() => setDays(range.days)}
                        >
                            {range.label}
                        </Button>
                    ))}
                    <Button type="button" variant="outline" asChild>
                        <AppLink href="/campaigns">Campaigns</AppLink>
                    </Button>
                </div>
            </div>
            <InputError message={error ?? undefined} />
            {data && (
                <>
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <Stat
                            label="Impressions"
                            value={formatNumber(data.impressions)}
                            hint="LinkedIn ingest"
                        />
                        <Stat
                            label="Unique clicks"
                            value={formatNumber(data.unique_clicks)}
                            hint={`${formatNumber(data.clicks)} total · CTR ${pct(data.ctr)}`}
                        />
                        <Stat
                            label="Qualified clicks"
                            value={formatNumber(data.qualified_clicks)}
                            hint="Pixel dwell ≥ 30s"
                        />
                        <Stat
                            label="Leads"
                            value={formatNumber(data.leads_count)}
                            hint="Form + manual"
                        />
                        <Stat
                            label="Spend"
                            value={euros(data.spend_cents)}
                            hint="Captured holds"
                        />
                        <Stat
                            label="Pipeline"
                            value={euros(data.pipeline_cents)}
                            hint="Attributed lead value"
                        />
                    </div>
                    <div className="grid gap-4 xl:grid-cols-2">
                        <ChartCard title="Clicks" hint={`${data.from} to ${data.to}`}>
                            <ResponsiveContainer width="100%" height={260}>
                                <LineChart data={chartData}>
                                    <CartesianGrid
                                        stroke="var(--border)"
                                        vertical={false}
                                    />
                                    <XAxis
                                        dataKey="label"
                                        tick={{ fill: 'var(--muted-foreground)', fontSize: 12 }}
                                        axisLine={{ stroke: 'var(--border)' }}
                                        tickLine={false}
                                    />
                                    <YAxis
                                        allowDecimals={false}
                                        tick={{ fill: 'var(--muted-foreground)', fontSize: 12 }}
                                        axisLine={false}
                                        tickLine={false}
                                        width={36}
                                    />
                                    <Tooltip
                                        content={({ active, payload, label }) => (
                                            <ChartTooltip
                                                active={active}
                                                payload={payload}
                                                label={label}
                                            />
                                        )}
                                    />
                                    <Line
                                        type="monotone"
                                        dataKey="clicks"
                                        name="Clicks"
                                        stroke="var(--chart-1)"
                                        strokeWidth={2}
                                        dot={false}
                                    />
                                    <Line
                                        type="monotone"
                                        dataKey="unique_clicks"
                                        name="Unique"
                                        stroke="var(--chart-2)"
                                        strokeWidth={2}
                                        dot={false}
                                    />
                                </LineChart>
                            </ResponsiveContainer>
                        </ChartCard>
                        <ChartCard title="Leads" hint={`${data.from} to ${data.to}`}>
                            <ResponsiveContainer width="100%" height={260}>
                                <BarChart data={chartData}>
                                    <CartesianGrid
                                        stroke="var(--border)"
                                        vertical={false}
                                    />
                                    <XAxis
                                        dataKey="label"
                                        tick={{ fill: 'var(--muted-foreground)', fontSize: 12 }}
                                        axisLine={{ stroke: 'var(--border)' }}
                                        tickLine={false}
                                    />
                                    <YAxis
                                        allowDecimals={false}
                                        tick={{ fill: 'var(--muted-foreground)', fontSize: 12 }}
                                        axisLine={false}
                                        tickLine={false}
                                        width={36}
                                    />
                                    <Tooltip
                                        content={({ active, payload, label }) => (
                                            <ChartTooltip
                                                active={active}
                                                payload={payload}
                                                label={label}
                                            />
                                        )}
                                    />
                                    <Bar
                                        dataKey="leads"
                                        name="Leads"
                                        fill="var(--chart-1)"
                                        radius={[4, 4, 0, 0]}
                                    />
                                </BarChart>
                            </ResponsiveContainer>
                        </ChartCard>
                        <ChartCard
                            title="Spend"
                            hint={`${data.from} to ${data.to}`}
                            className="xl:col-span-2"
                        >
                            <ResponsiveContainer width="100%" height={260}>
                                <LineChart data={chartData}>
                                    <CartesianGrid
                                        stroke="var(--border)"
                                        vertical={false}
                                    />
                                    <XAxis
                                        dataKey="label"
                                        tick={{ fill: 'var(--muted-foreground)', fontSize: 12 }}
                                        axisLine={{ stroke: 'var(--border)' }}
                                        tickLine={false}
                                    />
                                    <YAxis
                                        tick={{ fill: 'var(--muted-foreground)', fontSize: 12 }}
                                        axisLine={false}
                                        tickLine={false}
                                        width={48}
                                        tickFormatter={(value: number) =>
                                            `€${value}`
                                        }
                                    />
                                    <Tooltip
                                        content={({ active, payload, label }) => (
                                            <ChartTooltip
                                                active={active}
                                                payload={payload}
                                                label={label}
                                                money
                                            />
                                        )}
                                    />
                                    <Line
                                        type="monotone"
                                        dataKey="spend"
                                        name="Spend"
                                        stroke="var(--chart-1)"
                                        strokeWidth={2}
                                        dot={false}
                                    />
                                </LineChart>
                            </ResponsiveContainer>
                        </ChartCard>
                    </div>
                </>
            )}
        </div>
    );
}

function ChartCard({
    title,
    hint,
    className,
    children,
}: {
    title: string;
    hint: string;
    className?: string;
    children: ReactNode;
}) {
    return (
        <Card className={className}>
            <CardHeader className="px-4">
                <CardTitle className="text-sm font-medium">{title}</CardTitle>
                <p className="text-xs text-muted-foreground">{hint}</p>
            </CardHeader>
            <CardContent className="px-2 pb-2">{children}</CardContent>
        </Card>
    );
}

function ChartTooltip({
    active,
    payload,
    label,
    money = false,
}: {
    active?: boolean;
    payload?: ReadonlyArray<{ name?: string | number; value?: unknown }>;
    label?: unknown;
    money?: boolean;
}) {
    if (!active || !payload?.length) {
        return null;
    }

    return (
        <div className="rounded-md border border-border bg-card px-3 py-2 text-xs">
            <p className="mb-1 font-medium">{String(label ?? '')}</p>
            {payload.map((item) => {
                const value = Number(item.value ?? 0);

                return (
                    <p key={String(item.name)} className="text-muted-foreground">
                        {item.name}:{' '}
                        <span className="tabular-nums text-foreground">
                            {money
                                ? euros(Math.round(value * 100))
                                : formatNumber(value)}
                        </span>
                    </p>
                );
            })}
        </div>
    );
}

function Stat({
    label,
    value,
    hint,
}: {
    label: string;
    value: string;
    hint?: string;
}) {
    return (
        <Card>
            <CardContent className="px-4">
                <p className="text-sm text-muted-foreground">{label}</p>
                <p className="mt-2 text-2xl font-semibold tabular-nums">
                    {value}
                </p>
                {hint && (
                    <p className="mt-1 text-sm text-muted-foreground">{hint}</p>
                )}
            </CardContent>
        </Card>
    );
}

function dateRange(days: RangeDays): { from: string; to: string } {
    const to = new Date();
    const from = new Date();
    from.setDate(to.getDate() - (days - 1));

    return { from: isoDate(from), to: isoDate(to) };
}

function isoDate(value: Date): string {
    const year = value.getFullYear();
    const month = String(value.getMonth() + 1).padStart(2, '0');
    const day = String(value.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function formatDay(value: string): string {
    const date = new Date(`${value}T00:00:00`);

    return date.toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
    });
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
