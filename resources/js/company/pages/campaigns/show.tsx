import { useEffect, useRef, useState, type ReactNode } from 'react';
import { useNavigate, useParams } from 'react-router';
import { ArrowLeft } from 'lucide-react';
import { AppLink } from '@/components/app-link';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { euros } from '@/company/pages/creators/format';
import CampaignAnalytics from './analytics-shell';
import CampaignStatusSelect from './status-select';
import CampaignTracking from './tracking';
import { useCampaigns } from './store';
import { objectiveLabels, typeLabels } from './types';

export default function CompanyCampaignShowPage() {
    const { id } = useParams();
    const campaignId = Number(id);
    const navigate = useNavigate();
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
                <InputError message="This campaign does not exist." />
            </div>
        );
    }

    return (
        <div className="flex w-full flex-1 flex-col gap-8">
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
                            <p className="mt-2 text-sm text-muted-foreground">
                                {typeLabels[campaign.type]} ·{' '}
                                {objectiveLabels[campaign.objective]}
                                {campaign.company_icp
                                    ? ` · ${campaign.company_icp.title}`
                                    : ''}
                            </p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {dateRange(campaign.start_at, campaign.end_at)}{' '}
                                · {euros(campaign.budget_cents)}
                            </p>
                        </div>
                        <div className="flex flex-wrap items-center gap-2">
                            <Button type="button" variant="outline" asChild>
                                <AppLink
                                    href={`/brief?campaign=${campaignId}`}
                                >
                                    Edit brief
                                </AppLink>
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <AppLink
                                    href={`/collaboration?campaign=${campaignId}`}
                                >
                                    Collaborations
                                </AppLink>
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <AppLink
                                    href={`/campaigns/${campaignId}/analytics`}
                                >
                                    Open analytics
                                </AppLink>
                            </Button>
                            <CampaignStatusSelect
                                campaignId={campaignId}
                                status={campaign.status}
                            />
                        </div>
                    </div>
                )}
            </div>
            <InputError message={error ?? undefined} />
            {loading && campaign === null ? (
                <p className="text-sm text-muted-foreground">Loading…</p>
            ) : campaign ? (
                <>
                    <CampaignTracking campaignId={campaignId} />
                    <WhenVisible>
                        <CampaignAnalytics
                            campaignId={campaignId}
                            leadsCount={campaign.leads_count}
                            posts={campaign.posts}
                        />
                    </WhenVisible>
                </>
            ) : null}
        </div>
    );
}

function WhenVisible({ children }: { children: ReactNode }) {
    const ref = useRef<HTMLDivElement>(null);
    const [ready, setReady] = useState(false);

    useEffect(() => {
        const node = ref.current;

        if (node === null || ready) {
            return;
        }

        const observer = new IntersectionObserver(
            ([entry]) => {
                if (entry?.isIntersecting) {
                    setReady(true);
                }
            },
            { rootMargin: '160px' },
        );

        observer.observe(node);

        return () => observer.disconnect();
    }, [ready]);

    return <div ref={ref}>{ready ? children : null}</div>;
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
