import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { AppLink } from '@/components/app-link';
import { LinkedInPostPreview } from '@/components/linkedin/linkedin-post-preview';
import { postStatusBadgeVariant } from '@/components/linkedin/post-status';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { euros } from '@/company/pages/creators/format';
import { ApiError, companyApi, http } from '@/lib/api';
import type { CampaignPost } from './types';

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

type LeadRow = {
    id: number;
    source: string;
    occurred_at: string | null;
    payload: { pipeline_cents?: number; note?: string } | null;
};

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
    posts: (CampaignPost & { metrics: Metrics })[];
    leads: LeadRow[];
};

export default function CampaignAnalytics({
    campaignId,
    leadsCount,
    posts,
}: {
    campaignId: number;
    leadsCount: number;
    posts: CampaignPost[];
}) {
    const [data, setData] = useState<CampaignAnalytics | null>(null);
    const [creators, setCreators] = useState<CreatorRow[]>([]);
    const [pipeline, setPipeline] = useState<Record<number, string>>({});
    const [note, setNote] = useState('');
    const [downloading, setDownloading] = useState(false);

    async function load() {
        const [{ data: analytics }, { data: creatorRows }] = await Promise.all([
            http.get<CampaignAnalytics>(companyApi.campaignAnalytics(campaignId)),
            http.get<CreatorRow[]>(companyApi.campaignAnalyticsCreators(campaignId)),
        ]);
        setData(analytics);
        setCreators(creatorRows);
        setPipeline(
            Object.fromEntries(
                analytics.leads.map((lead) => [
                    lead.id,
                    lead.payload?.pipeline_cents
                        ? String(lead.payload.pipeline_cents / 100)
                        : '',
                ]),
            ),
        );
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

    async function addLead() {
        try {
            await http.post(companyApi.campaignLeads(campaignId), {
                source: 'manual',
                payload: { note },
            });
            setNote('');
            toast.success('Lead added');
            await load();
        } catch (caught) {
            toast.error(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not add this lead.',
            );
        }
    }

    async function downloadReport() {
        setDownloading(true);

        try {
            const { data: report } = await http.get<CampaignAnalytics>(
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

    async function savePipeline(leadId: number) {
        const eurosValue = Number(pipeline[leadId]);
        const cents = Number.isFinite(eurosValue)
            ? Math.round(eurosValue * 100)
            : 0;

        try {
            await http.patch(companyApi.lead(leadId), {
                payload: { pipeline_cents: cents },
            });
            toast.success('Pipeline saved');
            await load();
        } catch (caught) {
            toast.error(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not update pipeline.',
            );
        }
    }

    const snapshot = data;
    const postRows: Array<CampaignPost & { metrics?: Metrics }> =
        snapshot?.posts.length ? snapshot.posts : posts;

    return (
        <section className="grid gap-6">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 className="text-lg font-semibold">Analytics</h2>
                    <p className="text-muted-foreground text-sm">
                        Unique clicks, CTR, qualified visits, and attributed
                        pipeline for this campaign.
                    </p>
                </div>
                <Button
                    type="button"
                    variant="outline"
                    disabled={downloading}
                    onClick={() => void downloadReport()}
                >
                    Download JSON
                </Button>
            </div>
            <div className="grid gap-4 md:grid-cols-3">
                <StatCard
                    label="Impressions"
                    value={formatNumber(snapshot?.impressions ?? 0)}
                    hint="LinkedIn ingest"
                />
                <StatCard
                    label="Unique clicks"
                    value={formatNumber(snapshot?.unique_clicks ?? 0)}
                    hint={`${formatNumber(snapshot?.clicks ?? 0)} total · CTR ${pct(snapshot?.ctr ?? null)}`}
                />
                <StatCard
                    label="Qualified clicks"
                    value={formatNumber(snapshot?.qualified_clicks ?? 0)}
                    hint="Pixel dwell ≥ 30s"
                />
            </div>
            <div className="grid gap-4 md:grid-cols-3">
                <StatCard
                    label="Spend"
                    value={euros(snapshot?.spend_cents ?? 0)}
                    hint="Captured holds"
                />
                <StatCard
                    label="Pipeline"
                    value={euros(snapshot?.pipeline_cents ?? 0)}
                    hint="Attributed lead value"
                />
                <StatCard
                    label="Leads"
                    value={String(snapshot?.leads_count ?? leadsCount)}
                    hint="Form + manual"
                />
            </div>
            <div className="border-border bg-card rounded-2xl border p-5">
                <h3 className="font-medium">Performance over time</h3>
                <p className="text-muted-foreground mb-6 text-sm">
                    Daily clicks · last 12 days
                </p>
                <div className="flex h-40 items-end gap-2">
                    {(snapshot?.daily_clicks ?? Array.from({ length: 12 })).map(
                        (row, index) => {
                            const clicks =
                                typeof row === 'object' ? row.clicks : 0;

                            return (
                                <div
                                    key={typeof row === 'object' ? row.day : index}
                                    className="bg-primary/70 rounded-sm"
                                    style={{
                                        height: `${Math.max(8, (clicks / maxClicks) * 100)}%`,
                                        flex: 1,
                                    }}
                                />
                            );
                        },
                    )}
                </div>
                <div className="text-muted-foreground mt-3 flex justify-between text-xs">
                    <span>
                        {snapshot?.daily_clicks[0]?.day ?? '—'}
                    </span>
                    <span>
                        {snapshot?.daily_clicks.at(-1)?.day ?? '—'}
                    </span>
                </div>
            </div>
            <div className="border-border bg-card grid gap-4 rounded-2xl border p-5">
                <div>
                    <h3 className="font-medium">Post performance</h3>
                    <p className="text-muted-foreground text-sm">
                        Latest metrics collected from tracking hops and the
                        pixel.
                    </p>
                </div>
                <div className="grid grid-cols-3 gap-4 text-center">
                    <MiniStat
                        label="Posts"
                        value={String(postRows.length)}
                    />
                    <MiniStat
                        label="reactions"
                        value={formatNumber(snapshot?.likes ?? 0)}
                    />
                    <MiniStat
                        label="comments"
                        value={formatNumber(snapshot?.comments ?? 0)}
                    />
                </div>
            </div>
            <div className="border-border bg-card rounded-2xl border p-5">
                <h3 className="font-medium">Attribution by creator</h3>
                {creators.length === 0 ? (
                    <p className="text-muted-foreground mt-6 text-sm">
                        No attributed activity yet.
                    </p>
                ) : (
                    <div className="mt-4 grid gap-3">
                        {creators.map((row) => (
                            <div
                                key={row.collaboration_id}
                                className="border-border flex flex-wrap items-center justify-between gap-3 border-t pt-3 first:border-t-0 first:pt-0"
                            >
                                <div>
                                    <p className="font-medium">
                                        {row.creator.display_name}
                                    </p>
                                    <p className="text-muted-foreground text-xs">
                                        {row.status.replaceAll('_', ' ')}
                                    </p>
                                </div>
                                <p className="text-sm">
                                    {formatNumber(row.unique_clicks)} unique ·{' '}
                                    {formatNumber(row.qualified_clicks)}{' '}
                                    qualified · {row.leads_count} leads · CTR{' '}
                                    {pct(row.ctr)}
                                </p>
                            </div>
                        ))}
                    </div>
                )}
            </div>
            <div className="grid gap-4">
                <h3 className="text-lg font-semibold">Posts</h3>
                {postRows.length === 0 ? (
                    <p className="text-muted-foreground text-sm">
                        No posts on this campaign yet.
                    </p>
                ) : (
                    <div className="grid grid-cols-2 gap-5 lg:grid-cols-4">
                        {postRows.map((post) => (
                            <AppLink
                                key={post.id}
                                href={`/campaigns/${campaignId}/posts/${post.id}`}
                                className="flex flex-col gap-3 transition-opacity hover:opacity-90"
                            >
                                <div className="flex items-center justify-between gap-2">
                                    <p className="truncate text-xs font-medium text-muted-foreground">
                                        {post.creator.display_name ?? 'Creator'}
                                    </p>
                                    <Badge
                                        variant={postStatusBadgeVariant(
                                            post.status,
                                        )}
                                    >
                                        {post.status.replaceAll('_', ' ')}
                                    </Badge>
                                </div>
                                <LinkedInPostPreview
                                    author={{
                                        name:
                                            post.creator.display_name ??
                                            'Creator',
                                        avatarUrl: post.creator.photo_url,
                                    }}
                                    body={post.body ?? ''}
                                    media={post.media ?? []}
                                    publishedUrl={post.published_url}
                                    variant="compact"
                                />
                            </AppLink>
                        ))}
                    </div>
                )}
            </div>
            <div className="border-border bg-card grid gap-4 rounded-2xl border p-5">
                <h3 className="font-medium">Leads</h3>
                <form
                    className="flex flex-wrap gap-2"
                    onSubmit={(event) => {
                        event.preventDefault();
                        void addLead();
                    }}
                >
                    <Input
                        value={note}
                        onChange={(event) => setNote(event.target.value)}
                        placeholder="Manual lead note"
                    />
                    <Button type="submit" disabled={note.trim() === ''}>
                        Add lead
                    </Button>
                </form>
                {(snapshot?.leads ?? []).length === 0 ? (
                    <p className="text-muted-foreground text-sm">
                        No attributed leads yet.
                    </p>
                ) : (
                    snapshot?.leads.map((lead) => (
                        <div
                            key={lead.id}
                            className="border-border grid gap-2 border-t pt-3"
                        >
                            <p className="text-sm">
                                {lead.source} ·{' '}
                                {lead.payload?.note ?? 'No note'}
                            </p>
                            <div className="flex flex-wrap items-end gap-2">
                                <div className="grid gap-1">
                                    <Label htmlFor={`pipeline-${lead.id}`}>
                                        Pipeline €
                                    </Label>
                                    <Input
                                        id={`pipeline-${lead.id}`}
                                        value={pipeline[lead.id] ?? ''}
                                        onChange={(event) =>
                                            setPipeline((current) => ({
                                                ...current,
                                                [lead.id]: event.target.value,
                                            }))
                                        }
                                    />
                                </div>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => void savePipeline(lead.id)}
                                >
                                    Save
                                </Button>
                            </div>
                        </div>
                    ))
                )}
            </div>
        </section>
    );
}

function StatCard({
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

function MiniStat({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <p className="text-2xl font-semibold">{value}</p>
            <p className="text-muted-foreground text-xs">{label}</p>
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
