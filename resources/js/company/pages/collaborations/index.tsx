import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router';
import { Layers, UserPlus } from 'lucide-react';
import { toast } from 'sonner';
import { AppLink } from '@/components/app-link';
import CollaborationChatSheet from '@/components/collaboration-chat-sheet';
import { ConfirmDialog } from '@/components/confirm-dialog';
import {
    DataTable,
    type DataTableQuery,
} from '@/components/data-table';
import { FilterDropdown } from '@/components/filter-select';
import { SearchPill, SegmentedNav } from '@/components/ds';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
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
import CreatorProfileDialog from '@/company/pages/creators/creator-profile-dialog';
import { euros, initials } from '@/company/pages/creators/format';
import type { CreatorListItem } from '@/company/pages/creators/types';
import { canManageMoney } from '@/lib/current-user';
import { ApiError, companyApi, http } from '@/lib/api';
import {
    isShortlisted,
    readShortlist,
    toggleShortlist,
} from '@/lib/creator-shortlist';

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
    const [draftQuery, setDraftQuery] = useState('');
    const [query, setQuery] = useState<DataTableQuery>({
        page: 1,
        per_page: 25,
        q: '',
        filters: { campaign: campaignParam },
    });
    const [page, setPage] = useState<CollaborationList | null>(null);
    const [campaigns, setCampaigns] = useState<CampaignListItem[]>([]);
    const [loading, setLoading] = useState(false);
    const canManage = canManageMoney();
    const [inviteOpen, setInviteOpen] = useState(false);
    const [threadId, setThreadId] = useState<number | null>(null);
    const [openId, setOpenId] = useState<number | null>(null);
    const [shortlist, setShortlist] = useState<CreatorListItem[]>(() =>
        readShortlist(),
    );
    const [pendingCancel, setPendingCancel] = useState<CollaborationRow | null>(
        null,
    );
    const [cancelling, setCancelling] = useState(false);
    const threadRow = (page?.data ?? []).find((row) => row.id === threadId);

    useEffect(() => {
        http.get<CampaignList>(companyApi.campaigns({ per_page: 50 }))
            .then(({ data }) => setCampaigns(data.data))
            .catch(() => setCampaigns([]));
    }, []);

    useEffect(() => {
        if (hasCampaign && campaign?.id !== campaignId) {
            void fetchCampaign(campaignId);
        }
    }, [campaign?.id, campaignId, fetchCampaign, hasCampaign]);

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

    useEffect(() => {
        setDraftQuery(query.q);
    }, [query.q]);

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
    }

    function applySearch() {
        setQuery((current) => ({
            ...current,
            page: 1,
            q: draftQuery.trim(),
        }));
    }

    function setCampaignFilter(value: string) {
        setQuery((current) => ({
            ...current,
            page: 1,
            filters: {
                ...current.filters,
                campaign: value === 'all' ? null : value,
            },
        }));
    }

    function clearFilters() {
        setDraftQuery('');
        setQuery((current) => ({
            ...current,
            page: 1,
            q: '',
            filters: { ...current.filters, campaign: null },
        }));
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
    const filtersActive =
        query.q.trim() !== '' ||
        (query.filters.campaign !== null && query.filters.campaign !== '');

    return (
        <div className="flex w-full flex-1 flex-col gap-8">
            <div className="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div className="space-y-2">
                    <h1 className="text-heading font-medium tracking-tight">
                        Collaborations
                    </h1>
                    <p className="max-w-xl text-sm text-muted-foreground">
                        Invites, applications, and booked creators.
                    </p>
                </div>
                {Number.isFinite(selectedCampaignId) &&
                    selectedCampaignId > 0 && (
                        <Button
                            type="button"
                            className="rounded-pill"
                            onClick={() => setInviteOpen(true)}
                        >
                            <UserPlus className="size-4" />
                            Invite
                        </Button>
                    )}
            </div>

            <SegmentedNav
                items={pipelines.map((tab) => ({
                    id: tab,
                    label: `${pipelineLabels[tab]} ${counts[tab]}`,
                }))}
                value={pipeline}
                onChange={(id) => {
                    setPipeline(id as PipelineTab);
                    setQuery((current) => ({ ...current, page: 1 }));
                }}
                className="w-fit max-w-full flex-wrap"
            />

            <div className="flex flex-wrap items-center gap-3">
                <SearchPill
                    value={draftQuery}
                    onChange={setDraftQuery}
                    onSubmit={applySearch}
                    placeholder="Search creators or campaigns…"
                    className="min-w-[16rem] max-w-md flex-1"
                />
                <FilterDropdown
                    label="Campaign"
                    icon={<Layers className="size-4" />}
                    value={query.filters.campaign ?? 'all'}
                    idleValue="all"
                    onChange={setCampaignFilter}
                    items={[
                        { value: 'all', label: 'All campaigns' },
                        ...campaigns.map((item) => ({
                            value: String(item.id),
                            label: item.name,
                        })),
                    ]}
                />
                {filtersActive ? (
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        className="h-14 rounded-pill bg-card px-5"
                        onClick={clearFilters}
                    >
                        Clear
                    </Button>
                ) : null}
            </div>

            <DataTable
                page={page}
                query={query}
                onQueryChange={setQuery}
                loading={loading}
                search={false}
                empty="No collaborations in this view."
                rowKey={(row) => row.id}
                columns={[
                    {
                        key: 'creator',
                        header: 'Creator',
                        cell: (row) => (
                            <button
                                type="button"
                                className="flex min-w-0 items-center gap-3 text-left"
                                onClick={(event) => {
                                    event.stopPropagation();
                                    setOpenId(row.creator.id);
                                }}
                            >
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
                            </button>
                        ),
                    },
                    {
                        key: 'campaign',
                        header: 'Campaign',
                        cell: (row) =>
                            row.campaign ? (
                                <AppLink
                                    href={`/campaigns/${row.campaign.id}`}
                                    className="font-medium text-foreground underline-offset-4 hover:underline"
                                >
                                    {row.campaign.name}
                                </AppLink>
                            ) : (
                                '—'
                            ),
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
                                <div
                                    className="flex flex-wrap justify-end gap-2"
                                    onClick={(event) => event.stopPropagation()}
                                >
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
                                        <button
                                            type="button"
                                            className="flex w-full items-center gap-3 text-left"
                                            onClick={() =>
                                                setOpenId(creator.id)
                                            }
                                        >
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
                                        </button>
                                        <p className="mt-4 text-sm">
                                            From{' '}
                                            {euros(creator.from_price_cents)}
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
            <CollaborationChatSheet
                open={threadId !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setThreadId(null);
                    }
                }}
                collaborationId={threadId}
                title={threadRow?.creator.display_name ?? 'Messages'}
                side="company"
                canSend={threadRow?.status !== 'cancelled'}
            />
            <CreatorProfileDialog
                creatorId={openId}
                starred={openId !== null && isShortlisted(shortlist, openId)}
                intent="book"
                onClose={() => setOpenId(null)}
                onStar={(creator) =>
                    setShortlist((current) => toggleShortlist(current, creator))
                }
            />
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
