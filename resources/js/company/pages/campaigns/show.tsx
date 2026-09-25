import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router';
import { ArrowLeft } from 'lucide-react';
import { AppLink } from '@/components/app-link';
import { SoftCard } from '@/components/ds';
import InputError from '@/components/input-error';
import { InfoChip } from '@/components/info-chip';
import { LinkedInPostPreview } from '@/components/linkedin/linkedin-post-preview';
import { postStatusBadgeVariant } from '@/components/linkedin/post-status';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { euros } from '@/company/pages/creators/format';
import CampaignStatusSelect from './status-select';
import CampaignTracking from './tracking';
import { useCampaigns } from './store';
import {
    objectiveLabels,
    typeLabels,
    type CampaignPost,
} from './types';

export default function CompanyCampaignShowPage() {
    const { id } = useParams();
    const campaignId = Number(id);
    const navigate = useNavigate();
    const { campaign, loading, error, fetchCampaign } = useCampaigns();
    const [preview, setPreview] = useState<CampaignPost | null>(null);

    useEffect(() => {
        if (!Number.isFinite(campaignId) || campaignId < 1) {
            return;
        }

        void fetchCampaign(campaignId);
    }, [campaignId, fetchCampaign]);

    if (!Number.isFinite(campaignId) || campaignId < 1) {
        return (
            <div>
                <InputError message="This campaign does not exist." />
            </div>
        );
    }

    const posts = campaign?.posts ?? [];

    return (
        <div className="flex w-full flex-1 flex-col gap-8">
            <div className="flex flex-col gap-6">
                <Button
                    type="button"
                    variant="ghost"
                    className="w-fit px-0"
                    onClick={() => void navigate('/campaigns')}
                >
                    <ArrowLeft className="size-4" />
                    Campaigns
                </Button>

                {campaign ? (
                    <div className="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                        <div className="space-y-2">
                            <p className="text-sm text-muted-foreground">
                                Campaign
                            </p>
                            <h1 className="text-heading font-medium tracking-tight">
                                {campaign.name}
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                {typeLabels[campaign.type]} ·{' '}
                                {objectiveLabels[campaign.objective]}
                                {campaign.company_icp
                                    ? ` · ${campaign.company_icp.title}`
                                    : ''}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {dateRange(campaign.start_at, campaign.end_at)}{' '}
                                · {euros(campaign.budget_cents)}
                            </p>
                        </div>

                        <div className="flex flex-wrap items-center gap-2">
                            <Button
                                type="button"
                                className="rounded-pill"
                                asChild
                            >
                                <AppLink
                                    href={`/campaigns/${campaignId}/brief`}
                                >
                                    Edit brief
                                </AppLink>
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                className="rounded-pill"
                                asChild
                            >
                                <AppLink
                                    href={`/campaigns/${campaignId}/analytics`}
                                >
                                    View analytics
                                </AppLink>
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                className="rounded-pill"
                                asChild
                            >
                                <AppLink
                                    href={`/collaboration?campaign=${campaignId}`}
                                >
                                    Collaborations
                                </AppLink>
                            </Button>
                            <CampaignStatusSelect
                                campaignId={campaignId}
                                status={campaign.status}
                            />
                        </div>
                    </div>
                ) : null}
            </div>

            <InputError message={error ?? undefined} />

            {loading && campaign === null ? (
                <p className="text-sm text-muted-foreground">Loading…</p>
            ) : campaign ? (
                <>
                    <CampaignTracking campaignId={campaignId} />

                    <section className="space-y-4">
                        <div>
                            <h2 className="text-sm font-medium text-muted-foreground">
                                Posts
                            </h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Creator drafts and live posts for this campaign.
                            </p>
                        </div>

                        {posts.length === 0 ? (
                            <div className="rounded-3xl bg-muted p-6">
                                <p className="text-sm text-muted-foreground">
                                    No posts yet. Book a creator and wait for a
                                    draft.
                                </p>
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                                {posts.map((post) => (
                                    <button
                                        key={post.id}
                                        type="button"
                                        className="text-left"
                                        onClick={() => setPreview(post)}
                                    >
                                        <SoftCard className="flex h-full flex-col gap-4">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <p className="truncate text-sm font-medium">
                                                    {post.creator.display_name ??
                                                        'Creator'}
                                                </p>
                                                <Badge
                                                    variant={postStatusBadgeVariant(
                                                        post.status,
                                                    )}
                                                >
                                                    {post.status}
                                                </Badge>
                                            </div>
                                            <p className="line-clamp-4 text-sm text-muted-foreground">
                                                {post.body?.trim() ||
                                                    'No body yet.'}
                                            </p>
                                            <div className="mt-auto flex flex-wrap gap-1.5">
                                                {(post.media ?? []).length >
                                                0 ? (
                                                    <InfoChip>
                                                        {
                                                            (post.media ?? [])
                                                                .length
                                                        }{' '}
                                                        media
                                                    </InfoChip>
                                                ) : null}
                                                {post.published_url ? (
                                                    <InfoChip>
                                                        Published
                                                    </InfoChip>
                                                ) : null}
                                            </div>
                                        </SoftCard>
                                    </button>
                                ))}
                            </div>
                        )}
                    </section>
                </>
            ) : null}

            <Dialog
                open={preview !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setPreview(null);
                    }
                }}
            >
                <DialogContent className="max-h-[90vh] max-w-2xl overflow-y-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    {preview ? (
                        <>
                            <DialogHeader>
                                <DialogTitle>
                                    {preview.creator.display_name ?? 'Post'}
                                </DialogTitle>
                            </DialogHeader>
                            <LinkedInPostPreview
                                author={{
                                    name:
                                        preview.creator.display_name ??
                                        'Creator',
                                    avatarUrl: preview.creator.photo_url,
                                }}
                                body={preview.body ?? ''}
                                media={preview.media ?? []}
                                publishedUrl={preview.published_url}
                            />
                            <div className="flex justify-end">
                                <Button
                                    type="button"
                                    variant="outline"
                                    className="rounded-pill"
                                    asChild
                                >
                                    <AppLink
                                        href={`/campaigns/${campaignId}/posts/${preview.id}`}
                                    >
                                        Open review
                                    </AppLink>
                                </Button>
                            </div>
                        </>
                    ) : null}
                </DialogContent>
            </Dialog>
        </div>
    );
}

function dateRange(start: string | null, end: string | null): string {
    if (!start && !end) {
        return 'No dates';
    }

    const format = (value: string) =>
        new Date(value).toLocaleDateString('en-GB', {
            day: 'numeric',
            month: 'short',
        });

    if (start && end) {
        return `${format(start)} – ${format(end)}`;
    }

    return format(start ?? end ?? '');
}
