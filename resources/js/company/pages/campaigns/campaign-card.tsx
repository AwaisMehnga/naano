import { Megaphone, Pencil, Trash2, Users } from 'lucide-react';
import { AppLink } from '@/components/app-link';
import { InfoChip } from '@/components/info-chip';
import { SoftCard } from '@/components/ds';
import { Button } from '@/components/ui/button';
import { euros } from '@/company/pages/creators/format';
import {
    objectiveLabels,
    statusLabels,
    typeLabels,
    type CampaignListItem,
} from './types';

type CampaignCardProps = {
    campaign: CampaignListItem;
    onDelete?: (campaign: CampaignListItem) => void;
};

export default function CampaignCard({
    campaign,
    onDelete,
}: CampaignCardProps) {
    const canDelete =
        campaign.status !== 'completed' && campaign.status !== 'cancelled';

    return (
        <SoftCard className="flex h-full flex-col gap-5">
            <div className="flex items-start gap-3">
                <div className="flex size-11 shrink-0 items-center justify-center rounded-full bg-muted">
                    <Megaphone className="size-5 text-foreground" />
                </div>
                <div className="min-w-0 flex-1 space-y-2">
                    <div className="flex flex-wrap items-center gap-2">
                        <h2 className="truncate text-base font-medium tracking-tight">
                            {campaign.name}
                        </h2>
                        <InfoChip>{statusLabels[campaign.status]}</InfoChip>
                    </div>
                    <div className="flex flex-wrap gap-1.5">
                        <InfoChip>{typeLabels[campaign.type]}</InfoChip>
                        <InfoChip>
                            {objectiveLabels[campaign.objective]}
                        </InfoChip>
                    </div>
                </div>
            </div>

            <dl className="grid grid-cols-3 gap-3 text-sm">
                <div>
                    <dt className="text-muted-foreground">Dates</dt>
                    <dd className="mt-1 font-medium tabular-nums">
                        {dateRange(campaign.start_at, campaign.end_at)}
                    </dd>
                </div>
                <div>
                    <dt className="text-muted-foreground">Budget</dt>
                    <dd className="mt-1 font-medium tabular-nums">
                        {campaign.budget_cents == null
                            ? '—'
                            : euros(campaign.budget_cents)}
                    </dd>
                </div>
                <div>
                    <dt className="text-muted-foreground">Collabs</dt>
                    <dd className="mt-1 font-medium tabular-nums">
                        {campaign.collab_count}
                    </dd>
                </div>
            </dl>

            <div className="mt-auto flex flex-wrap items-center gap-2">
                <Button variant="default" size="sm" className="rounded-pill" asChild>
                    <AppLink href={`/campaigns/${campaign.id}`}>Open</AppLink>
                </Button>
                <Button variant="outline" size="sm" className="rounded-pill" asChild>
                    <AppLink href={`/campaigns/${campaign.id}/brief`}>
                        <Pencil className="size-4" />
                        Brief
                    </AppLink>
                </Button>
                <Button variant="outline" size="sm" className="rounded-pill" asChild>
                    <AppLink href={`/collaboration?campaign=${campaign.id}`}>
                        <Users className="size-4" />
                        Collabs
                    </AppLink>
                </Button>
                {canDelete && onDelete ? (
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="rounded-pill"
                        title="Delete"
                        onClick={() => onDelete(campaign)}
                    >
                        <Trash2 className="size-4" />
                    </Button>
                ) : null}
            </div>
        </SoftCard>
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
