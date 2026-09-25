import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router';
import { ArrowLeft, BookOpen, Copy, FileText, MessageSquare } from 'lucide-react';
import { toast } from 'sonner';
import {
    CampaignBriefDialog,
    type CampaignBrief,
} from '@/components/campaign-brief-dialog';
import CollaborationThread from '@/components/collaboration-thread';
import ContractDocumentView from '@/components/contract-document';
import { InfoChip } from '@/components/info-chip';
import InputError from '@/components/input-error';
import { LinkedInPostBuilderDialog } from '@/components/linkedin/linkedin-post-builder-dialog';
import { LinkedInPostPreview } from '@/components/linkedin/linkedin-post-preview';
import { postStatusBadgeVariant } from '@/components/linkedin/post-status';
import type { MediaItem, PostReviewItem } from '@/components/media/types';
import { IconButton, MetricStat, SoftCard } from '@/components/ds';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { euros } from '@/company/pages/creators/format';
import { ApiError, creatorApi, http } from '@/lib/api';
import type { ContractDocument } from '@/lib/contract';
import { cn } from '@/lib/utils';

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
    media?: MediaItem[];
    reviews?: PostReviewItem[];
};

type DealDetail = {
    id: number;
    status: string;
    booked_price_cents: number | null;
    booked_posts_count: number | null;
    campaign: { id: number; name: string };
    company: { name: string | null };
    brief: CampaignBrief | null;
    goal: string | null;
    key_messages: string[] | null;
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
    const [messagesOpen, setMessagesOpen] = useState(false);
    const [briefOpen, setBriefOpen] = useState(false);
    const [builderPostId, setBuilderPostId] = useState<number | null>(null);
    const [contractOpen, setContractOpen] = useState(false);
    const [contract, setContract] = useState<ContractDocument | null>(null);
    const [contractError, setContractError] = useState<string | null>(null);
    const [contractLoading, setContractLoading] = useState(false);
    const [draftMedia, setDraftMedia] = useState<MediaItem[]>([]);

    const author = {
        name: window.Naano?.user?.name ?? 'You',
        headline: 'Creator',
        avatarUrl: window.Naano?.user?.avatar ?? null,
    };

    async function act(action: 'accept' | 'decline') {
        setBusy(true);
        setError(null);

        try {
            await http.post(
                action === 'accept'
                    ? creatorApi.collaborationAccept(dealId)
                    : creatorApi.collaborationDecline(dealId),
            );
            toast.success(
                action === 'accept'
                    ? 'Invite accepted'
                    : deal?.status === 'applied'
                      ? 'Application withdrawn'
                      : 'Invite declined',
            );
            await load();
        } catch (caught) {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not update this deal.',
            );
        } finally {
            setBusy(false);
        }
    }

    async function load() {
        const [{ data: nextDeal }, { data: nextPosts }, { data: nextMetrics }] =
            await Promise.all([
                http.get<DealDetail>(creatorApi.collaboration(dealId)),
                http.get<CreatorPost[]>(creatorApi.collaborationPosts(dealId)),
                http.get<DealMetrics>(creatorApi.collaborationMetrics(dealId)),
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
                media_ids: draftMedia.map((item) => item.id),
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

    async function submit(post: CreatorPost): Promise<boolean> {
        setBusy(true);
        setError(null);

        try {
            if (
                ['draft', 'changes_requested'].includes(post.status) &&
                (bodies[post.id] !== post.body ||
                    JSON.stringify(draftMedia.map((item) => item.id)) !==
                        JSON.stringify((post.media ?? []).map((item) => item.id)))
            ) {
                await http.patch(creatorApi.post(post.id), {
                    body: bodies[post.id] ?? '',
                    media_ids: draftMedia.map((item) => item.id),
                });
            }

            await http.post(creatorApi.postSubmit(post.id));
            toast.success('Submitted for review');
            await load();

            return true;
        } catch (caught) {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not submit this post.',
            );

            return false;
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
            const { data } = await http.post<CreatorPost>(
                creatorApi.collaborationPosts(dealId),
            );
            await load();
            setBodies((current) => ({
                ...current,
                [data.id]: data.body ?? '',
            }));
            setDraftMedia(data.media ?? []);
            setBuilderPostId(data.id);
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

    async function openContract() {
        setContractOpen(true);
        setContractLoading(true);
        setContractError(null);

        try {
            const { data } = await http.get<ContractDocument>(
                creatorApi.collaborationContract(dealId),
            );
            setContract(data);
        } catch (caught) {
            setContractError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not load the contract.',
            );
            setContract(null);
        } finally {
            setContractLoading(false);
        }
    }

    const builderPost =
        builderPostId === null
            ? null
            : (posts.find((post) => post.id === builderPostId) ?? null);
    const canEditBuilder =
        builderPost !== null &&
        ['draft', 'changes_requested'].includes(builderPost.status);

    return (
        <div className="flex w-full flex-1 flex-col gap-6">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <Button
                    type="button"
                    variant="ghost"
                    className="w-fit rounded-pill px-3"
                    onClick={() => void navigate('/deals')}
                >
                    <ArrowLeft className="size-4" />
                    Deals
                </Button>

                {deal ? (
                    <Button
                        type="button"
                        variant="outline"
                        className="rounded-pill lg:hidden"
                        onClick={() => setMessagesOpen((open) => !open)}
                    >
                        <MessageSquare className="size-4" />
                        {messagesOpen ? 'Hide messages' : 'Messages'}
                    </Button>
                ) : null}
            </div>

            <InputError message={error ?? undefined} />

            {!deal && !error ? (
                <p className="text-sm text-muted-foreground">Loading deal…</p>
            ) : null}

            {deal ? (
                <div className="flex flex-col gap-5 lg:flex-row lg:items-start">
                    <div className="flex min-w-0 flex-1 flex-col gap-6">
                        <header className="flex flex-wrap items-start justify-between gap-5">
                            <div className="min-w-0 space-y-3">
                                <h1 className="text-heading font-medium tracking-tight text-balance">
                                    {deal.campaign.name}
                                </h1>
                                <div className="flex flex-wrap items-center gap-2">
                                    <InfoChip>
                                        {deal.company.name ?? 'Company'}
                                    </InfoChip>
                                    <InfoChip>
                                        {titleCase(deal.status)}
                                    </InfoChip>
                                    {deal.booked_price_cents != null ? (
                                        <InfoChip>
                                            {euros(deal.booked_price_cents)}
                                        </InfoChip>
                                    ) : null}
                                    {deal.booked_posts_count != null ? (
                                        <InfoChip>
                                            {deal.booked_posts_count}{' '}
                                            {deal.booked_posts_count === 1
                                                ? 'post'
                                                : 'posts'}
                                        </InfoChip>
                                    ) : null}
                                </div>
                            </div>

                            <div className="flex flex-wrap gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    className="rounded-pill"
                                    onClick={() => setBriefOpen(true)}
                                >
                                    <BookOpen className="size-4" />
                                    Brief
                                </Button>
                                {deal.status === 'invited' ? (
                                    <>
                                        <Button
                                            type="button"
                                            variant="accent"
                                            className="rounded-pill"
                                            disabled={busy}
                                            onClick={() => void act('accept')}
                                        >
                                            Accept
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            className="rounded-pill"
                                            disabled={busy}
                                            onClick={() => void act('decline')}
                                        >
                                            Decline
                                        </Button>
                                    </>
                                ) : null}
                                {deal.status === 'applied' ? (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="rounded-pill"
                                        disabled={busy}
                                        onClick={() => void act('decline')}
                                    >
                                        Withdraw
                                    </Button>
                                ) : null}
                                {deal.status === 'booked' ? (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="rounded-pill"
                                        onClick={() => void openContract()}
                                    >
                                        <FileText className="size-4" />
                                        Contract
                                    </Button>
                                ) : null}
                            </div>
                        </header>

                        {metrics ? (
                            <section className="grid gap-5 sm:grid-cols-3">
                                <SoftCard>
                                    <MetricStat
                                        value={formatNumber(
                                            metrics.impressions,
                                        )}
                                        label="Impressions"
                                    />
                                </SoftCard>
                                <SoftCard>
                                    <MetricStat
                                        value={formatNumber(
                                            metrics.unique_clicks,
                                        )}
                                        label="Unique clicks"
                                        hint={`${formatNumber(metrics.clicks)} total · CTR ${pct(metrics.ctr)}`}
                                    />
                                </SoftCard>
                                <SoftCard>
                                    <MetricStat
                                        value={formatNumber(
                                            metrics.qualified_clicks,
                                        )}
                                        label="Qualified"
                                        hint={`${formatNumber(metrics.leads_count)} leads`}
                                    />
                                </SoftCard>
                            </section>
                        ) : null}

                        {hireLink ? (
                            <SoftCard title="LinkedIn CTA">
                                <div className="space-y-4">
                                    <p className="text-sm text-muted-foreground">
                                        Paste this unique link in the post CTA.
                                        Do not send visitors to the company
                                        website directly.
                                    </p>
                                    <div className="flex flex-wrap items-center gap-3 rounded-2xl border border-border bg-muted px-4 py-3">
                                        <p className="min-w-0 flex-1 font-mono text-sm break-all">
                                            {hireLink}
                                        </p>
                                        <IconButton
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            aria-label="Copy CTA link"
                                            onClick={() => void copy(hireLink)}
                                        >
                                            <Copy className="size-4" />
                                        </IconButton>
                                    </div>
                                </div>
                            </SoftCard>
                        ) : null}

                        <section className="space-y-5">
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <h2 className="text-title font-medium tracking-tight">
                                        Posts
                                    </h2>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        Draft, submit, and publish deliverables
                                        for this deal.
                                    </p>
                                </div>
                                {canCreate ? (
                                    <Button
                                        type="button"
                                        variant="accent"
                                        className="rounded-pill"
                                        disabled={busy}
                                        onClick={() => void createDraft()}
                                    >
                                        Write post
                                    </Button>
                                ) : null}
                            </div>

                            {posts.length === 0 ? (
                                <SoftCard>
                                    <p className="text-sm text-muted-foreground">
                                        No drafts yet.
                                        {canCreate
                                            ? ' Write a post to get started.'
                                            : ''}
                                    </p>
                                </SoftCard>
                            ) : (
                                <div className="grid grid-cols-2 gap-5 lg:grid-cols-4">
                                    {posts.map((post, index) => {
                                        const publishable = [
                                            'approved',
                                            'scheduled',
                                        ].includes(post.status);

                                        return (
                                            <div
                                                key={post.id}
                                                className="flex flex-col gap-3"
                                            >
                                                <button
                                                    type="button"
                                                    className="w-full rounded-2xl text-left transition-opacity hover:opacity-90"
                                                    onClick={() => {
                                                        setDraftMedia(
                                                            post.media ?? [],
                                                        );
                                                        setBuilderPostId(
                                                            post.id,
                                                        );
                                                    }}
                                                >
                                                    <div className="mb-3 flex items-center justify-between gap-2">
                                                        <p className="text-xs font-medium text-muted-foreground">
                                                            Post {index + 1}
                                                        </p>
                                                        <Badge
                                                            variant={postStatusBadgeVariant(
                                                                post.status,
                                                            )}
                                                        >
                                                            {titleCase(
                                                                post.status,
                                                            )}
                                                        </Badge>
                                                    </div>
                                                    <LinkedInPostPreview
                                                        author={author}
                                                        body={
                                                            bodies[post.id] ||
                                                            post.body ||
                                                            ''
                                                        }
                                                        media={post.media ?? []}
                                                        publishedUrl={
                                                            post.published_url
                                                        }
                                                        variant="compact"
                                                    />
                                                </button>

                                                {publishable ? (
                                                    <div className="space-y-3 rounded-2xl border border-border bg-card p-4">
                                                        {post.status ===
                                                        'approved' ? (
                                                            <div className="space-y-2">
                                                                <Label
                                                                    htmlFor={`schedule-${post.id}`}
                                                                >
                                                                    Schedule
                                                                </Label>
                                                                <Input
                                                                    id={`schedule-${post.id}`}
                                                                    type="datetime-local"
                                                                    className="rounded-sm"
                                                                    value={
                                                                        scheduledAt[
                                                                            post
                                                                                .id
                                                                        ] ?? ''
                                                                    }
                                                                    onChange={(
                                                                        event,
                                                                    ) =>
                                                                        setScheduledAt(
                                                                            (
                                                                                current,
                                                                            ) => ({
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
                                                                    size="sm"
                                                                    className="rounded-pill"
                                                                    disabled={
                                                                        busy ||
                                                                        !scheduledAt[
                                                                            post
                                                                                .id
                                                                        ]
                                                                    }
                                                                    onClick={() =>
                                                                        void schedule(
                                                                            post,
                                                                        )
                                                                    }
                                                                >
                                                                    Schedule
                                                                </Button>
                                                            </div>
                                                        ) : null}
                                                        <div className="space-y-2">
                                                            <Label
                                                                htmlFor={`live-${post.id}`}
                                                            >
                                                                Live URL
                                                            </Label>
                                                            <Input
                                                                id={`live-${post.id}`}
                                                                className="rounded-sm"
                                                                value={
                                                                    publishedUrl[
                                                                        post.id
                                                                    ] ?? ''
                                                                }
                                                                onChange={(
                                                                    event,
                                                                ) =>
                                                                    setPublishedUrl(
                                                                        (
                                                                            current,
                                                                        ) => ({
                                                                            ...current,
                                                                            [post.id]:
                                                                                event
                                                                                    .target
                                                                                    .value,
                                                                        }),
                                                                    )
                                                                }
                                                                placeholder="https://linkedin.com/posts/…"
                                                            />
                                                            <Button
                                                                type="button"
                                                                variant="accent"
                                                                size="sm"
                                                                className="rounded-pill"
                                                                disabled={
                                                                    busy ||
                                                                    (publishedUrl[
                                                                        post.id
                                                                    ] ??
                                                                        '') ===
                                                                        ''
                                                                }
                                                                onClick={() =>
                                                                    void publish(
                                                                        post,
                                                                    )
                                                                }
                                                            >
                                                                Publish
                                                            </Button>
                                                        </div>
                                                    </div>
                                                ) : null}
                                            </div>
                                        );
                                    })}
                                </div>
                            )}
                        </section>
                    </div>

                    <aside
                        className={cn(
                            'w-full shrink-0 lg:sticky lg:top-4 lg:block lg:w-88 lg:self-start',
                            messagesOpen ? 'block' : 'hidden',
                        )}
                    >
                        <SoftCard className="flex h-[min(36rem,70vh)] flex-col overflow-hidden rounded-2xl p-0 lg:h-[calc(100vh-10rem)]">
                            <div className="shrink-0 border-b border-border px-5 py-4">
                                <p className="text-sm font-medium">Messages</p>
                                <p className="mt-0.5 truncate text-xs text-muted-foreground">
                                    {deal.company.name ?? 'Company'}
                                </p>
                            </div>
                            <CollaborationThread
                                collaborationId={deal.id}
                                side="creator"
                                canSend={deal.status !== 'cancelled'}
                            />
                        </SoftCard>
                    </aside>

                    <CampaignBriefDialog
                        open={briefOpen}
                        onOpenChange={setBriefOpen}
                        title={deal.campaign.name}
                        companyName={deal.company.name}
                        brief={deal.brief}
                    />

                    {builderPost ? (
                        <LinkedInPostBuilderDialog
                            open={builderPostId !== null}
                            onOpenChange={(open) => {
                                if (!open) {
                                    setBuilderPostId(null);
                                }
                            }}
                            title={
                                canEditBuilder
                                    ? 'Create a post'
                                    : 'LinkedIn post'
                            }
                            statusLabel={titleCase(builderPost.status)}
                            status={builderPost.status}
                            description={
                                canEditBuilder
                                    ? 'Write your post, add media, and check the preview.'
                                    : 'Read-only view with review history.'
                            }
                            author={author}
                            value={
                                bodies[builderPost.id] ?? builderPost.body ?? ''
                            }
                            onChange={
                                canEditBuilder
                                    ? (value) =>
                                          setBodies((current) => ({
                                              ...current,
                                              [builderPost.id]: value,
                                          }))
                                    : undefined
                            }
                            media={draftMedia}
                            onMediaChange={
                                canEditBuilder ? setDraftMedia : undefined
                            }
                            reviews={builderPost.reviews ?? []}
                            publishedUrl={builderPost.published_url}
                            readOnly={!canEditBuilder}
                            saving={busy}
                            canSubmit={
                                (
                                    bodies[builderPost.id] ??
                                    builderPost.body ??
                                    ''
                                ).trim() !== ''
                            }
                            onSave={
                                canEditBuilder
                                    ? () => void save(builderPost)
                                    : undefined
                            }
                            onSubmit={
                                canEditBuilder
                                    ? () => {
                                          void submit(builderPost).then(
                                              (ok) => {
                                                  if (ok) {
                                                      setBuilderPostId(null);
                                                  }
                                              },
                                          );
                                      }
                                    : undefined
                            }
                        />
                    ) : null}

                    <Dialog open={contractOpen} onOpenChange={setContractOpen}>
                        <DialogContent className="flex max-h-[85vh] w-full flex-col gap-0 overflow-hidden rounded-sm border-border p-0 sm:max-w-2xl">
                            <DialogHeader className="shrink-0 space-y-1 border-b border-border px-6 py-5 pr-12 text-left">
                                <DialogTitle className="text-title font-medium tracking-tight">
                                    Contract
                                </DialogTitle>
                                <DialogDescription>
                                    {deal.campaign.name}
                                </DialogDescription>
                            </DialogHeader>
                            <div className="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                                {contractLoading ? (
                                    <p className="text-sm text-muted-foreground">
                                        Loading contract…
                                    </p>
                                ) : null}
                                {contractError ? (
                                    <p className="text-sm text-destructive">
                                        {contractError}
                                    </p>
                                ) : null}
                                {contract ? (
                                    <ContractDocumentView contract={contract} />
                                ) : null}
                            </div>
                        </DialogContent>
                    </Dialog>
                </div>
            ) : null}
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

function titleCase(value: string): string {
    return value
        .replaceAll('_', ' ')
        .replace(/^\w/, (letter) => letter.toUpperCase());
}
