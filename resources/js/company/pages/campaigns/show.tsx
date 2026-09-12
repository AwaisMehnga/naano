import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router';
import { ArrowLeft } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { euros } from '@/company/pages/creators/format';
import { cn } from '@/lib/utils';
import CampaignAnalytics from './analytics-shell';
import CampaignBriefEditor from './brief-editor';
import CampaignCollaborations from './collaborations';
import CampaignStatusSelect from './status-select';
import { useCampaigns } from './store';
import {
    objectiveLabels,
    typeLabels,
    type DetailTab,
} from './types';

const tabs: { id: DetailTab; label: string }[] = [
    { id: 'collaborations', label: 'Collaborations' },
    { id: 'brief', label: 'Brief' },
    { id: 'analytics', label: 'Analytics' },
];

export default function CompanyCampaignShowPage() {
    const { id } = useParams();
    const campaignId = Number(id);
    const navigate = useNavigate();
    const { campaign, loading, error, fetchCampaign, fetchCollaborations } =
        useCampaigns();
    const [tab, setTab] = useState<DetailTab>('collaborations');

    useEffect(() => {
        if (!Number.isFinite(campaignId) || campaignId < 1) {
            return;
        }

        void fetchCampaign(campaignId);
        void fetchCollaborations(campaignId, 'all');
    }, [campaignId, fetchCampaign, fetchCollaborations]);

    if (!Number.isFinite(campaignId) || campaignId < 1) {
        return (
            <div className="p-6">
                <InputError message="This campaign does not exist." />
            </div>
        );
    }

    return (
        <div className="mx-auto flex w-full w-full flex-1 flex-col gap-8 p-4 lg:p-6">
            <div className="flex flex-col gap-4">
                <Button
                    type="button"
                    variant="ghost"
                    className="w-fit px-0"
                    onClick={() => void navigate('/campaigns')}
                >
                    <ArrowLeft className="size-4" />
                    Campaigns
                </Button>
                {campaign && (
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h1 className="text-2xl font-semibold tracking-tight">
                                {campaign.name}
                            </h1>
                            <p className="text-muted-foreground mt-2 text-sm">
                                {typeLabels[campaign.type]} ·{' '}
                                {objectiveLabels[campaign.objective]}
                                {campaign.company_icp
                                    ? ` · ${campaign.company_icp.title}`
                                    : ''}
                            </p>
                            <p className="text-muted-foreground mt-1 text-sm">
                                {dateRange(campaign.start_at, campaign.end_at)}{' '}
                                · {euros(campaign.budget_cents)}
                            </p>
                        </div>
                        <CampaignStatusSelect
                            campaignId={campaignId}
                            status={campaign.status}
                        />
                    </div>
                )}
            </div>
            <InputError message={error ?? undefined} />
            {loading && campaign === null ? (
                <p className="text-muted-foreground text-sm">Loading…</p>
            ) : campaign ? (
                <>
                    <div className="border-border flex gap-6 border-b">
                        {tabs.map((item) => (
                            <button
                                key={item.id}
                                type="button"
                                className={cn(
                                    'border-b-2 pb-3 text-sm',
                                    tab === item.id
                                        ? 'border-primary text-foreground font-medium'
                                        : 'text-muted-foreground border-transparent',
                                )}
                                onClick={() => setTab(item.id)}
                            >
                                {item.label}
                            </button>
                        ))}
                    </div>
                    {tab === 'collaborations' && (
                        <CampaignCollaborations campaignId={campaignId} />
                    )}
                    {tab === 'brief' && (
                        <CampaignBriefEditor
                            campaignId={campaignId}
                            brief={campaign.brief}
                        />
                    )}
                    {tab === 'analytics' && (
                        <CampaignAnalytics
                            leadsCount={campaign.leads_count}
                            posts={campaign.posts}
                        />
                    )}
                </>
            ) : null}
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
