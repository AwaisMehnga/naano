import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router';
import { ArrowLeft } from 'lucide-react';
import { toast } from 'sonner';
import InputError from '@/components/input-error';
import { LinkedInPostPreview } from '@/components/linkedin/linkedin-post-preview';
import {
    postStatusBadgeVariant,
    reviewActionSurface,
} from '@/components/linkedin/post-status';
import type { MediaItem, PostReviewItem } from '@/components/media/types';
import { SoftCard } from '@/components/ds';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { ApiError, companyApi, http } from '@/lib/api';
import { cn } from '@/lib/utils';

type TrackingLink = {
    id: number;
    short_url: string;
    destination_url: string;
};

type ReviewPost = {
    id: number;
    collaboration_id: number;
    status: string;
    body: string | null;
    review_note: string | null;
    guidelines: string | null;
    published_url: string | null;
    tracking_links: TrackingLink[];
    media?: MediaItem[];
    reviews?: PostReviewItem[];
    creator?: {
        display_name: string | null;
        photo_url: string | null;
        headline: string | null;
    };
};

export default function CompanyPostReviewPage() {
    const { campaignId, postId } = useParams();
    const navigate = useNavigate();
    const numericCampaignId = Number(campaignId);
    const numericPostId = Number(postId);
    const [post, setPost] = useState<ReviewPost | null>(null);
    const [reviewNote, setReviewNote] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);

    async function load() {
        const { data } = await http.get<ReviewPost>(
            companyApi.post(numericPostId),
        );
        setPost(data);
        setReviewNote(data.review_note ?? '');
    }

    useEffect(() => {
        if (!Number.isFinite(numericPostId) || numericPostId < 1) {
            return;
        }

        load().catch((caught: unknown) => {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not load this post.',
            );
        });
    }, [numericPostId]);

    async function decide(action: 'approve' | 'changes' | 'reject') {
        if (!post) {
            return;
        }

        setBusy(true);
        setError(null);

        try {
            if (action === 'approve') {
                await http.post(companyApi.postApprove(post.id));
            } else if (action === 'changes') {
                await http.post(companyApi.postChanges(post.id), {
                    review_note: reviewNote,
                });
            } else {
                await http.post(companyApi.postReject(post.id), {
                    review_note: reviewNote || undefined,
                });
            }

            toast.success(
                action === 'approve'
                    ? 'Post approved'
                    : action === 'changes'
                      ? 'Changes requested'
                      : 'Post rejected',
            );
            await load();
        } catch (caught) {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not update this post.',
            );
        } finally {
            setBusy(false);
        }
    }

    async function copy(url: string) {
        await navigator.clipboard.writeText(url);
        toast.success('Tracking link copied');
    }

    const authorName = post?.creator?.display_name ?? 'Creator';

    return (
        <div className="flex w-full flex-1 flex-col gap-6">
            <Button
                type="button"
                variant="ghost"
                className="w-fit rounded-pill px-3"
                onClick={() =>
                    void navigate(`/campaigns/${numericCampaignId}`)
                }
            >
                <ArrowLeft className="size-4" />
                Campaign
            </Button>
            <InputError message={error ?? undefined} />
            {post ? (
                <>
                    <header className="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h1 className="text-heading font-medium tracking-tight">
                                Review post
                            </h1>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {authorName}
                            </p>
                        </div>
                        <Badge variant={postStatusBadgeVariant(post.status)}>
                            {post.status.replaceAll('_', ' ')}
                        </Badge>
                    </header>

                    <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,22rem)]">
                        <LinkedInPostPreview
                            author={{
                                name: authorName,
                                headline: post.creator?.headline,
                                avatarUrl: post.creator?.photo_url,
                            }}
                            body={post.body ?? ''}
                            media={post.media ?? []}
                            publishedUrl={post.published_url}
                            variant="full"
                        />

                        <div className="space-y-5">
                            {post.published_url ? (
                                <SoftCard
                                    title="Live on LinkedIn"
                                    className="border-transparent bg-lime-soft"
                                >
                                    <a
                                        href={post.published_url}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="break-all text-sm font-medium text-lime-soft-foreground underline-offset-4 hover:underline"
                                    >
                                        {post.published_url}
                                    </a>
                                </SoftCard>
                            ) : null}

                            <SoftCard title="Review history">
                                {(post.reviews ?? []).length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        No review activity yet.
                                    </p>
                                ) : (
                                    <ul className="space-y-3">
                                        {(post.reviews ?? []).map((review) => (
                                            <li
                                                key={review.id}
                                                className={cn(
                                                    'rounded-2xl border px-4 py-3',
                                                    reviewActionSurface(
                                                        review.action,
                                                    ),
                                                )}
                                            >
                                                <p className="text-xs font-medium">
                                                    {review.action.replaceAll(
                                                        '_',
                                                        ' ',
                                                    )}
                                                </p>
                                                <p className="mt-0.5 text-xs text-muted-foreground">
                                                    {review.actor_name}
                                                    {review.created_at
                                                        ? ` · ${new Date(review.created_at).toLocaleString()}`
                                                        : ''}
                                                </p>
                                                {review.note ? (
                                                    <p className="mt-2 text-sm leading-5">
                                                        {review.note}
                                                    </p>
                                                ) : null}
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </SoftCard>

                            {post.guidelines ? (
                                <SoftCard title="Guidelines">
                                    <p className="whitespace-pre-wrap text-sm leading-7 text-muted-foreground">
                                        {post.guidelines}
                                    </p>
                                </SoftCard>
                            ) : null}

                            {post.tracking_links.length > 0 ? (
                                <SoftCard title="Tracking links">
                                    <div className="space-y-3">
                                        {post.tracking_links.map((link) => (
                                            <div
                                                key={link.id}
                                                className="flex flex-wrap items-center justify-between gap-2"
                                            >
                                                <p className="font-mono text-sm break-all">
                                                    {link.short_url}
                                                </p>
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant="outline"
                                                    className="rounded-pill"
                                                    onClick={() =>
                                                        void copy(link.short_url)
                                                    }
                                                >
                                                    Copy
                                                </Button>
                                            </div>
                                        ))}
                                    </div>
                                </SoftCard>
                            ) : null}

                            {post.status === 'in_review' ? (
                                <SoftCard title="Decision">
                                    <div className="space-y-4">
                                        <div className="space-y-2">
                                            <Label htmlFor="review_note">
                                                Review note
                                            </Label>
                                            <Textarea
                                                id="review_note"
                                                value={reviewNote}
                                                onChange={(event) =>
                                                    setReviewNote(
                                                        event.target.value,
                                                    )
                                                }
                                                className="min-h-28 rounded-2xl"
                                                placeholder="What should change, if anything…"
                                            />
                                        </div>
                                        <div className="flex flex-wrap gap-2">
                                            <Button
                                                type="button"
                                                variant="accent"
                                                disabled={busy}
                                                onClick={() =>
                                                    void decide('approve')
                                                }
                                            >
                                                Approve
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                disabled={
                                                    busy ||
                                                    reviewNote.trim() === ''
                                                }
                                                onClick={() =>
                                                    void decide('changes')
                                                }
                                            >
                                                Request changes
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                disabled={busy}
                                                onClick={() =>
                                                    void decide('reject')
                                                }
                                            >
                                                Reject
                                            </Button>
                                        </div>
                                    </div>
                                </SoftCard>
                            ) : null}
                        </div>
                    </div>
                </>
            ) : null}
        </div>
    );
}
