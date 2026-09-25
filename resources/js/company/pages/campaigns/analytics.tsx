import { useEffect } from 'react';
import { useNavigate, useParams } from 'react-router';
import { ArrowLeft } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import CampaignAnalytics from './analytics-shell';
import { useCampaigns } from './store';

export default function CompanyCampaignAnalyticsPage() {
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
            <div className="space-y-2">
                <Button
                    type="button"
                    variant="ghost"
                    className="w-fit px-0"
                    onClick={() => void navigate(`/campaigns/${campaignId}`)}
                >
                    <ArrowLeft className="size-4" />
                    Campaign
                </Button>
                <p className="text-sm text-muted-foreground">Analytics</p>
                <h1 className="text-heading font-medium tracking-tight">
                    {campaign?.name ?? 'Campaign'}
                </h1>
                <p className="text-sm text-muted-foreground">
                    Reach, clicks, spend, and who drove them.
                </p>
            </div>
            <InputError message={error ?? undefined} />
            {loading && campaign === null ? (
                <p className="text-sm text-muted-foreground">Loading…</p>
            ) : campaign ? (
                <CampaignAnalytics campaignId={campaignId} />
            ) : null}
        </div>
    );
}
