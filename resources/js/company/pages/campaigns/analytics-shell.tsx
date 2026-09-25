import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { SoftCard, MetricStat } from '@/components/ds';
import { Button } from '@/components/ui/button';
import { euros } from '@/company/pages/creators/format';
import { ApiError, companyApi, http } from '@/lib/api';

type Metrics = {
    impressions: number;
    likes: number;
    comments: number;
    clicks: number;
    unique_clicks: number;
    qualified_clicks: number;
    leads_count: number;
    ctr: number | null;
    ctr_total: number | null;
};

type DailyClick = { day: string; clicks: number };

type CreatorRow = {
    collaboration_id: number;
    status: string;
    creator: { id: number; display_name: string | null };
    impressions: number;
    unique_clicks: number;
    qualified_clicks: number;
    leads_count: number;
    ctr: number | null;
};

type CampaignAnalytics = Metrics & {
    pipeline_cents: number;
    spend_cents: number;
    daily_clicks: DailyClick[];
};

export default function CampaignAnalytics({
    campaignId,
}: {
    campaignId: number;
}) {
    const [data, setData] = useState<CampaignAnalytics | null>(null);
    const [creators, setCreators] = useState<CreatorRow[]>([]);
    const [downloading, setDownloading] = useState(false);

    async function load() {
        const [{ data: analytics }, { data: creatorRows }] = await Promise.all([
            http.get<CampaignAnalytics>(
                companyApi.campaignAnalytics(campaignId),
            ),
            http.get<CreatorRow[]>(
                companyApi.campaignAnalyticsCreators(campaignId),
            ),
        ]);
        setData(analytics);
        setCreators(creatorRows);
    }

    useEffect(() => {
        load().catch((caught: unknown) => {
            toast.error(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not load analytics.',
            );
        });
    }, [campaignId]);

    const maxClicks = Math.max(
        1,
        ...(data?.daily_clicks.map((row) => row.clicks) ?? [0]),
    );

    async function downloadReport() {
        setDownloading(true);

        try {
            const { data: report } = await http.get(
                companyApi.campaignReport(campaignId),
            );
            const blob = new Blob([JSON.stringify(report, null, 2)], {
                type: 'application/json',
            });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `campaign-${campaignId}-report.json`;
            link.click();
            URL.revokeObjectURL(url);
        } catch (caught) {
            toast.error(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not download this report.',
            );
        } finally {
            setDownloading(false);
        }
    }

    const snapshot = data;

    return (
        <section className="grid gap-6">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <p className="text-sm text-muted-foreground">
                    Clicks, spend, and creator breakdown for this campaign.
                </p>
                <Button
                    type="button"
                    variant="outline"
                    className="rounded-pill"
                    disabled={downloading}
                    onClick={() => void downloadReport()}
                >
                    Download JSON
                </Button>
            </div>

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <SoftCard>
                    <MetricStat
                        value={formatNumber(snapshot?.impressions ?? 0)}
                        label="Reach"
                    />
                </SoftCard>
                <SoftCard>
                    <MetricStat
                        value={formatNumber(snapshot?.unique_clicks ?? 0)}
                        label="Unique clicks"
                        hint={
                            <span>
                                {formatNumber(snapshot?.clicks ?? 0)} total ·
                                CTR {pct(snapshot?.ctr ?? null)}
                            </span>
                        }
                    />
                </SoftCard>
                <SoftCard>
                    <MetricStat
                        value={euros(snapshot?.spend_cents ?? 0)}
                        label="Spend"
                    />
                </SoftCard>
                <SoftCard className="border-transparent bg-lime-soft">
                    <MetricStat
                        value={euros(snapshot?.pipeline_cents ?? 0)}
                        label="Pipeline"
                    />
                </SoftCard>
            </div>

            <SoftCard title="Clicks over time">
                <p className="mb-6 text-sm text-muted-foreground">
                    Daily clicks · last 12 days
                </p>
                <div className="flex h-40 items-end gap-2">
                    {(snapshot?.daily_clicks ?? Array.from({ length: 12 })).map(
                        (row, index) => {
                            const clicks =
                                typeof row === 'object' ? row.clicks : 0;

                            return (
                                <div
                                    key={
                                        typeof row === 'object'
                                            ? row.day
                                            : index
                                    }
                                    className="flex-1 rounded-sm bg-primary/70"
                                    style={{
                                        height: `${Math.max(8, (clicks / maxClicks) * 100)}%`,
                                    }}
                                />
                            );
                        },
                    )}
                </div>
                <div className="mt-3 flex justify-between text-xs text-muted-foreground">
                    <span>{snapshot?.daily_clicks[0]?.day ?? '—'}</span>
                    <span>{snapshot?.daily_clicks.at(-1)?.day ?? '—'}</span>
                </div>
            </SoftCard>

            <SoftCard title="By creator">
                {creators.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No attributed activity yet.
                    </p>
                ) : (
                    <div className="grid gap-3">
                        {creators.map((row) => (
                            <div
                                key={row.collaboration_id}
                                className="flex flex-wrap items-center justify-between gap-3 border-t border-border pt-3 first:border-t-0 first:pt-0"
                            >
                                <div>
                                    <p className="font-medium">
                                        {row.creator.display_name}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {row.status.replaceAll('_', ' ')}
                                    </p>
                                </div>
                                <p className="text-sm tabular-nums">
                                    {formatNumber(row.unique_clicks)} unique ·{' '}
                                    {formatNumber(row.qualified_clicks)}{' '}
                                    qualified · CTR {pct(row.ctr)}
                                </p>
                            </div>
                        ))}
                    </div>
                )}
            </SoftCard>
        </section>
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
