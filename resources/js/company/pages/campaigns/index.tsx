import { useEffect, useState, type FormEvent } from 'react';
import { useNavigate } from 'react-router';
import { Megaphone, PlusSquare } from 'lucide-react';
import { toast } from 'sonner';
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
import { cn } from '@/lib/utils';
import { useCampaigns } from './store';
import {
    objectiveLabels,
    statusLabels,
    typeLabels,
    type CampaignObjective,
    type CampaignStatus,
    type CampaignType,
    type IcpOption,
} from './types';

const statusTabs: Array<CampaignStatus | ''> = [
    '',
    'draft',
    'active',
    'paused',
    'completed',
    'cancelled',
];

export default function CompanyCampaignsPage() {
    const { list, listStatus, loading, error, fetchList } = useCampaigns();
    const [createOpen, setCreateOpen] = useState(false);

    useEffect(() => {
        void fetchList();
    }, [fetchList]);

    return (
        <div className="flex flex-1 flex-col gap-6 p-4 lg:p-6">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Campaigns
                    </h1>
                    <p className="text-muted-foreground mt-1 max-w-2xl text-sm">
                        Briefs, creator pipeline, and posts for each campaign.
                    </p>
                </div>
                <Button type="button" onClick={() => setCreateOpen(true)}>
                    <PlusSquare className="size-4" />
                    New campaign
                </Button>
            </div>
            <div className="border-border flex flex-wrap gap-6 border-b">
                {statusTabs.map((status) => (
                    <button
                        key={status || 'all'}
                        type="button"
                        className={cn(
                            'border-b-2 pb-3 text-sm',
                            listStatus === status
                                ? 'border-primary text-foreground font-medium'
                                : 'text-muted-foreground border-transparent',
                        )}
                        onClick={() => void fetchList({ status })}
                    >
                        {status === '' ? 'All' : statusLabels[status]}
                    </button>
                ))}
            </div>
            <InputError message={error ?? undefined} />
            {loading && list === null ? (
                <p className="text-muted-foreground text-sm">Loading…</p>
            ) : list?.items.length === 0 ? (
                <div className="border-border bg-card flex flex-col items-center gap-3 rounded-2xl border px-6 py-16 text-center">
                    <Megaphone className="text-muted-foreground size-8" />
                    <p className="font-medium">No campaigns yet</p>
                    <p className="text-muted-foreground max-w-sm text-sm">
                        Create a draft, write the brief, then invite creators.
                    </p>
                    <Button type="button" onClick={() => setCreateOpen(true)}>
                        New campaign
                    </Button>
                </div>
            ) : (
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {list?.items.map((campaign) => (
                        <AppLink
                            key={campaign.id}
                            href={`/campaigns/${campaign.id}`}
                            className="border-border bg-card hover:border-primary/40 flex flex-col gap-4 rounded-2xl border p-5 shadow-sm transition-colors"
                        >
                            <div className="flex items-start justify-between gap-3">
                                <h2 className="text-base font-semibold">
                                    {campaign.name}
                                </h2>
                                <Badge variant="secondary">
                                    {statusLabels[campaign.status]}
                                </Badge>
                            </div>
                            <p className="text-muted-foreground text-sm">
                                {typeLabels[campaign.type]} ·{' '}
                                {objectiveLabels[campaign.objective]}
                            </p>
                            <div className="text-muted-foreground flex justify-between text-xs">
                                <span>{dateRange(campaign.start_at, campaign.end_at)}</span>
                                <span>{euros(campaign.budget_cents)}</span>
                            </div>
                            <p className="text-sm">
                                {campaign.collab_count} collaboration
                                {campaign.collab_count === 1 ? '' : 's'}
                            </p>
                        </AppLink>
                    ))}
                </div>
            )}
            <CreateCampaignDialog
                open={createOpen}
                onOpenChange={setCreateOpen}
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
            void navigate(`/campaigns/${campaign.id}`);
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
