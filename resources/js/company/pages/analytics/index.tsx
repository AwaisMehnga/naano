import { useEffect, useState } from 'react';
import { AppLink } from '@/components/app-link';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { euros } from '@/company/pages/creators/format';
import { ApiError, companyApi, http } from '@/lib/api';

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
};

export default function CompanyAnalyticsPage() {
    const [data, setData] = useState<Overview | null>(null);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        http.get<Overview>(companyApi.analyticsOverview)
            .then(({ data: next }) => setData(next))
            .catch((caught: unknown) => {
                setError(
                    caught instanceof ApiError
                        ? caught.message
                        : 'Could not load analytics.',
                );
            });
    }, []);

    return (
        <div className="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 p-4 lg:p-6">
            <div className="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Dashboard
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Workspace impressions, unique clicks, qualified visits,
                        spend, and attributed pipeline.
                    </p>
                </div>
                <Button type="button" variant="outline" asChild>
                    <AppLink href="/campaigns">Campaigns</AppLink>
                </Button>
            </div>
            <InputError message={error ?? undefined} />
            {data && (
                <>
                    <div className="grid gap-4 md:grid-cols-3">
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
                    </div>
                    <div className="grid gap-4 md:grid-cols-3">
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
                        <Stat
                            label="Leads"
                            value={formatNumber(data.leads_count)}
                            hint="Form + manual"
                        />
                    </div>
                </>
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
