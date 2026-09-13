import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router';
import { ArrowLeft } from 'lucide-react';
import { toast } from 'sonner';
import { AppLink } from '@/components/app-link';
import CollaborationThread from '@/components/collaboration-thread';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { euros } from '@/company/pages/creators/format';
import { ApiError, creatorApi, http } from '@/lib/api';

type TrackingLink = {
    id: number;
    short_url: string;
};

type CreatorPost = {
    id: number;
    status: string;
    body: string | null;
    review_note: string | null;
    scheduled_at: string | null;
    published_url: string | null;
    tracking_links: TrackingLink[];
};

type DealDetail = {
    id: number;
    status: string;
    booked_price_cents: number | null;
    booked_posts_count: number | null;
    campaign: { id: number; name: string };
    company: { name: string | null };
    brief: { context?: string; key_message?: string } | null;
    guidelines: string | null;
};

type DealMetrics = {
    impressions: number;
    likes: number;
    comments: number;
    clicks: number;
    unique_clicks: number;
    qualified_clicks: number;
    leads_count: number;
    ctr: number | null;
};

export default function CreatorDealShowPage() {
    const { id } = useParams();
    const dealId = Number(id);
    const navigate = useNavigate();
    const [deal, setDeal] = useState<DealDetail | null>(null);
    const [metrics, setMetrics] = useState<DealMetrics | null>(null);
    const [posts, setPosts] = useState<CreatorPost[]>([]);
    const [bodies, setBodies] = useState<Record<number, string>>({});
    const [publishedUrl, setPublishedUrl] = useState<Record<number, string>>(
        {},
    );
    const [scheduledAt, setScheduledAt] = useState<Record<number, string>>({});
    const [error, setError] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);

    async function load() {
        const [{ data: nextDeal }, { data: nextPosts }, { data: nextMetrics }] =
            await Promise.all([
                http.get<DealDetail>(creatorApi.collaboration(dealId)),
                http.get<CreatorPost[]>(creatorApi.collaborationPosts(dealId)),
                http.get<DealMetrics>(
                    creatorApi.collaborationMetrics(dealId),
                ),
            ]);
        setDeal(nextDeal);
        setMetrics(nextMetrics);
        setPosts(nextPosts);
        setBodies(
            Object.fromEntries(
                nextPosts.map((post) => [post.id, post.body ?? '']),
            ),
        );
        setPublishedUrl(
            Object.fromEntries(
                nextPosts.map((post) => [post.id, post.published_url ?? '']),
            ),
        );
        setScheduledAt(
            Object.fromEntries(
                nextPosts.map((post) => [
                    post.id,
                    toLocalInput(post.scheduled_at),
                ]),
            ),
        );
    }

    useEffect(() => {
        if (!Number.isFinite(dealId) || dealId < 1) {
            return;
        }

        load().catch((caught: unknown) => {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not load this deal.',
            );
        });
    }, [dealId]);

    const cap = Math.max(1, deal?.booked_posts_count ?? 1);
    const canCreate = deal?.status === 'booked' && posts.length < cap;
    const hireLink = posts[0]?.tracking_links[0]?.short_url ?? null;

    async function copy(url: string) {
        await navigator.clipboard.writeText(url);
        toast.success('CTA copied');
    }

    async function save(post: CreatorPost) {
        setBusy(true);
        setError(null);

        try {
            await http.patch(creatorApi.post(post.id), {
                body: bodies[post.id] ?? '',
            });
            toast.success('Draft saved');
            await load();
        } catch (caught) {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not save this draft.',
            );
        } finally {
            setBusy(false);
        }
    }

    async function submit(post: CreatorPost) {
        setBusy(true);
        setError(null);

        try {
            if (
                ['draft', 'changes_requested'].includes(post.status) &&
                bodies[post.id] !== post.body
            ) {
                await http.patch(creatorApi.post(post.id), {
                    body: bodies[post.id] ?? '',
                });
            }

            await http.post(creatorApi.postSubmit(post.id));
            toast.success('Submitted for review');
            await load();
        } catch (caught) {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not submit this post.',
            );
        } finally {
            setBusy(false);
        }
    }

    async function schedule(post: CreatorPost) {
        setBusy(true);
        setError(null);

        try {
            await http.post(creatorApi.postSchedule(post.id), {
                scheduled_at: new Date(scheduledAt[post.id]).toISOString(),
            });
            toast.success('Post scheduled');
            await load();
        } catch (caught) {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not schedule this post.',
            );
        } finally {
            setBusy(false);
        }
    }

    async function publish(post: CreatorPost) {
        setBusy(true);
        setError(null);

        try {
            await http.post(creatorApi.postPublish(post.id), {
                published_url: publishedUrl[post.id],
            });
            toast.success('Live URL saved');
            await load();
        } catch (caught) {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not publish this post.',
            );
        } finally {
            setBusy(false);
        }
    }

    async function createDraft() {
        setBusy(true);
        setError(null);

        try {
            await http.post(creatorApi.collaborationPosts(dealId));
            await load();
        } catch (caught) {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not create a draft.',
            );
        } finally {
            setBusy(false);
        }
    }

    return (
        <div className="flex w-full flex-1 flex-col gap-6">
            <Button
                type="button"
                variant="ghost"
                className="w-fit px-0"
                onClick={() => void navigate('/deals')}
            >
                <ArrowLeft className="size-4" />
                Deals
            </Button>
            <InputError message={error ?? undefined} />
            {deal && (
                <>
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {deal.campaign.name}
                        </h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            {deal.company.name} · {deal.status}
                            {deal.booked_price_cents
                                ? ` · ${euros(deal.booked_price_cents)}`
                                : ''}
                        </p>
                    </div>
                    {deal.status === 'booked' && (
                        <Button type="button" variant="outline" asChild>
                            <AppLink href={`/collaborations/${deal.id}/contract`}>
                                Contract
                            </AppLink>
                        </Button>
                    )}
                    {metrics && (
                        <section className="grid gap-4 md:grid-cols-3">
                            <div className="border-border bg-card rounded-2xl border p-5">
                                <p className="text-muted-foreground text-sm">
                                    Impressions
                                </p>
                                <p className="mt-2 text-2xl font-semibold">
                                    {formatNumber(metrics.impressions)}
                                </p>
                            </div>
                            <div className="border-border bg-card rounded-2xl border p-5">
                                <p className="text-muted-foreground text-sm">
                                    Unique clicks
                                </p>
                                <p className="mt-2 text-2xl font-semibold">
                                    {formatNumber(metrics.unique_clicks)}
                                </p>
                                <p className="text-muted-foreground mt-1 text-sm">
                                    {formatNumber(metrics.clicks)} total · CTR{' '}
                                    {pct(metrics.ctr)}
                                </p>
                            </div>
                            <div className="border-border bg-card rounded-2xl border p-5">
                                <p className="text-muted-foreground text-sm">
                                    Qualified
                                </p>
                                <p className="mt-2 text-2xl font-semibold">
                                    {formatNumber(metrics.qualified_clicks)}
                                </p>
                                <p className="text-muted-foreground mt-1 text-sm">
                                    {formatNumber(metrics.leads_count)} leads
                                </p>
                            </div>
                        </section>
                    )}
                    {(deal.brief?.context || deal.guidelines) && (
                        <section className="border-border bg-card grid gap-3 rounded-2xl border p-5">
                            <h2 className="font-medium">Brief</h2>
                            {deal.brief?.key_message && (
                                <p className="text-sm">{deal.brief.key_message}</p>
                            )}
                            {deal.brief?.context && (
                                <p className="text-muted-foreground whitespace-pre-wrap text-sm">
                                    {deal.brief.context}
                                </p>
                            )}
                            {deal.guidelines && (
                                <p className="text-muted-foreground whitespace-pre-wrap text-sm">
                                    {deal.guidelines}
                                </p>
                            )}
                        </section>
                    )}
                    {hireLink && (
                        <section className="border-border bg-card grid gap-3 rounded-2xl border p-5">
                            <h2 className="font-medium">LinkedIn CTA</h2>
                            <p className="text-muted-foreground text-sm">
                                Paste this unique link in the post CTA. Do not
                                send visitors to the company website directly.
                            </p>
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <p className="font-mono text-sm break-all">
                                    {hireLink}
                                </p>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    onClick={() => void copy(hireLink)}
                                >
                                    Copy
                                </Button>
                            </div>
                        </section>
                    )}
                    <div className="flex items-center justify-between gap-3">
                        <h2 className="text-lg font-semibold">Posts</h2>
                        {canCreate && (
                            <Button
                                type="button"
                                variant="outline"
                                disabled={busy}
                                onClick={() => void createDraft()}
                            >
                                New draft
                            </Button>
                        )}
                    </div>
                    {posts.length === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            No drafts yet.
                        </p>
                    ) : (
                        posts.map((post) => (
                            <article
                                key={post.id}
                                className="border-border grid gap-4 rounded-2xl border p-5"
                            >
                                <div className="flex items-center justify-between gap-3">
                                    <h3 className="font-medium">Post</h3>
                                    <Badge variant="outline">
                                        {post.status.replaceAll('_', ' ')}
                                    </Badge>
                                </div>
                                {post.review_note && (
                                    <p className="text-muted-foreground text-sm">
                                        Reviewer: {post.review_note}
                                    </p>
                                )}
                                {['draft', 'changes_requested'].includes(
                                    post.status,
                                ) ? (
                                    <>
                                        <Textarea
                                            value={bodies[post.id] ?? ''}
                                            onChange={(event) =>
                                                setBodies((current) => ({
                                                    ...current,
                                                    [post.id]: event.target.value,
                                                }))
                                            }
                                        />
                                        <div className="flex flex-wrap gap-2">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                disabled={busy}
                                                onClick={() => void save(post)}
                                            >
                                                Save
                                            </Button>
                                            <Button
                                                type="button"
                                                disabled={busy}
                                                onClick={() => void submit(post)}
                                            >
                                                Submit
                                            </Button>
                                        </div>
                                    </>
                                ) : (
                                    <p className="whitespace-pre-wrap text-sm">
                                        {post.body}
                                    </p>
                                )}
                                {['approved', 'scheduled'].includes(
                                    post.status,
                                ) && (
                                    <div className="grid gap-4">
                                        {post.status === 'approved' && (
                                            <div className="grid gap-2">
                                                <Label
                                                    htmlFor={`schedule-${post.id}`}
                                                >
                                                    Schedule
                                                </Label>
                                                <div className="flex flex-wrap gap-2">
                                                    <Input
                                                        id={`schedule-${post.id}`}
                                                        type="datetime-local"
                                                        value={
                                                            scheduledAt[post.id] ??
                                                            ''
                                                        }
                                                        onChange={(event) =>
                                                            setScheduledAt(
                                                                (current) => ({
                                                                    ...current,
                                                                    [post.id]:
                                                                        event
                                                                            .target
                                                                            .value,
                                                                }),
                                                            )
                                                        }
                                                    />
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        disabled={
                                                            busy ||
                                                            !scheduledAt[post.id]
                                                        }
                                                        onClick={() =>
                                                            void schedule(post)
                                                        }
                                                    >
                                                        Schedule
                                                    </Button>
                                                </div>
                                            </div>
                                        )}
                                        <div className="grid gap-2">
                                            <Label htmlFor={`live-${post.id}`}>
                                                Live LinkedIn URL
                                            </Label>
                                            <Input
                                                id={`live-${post.id}`}
                                                value={
                                                    publishedUrl[post.id] ?? ''
                                                }
                                                onChange={(event) =>
                                                    setPublishedUrl(
                                                        (current) => ({
                                                            ...current,
                                                            [post.id]:
                                                                event.target
                                                                    .value,
                                                        }),
                                                    )
                                                }
                                            />
                                            <Button
                                                type="button"
                                                disabled={
                                                    busy ||
                                                    (publishedUrl[post.id] ??
                                                        '') === ''
                                                }
                                                onClick={() => void publish(post)}
                                            >
                                                Publish
                                            </Button>
                                        </div>
                                    </div>
                                )}
                            </article>
                        ))
                    )}
                    <section className="border-border bg-card grid gap-3 rounded-2xl border p-5">
                        <h2 className="font-medium">Messages</h2>
                        <CollaborationThread
                            collaborationId={deal.id}
                            side="creator"
                            canSend={deal.status !== 'cancelled'}
                        />
                    </section>
                </>
            )}
        </div>
    );
}

function toLocalInput(iso: string | null): string {
    if (!iso) {
        return '';
    }

    const date = new Date(iso);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    const pad = (value: number) => String(value).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
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
