import { useEffect, useState, type FormEvent } from 'react';
import { useSearchParams } from 'react-router';
import { toast } from 'sonner';
import { AppLink } from '@/components/app-link';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import CampaignBriefEditor from '@/company/pages/campaigns/brief-editor';
import { useCampaigns } from '@/company/pages/campaigns/store';
import {
    objectiveLabels,
    typeLabels,
    type CampaignObjective,
    type CampaignType,
    type IcpOption,
} from '@/company/pages/campaigns/types';
import { centsFromEuros, euros } from '@/company/pages/creators/format';
import { ApiError, api, companyApi } from '@/lib/api';

export default function CompanyBriefPage() {
    const [params] = useSearchParams();
    const campaignId = Number(params.get('campaign'));
    const { campaign, loading, error, fetchCampaign } = useCampaigns();

    useEffect(() => {
        if (!Number.isFinite(campaignId) || campaignId < 1) {
            return;
        }

        void fetchCampaign(campaignId);
    }, [campaignId, fetchCampaign]);

    if (!Number.isFinite(campaignId) || campaignId < 1) {
        return (
            <div>
                <InputError message="Choose a campaign to edit its brief." />
            </div>
        );
    }

    return (
        <div className="flex w-full flex-1 flex-col gap-8">
            <div className="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {campaign?.name ?? 'Brief'}
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Edit the brief and the fields this campaign still allows.
                    </p>
                </div>
                <Button type="button" variant="outline" asChild>
                    <AppLink href={`/campaigns/${campaignId}`}>View</AppLink>
                </Button>
            </div>
            <InputError message={error ?? undefined} />
            {loading && campaign === null ? (
                <p className="text-sm text-muted-foreground">Loading…</p>
            ) : campaign && campaign.id === campaignId ? (
                <>
                    <CampaignFields campaignId={campaignId} />
                    <CampaignBriefEditor
                        campaignId={campaignId}
                        brief={campaign.brief}
                        readOnly={
                            campaign.status === 'completed' ||
                            campaign.status === 'cancelled'
                        }
                    />
                </>
            ) : null}
        </div>
    );
}

function CampaignFields({ campaignId }: { campaignId: number }) {
    const campaign = useCampaigns((state) => state.campaign);
    const updateCampaign = useCampaigns((state) => state.updateCampaign);
    const saving = useCampaigns((state) => state.saving);
    const [icps, setIcps] = useState<IcpOption[]>([]);
    const [name, setName] = useState(campaign?.name ?? '');
    const [type, setType] = useState<CampaignType>(
        campaign?.type ?? 'thought_leadership',
    );
    const [objective, setObjective] = useState<CampaignObjective>(
        campaign?.objective ?? 'awareness',
    );
    const [budget, setBudget] = useState(
        campaign?.budget_cents ? String(campaign.budget_cents / 100) : '',
    );
    const [icpId, setIcpId] = useState(
        campaign?.company_icp_id ? String(campaign.company_icp_id) : 'none',
    );
    const [startAt, setStartAt] = useState(dateInput(campaign?.start_at ?? null));
    const [endAt, setEndAt] = useState(dateInput(campaign?.end_at ?? null));

    const locked = campaign?.status === 'completed' || campaign?.status === 'cancelled';
    const identityLocked = campaign?.status !== 'draft';

    useEffect(() => {
        api<IcpOption[]>(companyApi.icps)
            .then(setIcps)
            .catch(() => setIcps([]));
    }, []);

    useEffect(() => {
        if (!campaign) {
            return;
        }

        setName(campaign.name);
        setType(campaign.type);
        setObjective(campaign.objective);
        setBudget(
            campaign.budget_cents ? String(campaign.budget_cents / 100) : '',
        );
        setIcpId(
            campaign.company_icp_id ? String(campaign.company_icp_id) : 'none',
        );
        setStartAt(dateInput(campaign.start_at));
        setEndAt(dateInput(campaign.end_at));
    }, [campaign]);

    async function submit(event: FormEvent) {
        event.preventDefault();

        const payload: Record<string, unknown> = {
            name,
            budget_cents: budget === '' ? null : centsFromEuros(budget),
            company_icp_id: icpId === 'none' ? null : Number(icpId),
            start_at: startAt === '' ? null : startAt,
            end_at: endAt === '' ? null : endAt,
        };

        if (!identityLocked) {
            payload.type = type;
            payload.objective = objective;
        }

        try {
            await updateCampaign(campaignId, payload);
            toast.success('Campaign saved');
        } catch (caught) {
            toast.error(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not save the campaign.',
            );
        }
    }

    return (
        <form
            onSubmit={submit}
            className="grid gap-4 rounded-lg border border-border bg-card p-4 sm:grid-cols-2"
        >
            <div className="grid gap-2 sm:col-span-2">
                <Label htmlFor="brief-name">Name</Label>
                <Input
                    id="brief-name"
                    value={name}
                    onChange={(event) => setName(event.target.value)}
                    disabled={locked}
                    required
                />
            </div>
            <div className="grid gap-2">
                <Label>Type</Label>
                <Select
                    value={type}
                    onValueChange={(value) => setType(value as CampaignType)}
                    disabled={locked || identityLocked}
                >
                    <SelectTrigger className="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {Object.entries(typeLabels).map(([value, label]) => (
                            <SelectItem key={value} value={value}>
                                {label}
                            </SelectItem>
                        ))}
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
                    disabled={locked || identityLocked}
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
                <Label htmlFor="brief-budget">Budget (€)</Label>
                <Input
                    id="brief-budget"
                    type="number"
                    min="0"
                    value={budget}
                    onChange={(event) => setBudget(event.target.value)}
                    disabled={locked}
                    placeholder={euros(campaign?.budget_cents ?? null)}
                />
            </div>
            <div className="grid gap-2">
                <Label>ICP</Label>
                <Select
                    value={icpId}
                    onValueChange={setIcpId}
                    disabled={locked}
                >
                    <SelectTrigger className="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="none">None</SelectItem>
                        {icps.map((icp) => (
                            <SelectItem key={icp.id} value={String(icp.id)}>
                                {icp.title}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
            <div className="grid gap-2">
                <Label htmlFor="brief-start">Start</Label>
                <Input
                    id="brief-start"
                    type="date"
                    value={startAt}
                    onChange={(event) => setStartAt(event.target.value)}
                    disabled={locked}
                />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="brief-end">End</Label>
                <Input
                    id="brief-end"
                    type="date"
                    value={endAt}
                    onChange={(event) => setEndAt(event.target.value)}
                    disabled={locked}
                />
            </div>
            <div className="sm:col-span-2">
                <Button type="submit" disabled={locked || saving}>
                    {saving ? 'Saving…' : 'Save campaign'}
                </Button>
            </div>
        </form>
    );
}

function dateInput(value: string | null): string {
    return value ? value.slice(0, 10) : '';
}
