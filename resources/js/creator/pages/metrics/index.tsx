import { useEffect, useState } from 'react';
import InputError from '@/components/input-error';
import { euros } from '@/company/pages/creators/format';
import { ApiError, creatorApi, http } from '@/lib/api';

type Metrics = {
    impressions: number;
    likes: number;
    comments: number;
    clicks: number;
    unique_clicks: number;
    qualified_clicks: number;
    leads_count: number;
    ctr: number | null;
    earnings_cents: number;
};

export default function CreatorMetricsPage() {
    const [data, setData] = useState<Metrics | null>(null);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        http.get<Metrics>(creatorApi.analyticsOverview)
            .then(({ data: next }) => setData(next))
            .catch((caught: unknown) => {
                setError(
                    caught instanceof ApiError
                        ? caught.message
                        : 'Could not load metrics.',
                );
            });
    }, []);

    return (
        <div className="flex w-full flex-1 flex-col gap-6">
            <div>
                <h1 className="text-2xl font-semibold tracking-tight">
                    Metrics
                </h1>
                <p className="text-muted-foreground mt-1 text-sm">
                    Impressions, unique clicks, and qualified visits across
                    your deals.
                </p>
            </div>
            <InputError message={error ?? undefined} />
            {data && (
                <div className="grid gap-4 md:grid-cols-3">
                    <Stat
                        label="Impressions"
                        value={formatNumber(data.impressions)}
                    />
                    <Stat
                        label="Unique clicks"
                        value={formatNumber(data.unique_clicks)}
                        hint={`${formatNumber(data.clicks)} total · CTR ${pct(data.ctr)}`}
                    />
                    <Stat
                        label="Qualified"
                        value={formatNumber(data.qualified_clicks)}
                    />
                    <Stat
                        label="Reactions"
                        value={formatNumber(data.likes)}
                    />
                    <Stat
                        label="Comments"
                        value={formatNumber(data.comments)}
                    />
                    <Stat
                        label="Leads"
                        value={formatNumber(data.leads_count)}
                    />
                    <Stat
                        label="Earnings"
                        value={euros(data.earnings_cents)}
                        hint="Credited after live URL"
                    />
                </div>
            )}
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
        <div className="border-border bg-card rounded-2xl border p-5">
            <p className="text-muted-foreground text-sm">{label}</p>
            <p className="mt-2 text-3xl font-semibold">{value}</p>
            {hint && (
                <p className="text-muted-foreground mt-1 text-sm">{hint}</p>
            )}
        </div>
    );
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
