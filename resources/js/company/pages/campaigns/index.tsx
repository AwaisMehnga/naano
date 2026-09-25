import { useEffect, useMemo, useState, type FormEvent } from 'react';
import { useNavigate, useSearchParams } from 'react-router';
import { Layers, ListFilter, Plus } from 'lucide-react';
import { toast } from 'sonner';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { FilterDropdown, FilterDropdownGroup } from '@/components/filter-select';
import { SearchPill } from '@/components/ds';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import CampaignCard from '@/company/pages/campaigns/campaign-card';
import { centsFromEuros } from '@/company/pages/creators/format';
import { ApiError, api, companyApi } from '@/lib/api';
import { useCampaigns } from './store';
import {
    objectiveLabels,
    statusLabels,
    typeLabels,
    type CampaignListItem,
    type CampaignObjective,
    type CampaignStatus,
    type CampaignType,
    type IcpOption,
} from './types';

const statuses = [
    'draft',
    'active',
    'paused',
    'completed',
    'cancelled',
] as const satisfies readonly CampaignStatus[];

const types = [
    'thought_leadership',
    'product',
    'hiring',
    'event',
    'other',
] as const satisfies readonly CampaignType[];

function readParam(
    params: URLSearchParams,
    key: string,
    fallback: string,
): string {
    return params.get(key)?.trim() || fallback;
}

export default function CompanyCampaignsPage() {
    const { list, loading, error, fetchList, transition } = useCampaigns();
    const [searchParams, setSearchParams] = useSearchParams();

    const q = readParam(searchParams, 'q', '');
    const status = readParam(searchParams, 'status', 'active');
    const type = readParam(searchParams, 'type', 'all');
    const page = Number(readParam(searchParams, 'page', '1')) || 1;

    const [draftQuery, setDraftQuery] = useState(q);
    const [createOpen, setCreateOpen] = useState(false);
    const [pendingDelete, setPendingDelete] = useState<CampaignListItem | null>(
        null,
    );
    const [deleting, setDeleting] = useState(false);

    const statusItems = useMemo(
        () => [
            { value: 'all', label: 'All statuses' },
            ...statuses.map((value) => ({
                value,
                label: statusLabels[value],
            })),
        ],
        [],
    );

    const typeItems = useMemo(
        () => [
            { value: 'all', label: 'All types' },
            ...types.map((value) => ({
                value,
                label: typeLabels[value],
            })),
        ],
        [],
    );

    useEffect(() => {
        setDraftQuery(q);
    }, [q]);

    useEffect(() => {
        void fetchList({
            page,
            per_page: 24,
            q: q || undefined,
            status: status as CampaignStatus | 'all' | '',
            type: type === 'all' ? '' : (type as CampaignType),
        });
    }, [fetchList, page, q, status, type]);

    function patchParams(patch: Record<string, string | null>) {
        setSearchParams(
            (current) => {
                const next = new URLSearchParams(current);

                for (const [key, value] of Object.entries(patch)) {
                    if (
                        value === null ||
                        value === '' ||
                        (key === 'status' && value === 'active') ||
                        (key === 'type' && value === 'all') ||
                        (key === 'page' && value === '1')
                    ) {
                        next.delete(key);
                    } else {
                        next.set(key, value);
                    }
                }

                return next;
            },
            { replace: true },
        );
    }

    function clearFilters() {
        setDraftQuery('');
        setSearchParams({}, { replace: true });
    }

    const filtersActive =
        q !== '' || status !== 'active' || type !== 'all' || page > 1;

    async function confirmDelete() {
        if (pendingDelete === null) {
            return;
        }

        setDeleting(true);

        try {
            await transition(pendingDelete.id, 'cancel');
            toast.success('Campaign deleted');
            setPendingDelete(null);
            void fetchList({
                page,
                per_page: 24,
                q: q || undefined,
                status: status as CampaignStatus | 'all' | '',
                type: type === 'all' ? '' : (type as CampaignType),
            });
        } catch (caught) {
            toast.error(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not delete this campaign.',
            );
        } finally {
            setDeleting(false);
        }
    }

    const items = list?.data ?? [];
    const total = list?.total ?? 0;
    const lastPage = list?.last_page ?? 1;

    return (
        <div className="flex w-full flex-1 flex-col gap-8">
            <div className="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div className="space-y-2">
                    <p className="text-sm text-muted-foreground">Company</p>
                    <h1 className="text-heading font-medium tracking-tight">
                        Campaigns
                    </h1>
                    <p className="max-w-xl text-sm text-muted-foreground">
                        Briefs, creator pipeline, and posts for each campaign.
                    </p>
                </div>

                <div className="flex flex-wrap items-center gap-3">
                    {!loading ? (
                        <p className="text-sm text-muted-foreground">
                            <span className="font-medium text-foreground">
                                {total}
                            </span>{' '}
                            {total === 1 ? 'campaign' : 'campaigns'}
                        </p>
                    ) : null}
                    <Button
                        type="button"
                        className="rounded-pill"
                        onClick={() => setCreateOpen(true)}
                    >
                        <Plus className="size-4" />
                        New campaign
                    </Button>
                </div>
            </div>

            <div className="flex flex-wrap items-center gap-3">
                <SearchPill
                    value={draftQuery}
                    onChange={setDraftQuery}
                    onSubmit={() =>
                        patchParams({
                            q: draftQuery.trim() || null,
                            page: '1',
                        })
                    }
                    placeholder="Search campaigns…"
                    className="min-w-[16rem] max-w-md flex-1"
                />

                <FilterDropdownGroup className="contents">
                    <FilterDropdown
                        label="Status"
                        icon={<ListFilter />}
                        value={status}
                        onChange={(value) =>
                            patchParams({ status: value, page: '1' })
                        }
                        items={statusItems}
                        idleValue="active"
                    />

                    <FilterDropdown
                        label="Type"
                        icon={<Layers />}
                        value={type}
                        onChange={(value) =>
                            patchParams({ type: value, page: '1' })
                        }
                        items={typeItems}
                        idleValue="all"
                    />
                </FilterDropdownGroup>

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

            <InputError message={error ?? undefined} />

            {loading ? (
                <p className="text-sm text-muted-foreground">
                    Loading campaigns…
                </p>
            ) : items.length === 0 ? (
                <div className="space-y-3 rounded-3xl bg-muted p-6">
                    <h2 className="text-lg font-medium">No campaigns</h2>
                    <p className="text-sm text-muted-foreground">
                        {status === 'active'
                            ? 'No active campaigns yet. Create a draft, write the brief, then launch.'
                            : 'Try another status or clear filters.'}
                    </p>
                    <div className="flex flex-wrap gap-2">
                        <Button
                            className="rounded-pill"
                            onClick={() => setCreateOpen(true)}
                        >
                            New campaign
                        </Button>
                        {filtersActive ? (
                            <Button
                                type="button"
                                variant="outline"
                                className="rounded-pill bg-card"
                                onClick={clearFilters}
                            >
                                Clear
                            </Button>
                        ) : null}
                    </div>
                </div>
            ) : (
                <>
                    <div className="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                        {items.map((campaign) => (
                            <CampaignCard
                                key={campaign.id}
                                campaign={campaign}
                                onDelete={setPendingDelete}
                            />
                        ))}
                    </div>

                    {lastPage > 1 ? (
                        <div className="flex items-center justify-between gap-3">
                            <p className="text-sm text-muted-foreground">
                                Page {page} of {lastPage}
                            </p>
                            <div className="flex gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    className="rounded-pill"
                                    disabled={page <= 1}
                                    onClick={() =>
                                        patchParams({
                                            page: String(page - 1),
                                        })
                                    }
                                >
                                    Previous
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    className="rounded-pill"
                                    disabled={page >= lastPage}
                                    onClick={() =>
                                        patchParams({
                                            page: String(page + 1),
                                        })
                                    }
                                >
                                    Next
                                </Button>
                            </div>
                        </div>
                    ) : null}
                </>
            )}

            <CreateCampaignDialog
                open={createOpen}
                onOpenChange={setCreateOpen}
            />
            <ConfirmDialog
                open={pendingDelete !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setPendingDelete(null);
                    }
                }}
                title="Delete this campaign?"
                description="This cancels the campaign and hides it from the active list. Filter to Cancelled to find it again."
                confirmLabel="Delete campaign"
                pending={deleting}
                onConfirm={confirmDelete}
            />
        </div>
    );
}

function CreateCampaignDialog({
    open,
    onOpenChange,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const create = useCampaigns((state) => state.create);
    const navigate = useNavigate();
    const [name, setName] = useState('');
    const [type, setType] = useState<CampaignType>('thought_leadership');
    const [objective, setObjective] = useState<CampaignObjective>('awareness');
    const [budget, setBudget] = useState('');
    const [icpId, setIcpId] = useState('none');
    const [icps, setIcps] = useState<IcpOption[]>([]);
    const [error, setError] = useState<string | null>(null);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (!open) {
            return;
        }

        api<IcpOption[]>(companyApi.icps)
            .then(setIcps)
            .catch(() => setIcps([]));
    }, [open]);

    async function submit(event: FormEvent) {
        event.preventDefault();
        setSaving(true);
        setError(null);

        try {
            const campaign = await create({
                name,
                type,
                objective,
                budget_cents: centsFromEuros(budget),
                company_icp_id: icpId === 'none' ? undefined : Number(icpId),
            });
            toast.success('Campaign created');
            onOpenChange(false);
            void navigate(`/brief?campaign=${campaign.id}`);
        } catch (caught) {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not create the campaign.',
            );
        } finally {
            setSaving(false);
        }
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <form onSubmit={submit} className="grid gap-4">
                    <DialogHeader>
                        <DialogTitle>New campaign</DialogTitle>
                        <DialogDescription>
                            Start as a draft. You can fill the brief on the next
                            screen.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-2">
                        <Label htmlFor="campaign-name">Name</Label>
                        <Input
                            id="campaign-name"
                            value={name}
                            onChange={(event) => setName(event.target.value)}
                            required
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label>Type</Label>
                        <Select
                            value={type}
                            onValueChange={(value) =>
                                setType(value as CampaignType)
                            }
                        >
                            <SelectTrigger className="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {Object.entries(typeLabels).map(
                                    ([value, label]) => (
                                        <SelectItem key={value} value={value}>
                                            {label}
                                        </SelectItem>
                                    ),
                                )}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="grid gap-2">
                        <Label>Objective</Label>
                        <Select
                            value={objective}
                            onValueChange={(value) =>
                                setObjective(value as CampaignObjective)
                            }
                        >
                            <SelectTrigger className="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {Object.entries(objectiveLabels).map(
                                    ([value, label]) => (
                                        <SelectItem key={value} value={value}>
                                            {label}
                                        </SelectItem>
                                    ),
                                )}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="campaign-budget">Budget (€)</Label>
                        <Input
                            id="campaign-budget"
                            type="number"
                            min="0"
                            value={budget}
                            onChange={(event) => setBudget(event.target.value)}
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label>ICP</Label>
                        <Select value={icpId} onValueChange={setIcpId}>
                            <SelectTrigger className="w-full">
                                <SelectValue placeholder="Optional" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">None</SelectItem>
                                {icps.map((icp) => (
                                    <SelectItem
                                        key={icp.id}
                                        value={String(icp.id)}
                                    >
                                        {icp.title}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <InputError message={error ?? undefined} />
                    <DialogFooter>
                        <Button type="submit" disabled={saving}>
                            {saving ? 'Creating…' : 'Create draft'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
