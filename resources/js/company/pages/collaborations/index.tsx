import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router';
import { UserPlus } from 'lucide-react';
import { toast } from 'sonner';
import { AppLink } from '@/components/app-link';
import CollaborationThread from '@/components/collaboration-thread';
import { ConfirmDialog } from '@/components/confirm-dialog';
import {
    DataTable,
    type DataTableQuery,
} from '@/components/data-table';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import InviteCreatorDialog from '@/company/pages/campaigns/invite-dialog';
import { useCampaigns } from '@/company/pages/campaigns/store';
import {
    pipelineLabels,
    type CampaignList,
    type CampaignListItem,
    type CollabCounts,
    type CollaborationList,
    type CollaborationRow,
    type PipelineTab,
} from '@/company/pages/campaigns/types';
import { euros, initials } from '@/company/pages/creators/format';
import { ApiError, companyApi, http } from '@/lib/api';
import { cn } from '@/lib/utils';

const pipelines: PipelineTab[] = [
    'all',
    'active',
    'invitations_received',
    'invitations_sent',
    'todo',
    'completed',
];

const emptyCounts: CollabCounts = {
    all: 0,
    active: 0,
    invitations_received: 0,
    invitations_sent: 0,
    todo: 0,
    completed: 0,
};

export default function CompanyCollaborationsPage() {
    const [searchParams, setSearchParams] = useSearchParams();
    const campaignParam = searchParams.get('campaign');
    const campaignId = Number(campaignParam);
    const hasCampaign = Number.isFinite(campaignId) && campaignId > 0;
    const { campaign, fetchCampaign, select, book, cancelCollab } =
        useCampaigns();
    const [pipeline, setPipeline] = useState<PipelineTab>('all');
    const [query, setQuery] = useState<DataTableQuery>({
        page: 1,
        per_page: 25,
        q: '',
        filters: { campaign: campaignParam },
    });
    const [page, setPage] = useState<CollaborationList | null>(null);
    const [campaigns, setCampaigns] = useState<CampaignListItem[]>([]);
    const [loading, setLoading] = useState(false);
    const [canManage, setCanManage] = useState(false);
    const [inviteOpen, setInviteOpen] = useState(false);
    const [threadId, setThreadId] = useState<number | null>(null);
    const [pendingCancel, setPendingCancel] = useState<CollaborationRow | null>(
        null,
    );
    const [cancelling, setCancelling] = useState(false);
    const threadRow = (page?.data ?? []).find((row) => row.id === threadId);

    useEffect(() => {
        http.get<{ can_manage_money: boolean }>(companyApi.profile)
            .then(({ data }) => setCanManage(data.can_manage_money))
            .catch(() => undefined);

        http.get<CampaignList>(companyApi.campaigns({ per_page: 50 }))
            .then(({ data }) => setCampaigns(data.data))
            .catch(() => setCampaigns([]));
    }, []);

    useEffect(() => {
        if (hasCampaign) {
            void fetchCampaign(campaignId);
        }
    }, [campaignId, fetchCampaign, hasCampaign]);

    useEffect(() => {
        const next = query.filters.campaign;
        const current = searchParams.get('campaign');

        if (next === current || (next === null && current === null)) {
            return;
        }

        const params = new URLSearchParams(searchParams);

        if (next) {
            params.set('campaign', next);
        } else {
            params.delete('campaign');
        }

        setSearchParams(params, { replace: true });
    }, [query.filters.campaign, searchParams, setSearchParams]);

    useEffect(() => {
        const campaignFilter = campaignParam;
        setQuery((current) =>
            current.filters.campaign === campaignFilter
                ? current
                : {
                      ...current,
                      page: 1,
                      filters: { ...current.filters, campaign: campaignFilter },
                  },
        );
    }, [campaignParam]);

    async function load() {
        setLoading(true);

        try {
            const { data } = await http.get<CollaborationList>(
                companyApi.collaborations({
                    pipeline,
                    campaign_id: query.filters.campaign || undefined,
                    q: query.q || undefined,
                    page: query.page,
                    per_page: query.per_page,
                }),
            );
            setPage(data);
        } catch (caught) {
            toast.error(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not load collaborations.',
            );
        } finally {
            setLoading(false);
        }
    }

    useEffect(() => {
        void load();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [pipeline, query]);

    async function reload() {
        await load();

        if (hasCampaign) {
            await fetchCampaign(campaignId);
        }
    }

    async function bookRow(row: CollaborationRow) {
        const id = row.campaign?.id ?? campaignId;

        try {
            await book(id, row.id);
            toast.success('Creator booked');
            await reload();
        } catch (caught) {
            const data =
                caught instanceof ApiError
                    ? (caught.payload.data as { checkout_url?: string })
                    : null;

            if (data?.checkout_url) {
                window.location.href = data.checkout_url;

                return;
            }

            toast.error(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not book this creator.',
            );
        }
    }

    async function confirmCancel() {
        if (pendingCancel === null) {
            return;
        }

        const id = pendingCancel.campaign?.id ?? campaignId;
        setCancelling(true);

        try {
            await cancelCollab(id, pendingCancel.id);
            toast.success('Collaboration cancelled');
            setPendingCancel(null);
            await reload();
        } catch (caught) {
            toast.error(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not cancel this collaboration.',
            );
        } finally {
            setCancelling(false);
        }
    }

    const counts = page?.counts ?? campaign?.collab_counts ?? emptyCounts;
    const selectedCampaignId = Number(query.filters.campaign);

    return (
        <div className="flex w-full flex-1 flex-col gap-6">
            <div className="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Collaborations
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Invites, applications, and booked creators.
                    </p>
                </div>
                {Number.isFinite(selectedCampaignId) &&
                    selectedCampaignId > 0 && (
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setInviteOpen(true)}
                        >
                            <UserPlus className="size-4" />
                            Invite
                        </Button>
                    )}
            </div>
            <div className="flex flex-wrap gap-5 border-b border-border">
                {pipelines.map((tab) => (
                    <button
                        key={tab}
                        type="button"
                        className={cn(
                            'border-b-2 pb-3 text-sm',
                            pipeline === tab
                                ? 'border-primary font-medium text-foreground'
                                : 'border-transparent text-muted-foreground',
                        )}
                        onClick={() => {
                            setPipeline(tab);
                            setQuery((current) => ({ ...current, page: 1 }));
                        }}
                    >
                        {pipelineLabels[tab]}
                        <span className="ml-2 text-muted-foreground">
                            {counts[tab]}
                        </span>
                    </button>
                ))}
            </div>
            <DataTable
                page={page}
                query={query}
                onQueryChange={setQuery}
                loading={loading}
                searchPlaceholder="Search creators or campaigns"
                empty="No collaborations in this view."
                rowKey={(row) => row.id}
                filterGroups={[
                    {
                        label: 'Campaign',
                        filters: [
                            {
                                key: 'campaign',
                                label: 'Campaign',
                                options: campaigns.map((item) => ({
                                    value: String(item.id),
                                    label: item.name,
                                })),
                            },
                        ],
                    },
                ]}
                columns={[
                    {
                        key: 'creator',
                        header: 'Creator',
                        cell: (row) => (
                            <div className="flex min-w-0 items-center gap-3">
                                <Avatar className="size-9">
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
                                    <p className="truncate text-xs text-muted-foreground">
                                        {row.source} · {row.status}
                                    </p>
                                </div>
                            </div>
                        ),
                    },
                    {
                        key: 'campaign',
                        header: 'Campaign',
                        cell: (row) => row.campaign?.name ?? '—',
                    },
                    {
                        key: 'price',
                        header: 'Price',
                        cell: (row) =>
                            row.booked_price_cents
                                ? euros(row.booked_price_cents)
                                : euros(row.creator.from_price_cents),
                    },
                    {
                        key: 'actions',
                        header: '',
                        cell: (row) => {
                            const id = row.campaign?.id ?? campaignId;

                            return (
                                <div className="flex flex-wrap justify-end gap-2">
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        onClick={() => setThreadId(row.id)}
                                    >
                                        Message
                                    </Button>
                                    {row.review_post_id && (
                                        <Button type="button" size="sm" asChild>
                                            <AppLink
                                                href={`/campaigns/${id}/posts/${row.review_post_id}`}
                                            >
                                                Review
                                            </AppLink>
                                        </Button>
                                    )}
                                    {[
                                        'invited',
                                        'applied',
                                        'outreach',
                                    ].includes(row.status) && (
                                        <Button
                                            type="button"
                                            size="sm"
                                            onClick={() =>
                                                void select(id, row.id).then(
                                                    () => reload(),
                                                )
                                            }
                                        >
                                            Shortlist
                                        </Button>
                                    )}
                                    {canManage &&
                                        row.status === 'selected' && (
                                            <Button
                                                type="button"
                                                size="sm"
                                                onClick={() =>
                                                    void bookRow(row)
                                                }
                                            >
                                                Book
                                            </Button>
                                        )}
                                    {row.status === 'booked' && (
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="outline"
                                            asChild
                                        >
                                            <AppLink
                                                href={`/collaborations/${row.id}/contract`}
                                            >
                                                Contract
                                            </AppLink>
                                        </Button>
                                    )}
                                    {row.status !== 'cancelled' &&
                                        row.status !== 'completed' &&
                                        !row.has_published_post && (
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="ghost"
                                                onClick={() =>
                                                    setPendingCancel(row)
                                                }
                                            >
                                                Cancel
                                            </Button>
                                        )}
                                </div>
                            );
                        },
                    },
                ]}
            />
            {campaign &&
                hasCampaign &&
                campaign.id === campaignId && (
                    <section className="grid gap-4">
                        <h2 className="text-lg font-semibold">
                            Shortlisted creators
                        </h2>
                        {campaign.shortlisted.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Shortlist an invited or applied creator to pin
                                them here.
                            </p>
                        ) : (
                            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                {campaign.shortlisted.map((creator) => (
                                    <article
                                        key={creator.collaboration_id}
                                        className="rounded-lg border border-border bg-card p-4"
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
                                                    {initials(
                                                        creator.display_name,
                                                    )}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div className="min-w-0">
                                                <p className="truncate font-medium">
                                                    {creator.display_name}
                                                </p>
                                                <p className="truncate text-sm text-muted-foreground">
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
                )}
            {Number.isFinite(selectedCampaignId) &&
                selectedCampaignId > 0 && (
                    <InviteCreatorDialog
                        campaignId={selectedCampaignId}
                        open={inviteOpen}
                        onOpenChange={(open) => {
                            setInviteOpen(open);

                            if (!open) {
                                void reload();
                            }
                        }}
                    />
                )}
            <Dialog
                open={threadId !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setThreadId(null);
                    }
                }}
            >
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>
                            {threadRow?.creator.display_name ?? 'Messages'}
                        </DialogTitle>
                    </DialogHeader>
                    {threadId !== null && (
                        <CollaborationThread
                            collaborationId={threadId}
                            side="company"
                            canSend={threadRow?.status !== 'cancelled'}
                        />
                    )}
                </DialogContent>
            </Dialog>
            <ConfirmDialog
                open={pendingCancel !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setPendingCancel(null);
                    }
                }}
                title="Cancel this collaboration?"
                description="The creator will be removed from this campaign. This cannot be undone from the pipeline."
                confirmLabel="Cancel collaboration"
                pending={cancelling}
                onConfirm={confirmCancel}
            />
        </div>
    );
}
