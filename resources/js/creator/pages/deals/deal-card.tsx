import { memo } from 'react';
import { useNavigate } from 'react-router';
import { FileText, Handshake, Target, Wallet } from 'lucide-react';
import { CampaignOpportunityCard } from '@/components/campaign-opportunity-card';
import { euros } from '@/company/pages/creators/format';
import type { Deal } from '@/creator/pages/deals/types';

function DealCard({
    deal,
    onMessage,
}: {
    deal: Deal;
    onMessage: (deal: Deal) => void;
}) {
    const navigate = useNavigate();

    const priceValue =
        deal.booked_price_cents == null
            ? 'TBD'
            : euros(deal.booked_price_cents);

    const postsValue =
        deal.booked_posts_count == null
            ? '—'
            : `${deal.booked_posts_count} post${deal.booked_posts_count === 1 ? '' : 's'}`;

    return (
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
            secondaryLabel="Message"
            primaryLabel="Open"
            onSecondary={() => onMessage(deal)}
            onPrimary={() => void navigate(`/deals/${deal.id}`)}
        />
    );
}

function titleCase(value: string): string {
    return value
        .replaceAll('_', ' ')
        .replace(/^\w/, (letter) => letter.toUpperCase());
}

export default memo(DealCard);
