import { useEffect, useState, type FormEvent } from 'react';
import { useNavigate } from 'react-router';
import { Eye, Pencil, PlusSquare, Trash2, Users } from 'lucide-react';
import { toast } from 'sonner';
import { ConfirmDialog } from '@/components/confirm-dialog';
import {
    DataTable,
    type DataTableQuery,
} from '@/components/data-table';
import { AppLink } from '@/components/app-link';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
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
import { centsFromEuros, euros } from '@/company/pages/creators/format';
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

const emptyQuery: DataTableQuery = {
    page: 1,
    per_page: 25,
    q: '',
    filters: { status: null, type: null },
};

export default function CompanyCampaignsPage() {
    const { list, loading, error, fetchList, transition } = useCampaigns();
    const [query, setQuery] = useState<DataTableQuery>(emptyQuery);
    const [createOpen, setCreateOpen] = useState(false);
    const [pendingDelete, setPendingDelete] = useState<CampaignListItem | null>(
        null,
    );
    const [deleting, setDeleting] = useState(false);

    useEffect(() => {
        void fetchList({
            page: query.page,
            per_page: query.per_page,
            q: query.q || undefined,
            status: (query.filters.status as CampaignStatus | '') ?? '',
            type: (query.filters.type as CampaignType | '') ?? '',
        });
    }, [fetchList, query]);

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
                page: query.page,
                per_page: query.per_page,
                q: query.q || undefined,
                status: (query.filters.status as CampaignStatus | '') ?? '',
                type: (query.filters.type as CampaignType | '') ?? '',
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

    return (
        <div className="flex w-full flex-1 flex-col gap-6">
            <div>
                <h1 className="text-2xl font-semibold tracking-tight">
                    Campaigns
                </h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    Briefs, creator pipeline, and posts for each campaign.
                </p>
            </div>
            <InputError message={error ?? undefined} />
            <DataTable
                page={list}
                query={query}
                onQueryChange={setQuery}
                loading={loading}
                searchPlaceholder="Search campaigns"
                empty="No campaigns yet. Create a draft, write the brief, then invite creators."
                rowKey={(row) => row.id}
                filterGroups={[
                    {
                        label: 'Campaign',
                        filters: [
                            {
                                key: 'status',
                                label: 'Status',
                                options: Object.entries(statusLabels).map(
                                    ([value, label]) => ({ value, label }),
                                ),
                            },
                            {
                                key: 'type',
                                label: 'Type',
                                options: Object.entries(typeLabels).map(
                                    ([value, label]) => ({ value, label }),
                                ),
                            },
                        ],
                    },
                ]}
                actions={[
                    {
                        label: 'New campaign',
                        icon: <PlusSquare className="size-4" />,
                        onClick: () => setCreateOpen(true),
                    },
                ]}
                columns={[
                    {
                        key: 'name',
                        header: 'Name',
                        cell: (row) => (
                            <span className="font-medium">{row.name}</span>
                        ),
                    },
                    {
                        key: 'status',
                        header: 'Status',
                        cell: (row) => (
                            <Badge variant="secondary">
                                {statusLabels[row.status]}
                            </Badge>
                        ),
                    },
                    {
                        key: 'kind',
                        header: 'Type',
                        cell: (row) => (
                            <span className="text-muted-foreground">
                                {typeLabels[row.type]} ·{' '}
                                {objectiveLabels[row.objective]}
                            </span>
                        ),
                    },
                    {
                        key: 'dates',
                        header: 'Dates',
                        cell: (row) => dateRange(row.start_at, row.end_at),
                    },
                    {
                        key: 'budget',
                        header: 'Budget',
                        cell: (row) => euros(row.budget_cents),
                    },
                    {
                        key: 'collabs',
                        header: 'Collaborations',
                        cell: (row) => row.collab_count,
                    },
                    {
                        key: 'actions',
                        header: '',
                        cell: (row) => (
                            <div className="flex justify-end gap-1">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    asChild
                                    title="View"
                                >
                                    <AppLink href={`/campaigns/${row.id}`}>
                                        <Eye />
                                    </AppLink>
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    asChild
                                    title="Edit brief"
                                >
                                    <AppLink
                                        href={`/brief?campaign=${row.id}`}
                                    >
                                        <Pencil />
                                    </AppLink>
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    asChild
                                    title="Collaborations"
                                >
                                    <AppLink
                                        href={`/collaboration?campaign=${row.id}`}
                                    >
                                        <Users />
                                    </AppLink>
                                </Button>
                                {row.status !== 'completed' &&
                                    row.status !== 'cancelled' && (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            title="Delete"
                                            onClick={() =>
                                                setPendingDelete(row)
                                            }
                                        >
                                            <Trash2 />
                                        </Button>
                                    )}
                            </div>
                        ),
                    },
                ]}
            />
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
                description="This cancels the campaign and hides it from the default list. You can reopen it later from a cancelled filter."
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
                company_icp_id:
                    icpId === 'none' ? undefined : Number(icpId),
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
