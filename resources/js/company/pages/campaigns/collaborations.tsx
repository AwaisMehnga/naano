import { useState } from 'react';
import { UserPlus } from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { euros, initials } from '@/company/pages/creators/format';
import { cn } from '@/lib/utils';
import InviteCreatorDialog from './invite-dialog';
import { useCampaigns } from './store';
import { pipelineLabels, type PipelineTab } from './types';

const pipelines: PipelineTab[] = [
    'all',
    'active',
    'invitations_received',
    'invitations_sent',
    'todo',
    'completed',
];

export default function CampaignCollaborations({
    campaignId,
}: {
    campaignId: number;
}) {
    const {
        campaign,
        collaborations,
        pipeline,
        fetchCollaborations,
        select,
        cancelCollab,
    } = useCampaigns();
    const [inviteOpen, setInviteOpen] = useState(false);

    if (!campaign) {
        return null;
    }

    return (
        <div className="grid gap-8">
            <section className="grid gap-4">
                <div className="flex items-center justify-between gap-3">
                    <div>
                        <h2 className="text-lg font-semibold">Collaborations</h2>
                        <p className="text-muted-foreground text-sm">
                            Invites, applications, and the creators on this brief.
                        </p>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => setInviteOpen(true)}
                    >
                        <UserPlus className="size-4" />
                        Invite
                    </Button>
                </div>
                <div className="border-border flex flex-wrap gap-5 border-b">
                    {pipelines.map((tab) => (
                        <button
                            key={tab}
                            type="button"
                            className={cn(
                                'border-b-2 pb-3 text-sm',
                                pipeline === tab
                                    ? 'border-primary text-foreground font-medium'
                                    : 'text-muted-foreground border-transparent',
                            )}
                            onClick={() =>
                                void fetchCollaborations(campaignId, tab)
                            }
                        >
                            {pipelineLabels[tab]}
                            <span className="text-muted-foreground ml-2">
                                {campaign.collab_counts[tab]}
                            </span>
                        </button>
                    ))}
                </div>
                {collaborations.length === 0 ? (
                    <p className="text-muted-foreground py-10 text-sm">
                        No collaborations in this view.
                    </p>
                ) : (
                    <div className="grid gap-2">
                        {collaborations.map((row) => (
                            <div
                                key={row.id}
                                className="border-border flex flex-wrap items-center justify-between gap-3 rounded-xl border px-4 py-3"
                            >
                                <div className="flex min-w-0 items-center gap-3">
                                    <Avatar className="size-10">
                                        {row.creator.photo_url && (
                                            <AvatarImage
                                                src={row.creator.photo_url}
                                                alt=""
                                            />
                                        )}
                                        <AvatarFallback>
                                            {initials(row.creator.display_name)}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div className="min-w-0">
                                        <p className="truncate font-medium">
                                            {row.creator.display_name}
                                        </p>
                                        <p className="text-muted-foreground truncate text-xs">
                                            {row.source} · {row.status}
                                            {row.booked_price_cents
                                                ? ` · ${euros(row.booked_price_cents)}`
                                                : ''}
                                        </p>
                                    </div>
                                </div>
                                <div className="flex gap-2">
                                    {['invited', 'applied', 'outreach'].includes(
                                        row.status,
                                    ) && (
                                        <Button
                                            type="button"
                                            size="sm"
                                            onClick={() =>
                                                void select(campaignId, row.id)
                                            }
                                        >
                                            Shortlist
                                        </Button>
                                    )}
                                    {row.status !== 'cancelled' &&
                                        row.status !== 'completed' && (
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="ghost"
                                                onClick={() =>
                                                    void cancelCollab(
                                                        campaignId,
                                                        row.id,
                                                    )
                                                }
                                            >
                                                Cancel
                                            </Button>
                                        )}
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </section>
            <section className="grid gap-4">
                <h2 className="text-lg font-semibold">Shortlisted creators</h2>
                {campaign.shortlisted.length === 0 ? (
                    <p className="text-muted-foreground text-sm">
                        Shortlist an invited or applied creator to pin them here.
                    </p>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {campaign.shortlisted.map((creator) => (
                            <article
                                key={creator.collaboration_id}
                                className="border-border bg-card rounded-2xl border p-4"
                            >
                                <div className="flex items-center gap-3">
                                    <Avatar className="size-12">
                                        {creator.photo_url && (
                                            <AvatarImage
                                                src={creator.photo_url}
                                                alt=""
                                            />
                                        )}
                                        <AvatarFallback>
                                            {initials(creator.display_name)}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div className="min-w-0">
                                        <p className="truncate font-medium">
                                            {creator.display_name}
                                        </p>
                                        <p className="text-muted-foreground truncate text-sm">
                                            {creator.headline}
                                        </p>
                                    </div>
                                </div>
                                <p className="mt-4 text-sm">
                                    From {euros(creator.from_price_cents)}
                                </p>
                            </article>
                        ))}
                    </div>
                )}
            </section>
            <InviteCreatorDialog
                campaignId={campaignId}
                open={inviteOpen}
                onOpenChange={setInviteOpen}
            />
        </div>
    );
}
