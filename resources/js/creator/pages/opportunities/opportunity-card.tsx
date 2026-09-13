import { AppLink } from '@/components/app-link';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { initials } from '@/company/pages/creators/format';
import {
    deadlineLabel,
    locationLabel,
    scoreLabel,
} from '@/creator/pages/opportunities/format';
import type { Opportunity } from '@/creator/pages/opportunities/types';

export default function OpportunityCard({
    opportunity,
}: {
    opportunity: Opportunity;
}) {
    return (
        <AppLink
            href={`/opportunities/${opportunity.id}`}
            className="border-border bg-card flex h-full flex-col rounded-lg border p-5"
        >
            <div className="flex items-start gap-3">
                <Avatar className="size-12 rounded-lg">
                    {opportunity.company.logo_url && (
                        <AvatarImage
                            src={opportunity.company.logo_url}
                            alt=""
                        />
                    )}
                    <AvatarFallback className="rounded-lg text-sm">
                        {initials(opportunity.company.name)}
                    </AvatarFallback>
                </Avatar>
                <div className="min-w-0 flex-1">
                    <p className="text-muted-foreground text-sm">
                        {opportunity.company.name ?? 'Company'}
                    </p>
                    <h3 className="mt-0.5 text-base font-semibold">
                        {opportunity.name}
                    </h3>
                </div>
                <Badge variant="secondary">
                    Match {scoreLabel(opportunity.match_score)}
                </Badge>
            </div>
            <dl className="mt-5 grid grid-cols-2 gap-3 text-sm">
                <div>
                    <dt className="text-muted-foreground">Location</dt>
                    <dd className="mt-1 font-medium">
                        {locationLabel(opportunity.location)}
                    </dd>
                </div>
                <div>
                    <dt className="text-muted-foreground">
                        Audience relevance
                    </dt>
                    <dd className="mt-1 font-medium">
                        {scoreLabel(opportunity.audience_relevance)}
                    </dd>
                </div>
                <div>
                    <dt className="text-muted-foreground">Deadline</dt>
                    <dd className="mt-1 font-medium">
                        {deadlineLabel(opportunity.deadline)}
                    </dd>
                </div>
                <div>
                    <dt className="text-muted-foreground">Objective</dt>
                    <dd className="mt-1 font-medium capitalize">
                        {opportunity.objective.replaceAll('_', ' ')}
                    </dd>
                </div>
            </dl>
        </AppLink>
    );
}
