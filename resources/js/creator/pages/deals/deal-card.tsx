import { memo, useState } from 'react';
import { useNavigate } from 'react-router';
import { FileText, Handshake, Target, Wallet } from 'lucide-react';
import {
    CampaignBriefDialog,
    type CampaignBrief,
} from '@/components/campaign-brief-dialog';
import { CampaignOpportunityCard } from '@/components/campaign-opportunity-card';
import { euros } from '@/company/pages/creators/format';
import type { Deal } from '@/creator/pages/deals/types';
import { ApiError, creatorApi, http } from '@/lib/api';

type DealDetailBrief = {
    brief: CampaignBrief | null;
    campaign: { name: string };
    company: { name: string | null };
};

function DealCard({ deal }: { deal: Deal }) {
    const navigate = useNavigate();
    const [open, setOpen] = useState(false);
    const [brief, setBrief] = useState<CampaignBrief | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const priceValue =
        deal.booked_price_cents == null
            ? 'TBD'
            : euros(deal.booked_price_cents);

    const postsValue =
        deal.booked_posts_count == null
            ? '—'
            : `${deal.booked_posts_count} post${deal.booked_posts_count === 1 ? '' : 's'}`;

    async function openBrief() {
        setOpen(true);
        setLoading(true);
        setError(null);

        try {
            const { data } = await http.get<DealDetailBrief>(
                creatorApi.collaboration(deal.id),
            );
            setBrief(data.brief);
        } catch (caught) {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not load this brief.',
            );
            setBrief(null);
        } finally {
            setLoading(false);
        }
    }

    return (
        <>
            <CampaignOpportunityCard
                badgeLabel={titleCase(deal.status)}
                badgeIcon={<Handshake className="size-3.5" />}
                companyName={deal.company.name ?? 'Company'}
                companyLogoUrl={deal.company.logo_url}
                companyWebsite={deal.company.website}
                title={deal.campaign.name}
                tagline={titleCase(deal.campaign.objective)}
                stats={[
                    {
                        icon: <Wallet className="size-4" />,
                        value: priceValue,
                        label: 'Price',
                    },
                    {
                        icon: <FileText className="size-4" />,
                        value: postsValue,
                        label: 'Deliverables',
                    },
                    {
                        icon: <Target className="size-4" />,
                        value: titleCase(deal.source),
                        label: 'Source',
                    },
                ]}
                secondaryLabel="Brief"
                primaryLabel="Open"
                onSecondary={() => void openBrief()}
                onPrimary={() => void navigate(`/deals/${deal.id}`)}
            />

            <CampaignBriefDialog
                open={open}
                onOpenChange={setOpen}
                title={deal.campaign.name}
                companyName={deal.company.name}
                brief={brief}
                loading={loading}
                error={error}
            />
        </>
    );
}

function titleCase(value: string): string {
    return value
        .replaceAll('_', ' ')
        .replace(/^\w/, (letter) => letter.toUpperCase());
}

export default memo(DealCard);
