import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router';
import { ArrowLeft } from 'lucide-react';
import { toast } from 'sonner';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { ApiError, companyApi, http } from '@/lib/api';

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
    tracking_links: TrackingLink[];
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

    return (
        <div className="flex w-full flex-1 flex-col gap-6">
            <Button
                type="button"
                variant="ghost"
                className="w-fit px-0"
                onClick={() =>
                    void navigate(`/campaigns/${numericCampaignId}`)
                }
            >
                <ArrowLeft className="size-4" />
                Campaign
            </Button>
            <InputError message={error ?? undefined} />
            {post && (
                <>
                    <div className="flex items-center justify-between gap-3">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Review post
                        </h1>
                        <Badge variant="outline">
                            {post.status.replaceAll('_', ' ')}
                        </Badge>
                    </div>
                    <section className="border-border bg-card grid gap-3 rounded-2xl border p-5">
                        <h2 className="font-medium">Draft</h2>
                        <p className="whitespace-pre-wrap text-sm">
                            {post.body || 'No copy yet.'}
                        </p>
                    </section>
                    {post.guidelines && (
                        <section className="border-border bg-card grid gap-3 rounded-2xl border p-5">
                            <h2 className="font-medium">Guidelines</h2>
                            <p className="text-muted-foreground whitespace-pre-wrap text-sm">
                                {post.guidelines}
                            </p>
                        </section>
                    )}
                    {post.tracking_links.length > 0 && (
                        <section className="border-border bg-card grid gap-3 rounded-2xl border p-5">
                            <h2 className="font-medium">Tracking links</h2>
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
                                        onClick={() => void copy(link.short_url)}
                                    >
                                        Copy
                                    </Button>
                                </div>
                            ))}
                        </section>
                    )}
                    {post.status === 'in_review' && (
                        <section className="grid gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="review_note">Review note</Label>
                                <Textarea
                                    id="review_note"
                                    value={reviewNote}
                                    onChange={(event) =>
                                        setReviewNote(event.target.value)
                                    }
                                />
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <Button
                                    type="button"
                                    disabled={busy}
                                    onClick={() => void decide('approve')}
                                >
                                    Approve
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    disabled={busy || reviewNote.trim() === ''}
                                    onClick={() => void decide('changes')}
                                >
                                    Request changes
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    disabled={busy}
                                    onClick={() => void decide('reject')}
                                >
                                    Reject
                                </Button>
                            </div>
                        </section>
                    )}
                </>
            )}
        </div>
    );
}
