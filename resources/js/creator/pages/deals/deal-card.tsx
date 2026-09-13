import { AppLink } from '@/components/app-link';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { euros, initials } from '@/company/pages/creators/format';
import type { Deal } from '@/creator/pages/deals/types';

export default function DealCard({
    deal,
    onMessage,
}: {
    deal: Deal;
    onMessage: (deal: Deal) => void;
}) {
    return (
        <article className="border-border bg-card flex flex-col rounded-md border p-3">
            <div className="flex items-center gap-2">
                <Avatar className="size-8 rounded-md">
                    {deal.company.logo_url && (
                        <AvatarImage src={deal.company.logo_url} alt="" />
                    )}
                    <AvatarFallback className="rounded-md text-xs">
                        {initials(deal.company.name)}
                    </AvatarFallback>
                </Avatar>
                <div className="min-w-0 flex-1">
                    <p className="text-muted-foreground truncate text-xs">
                        {deal.company.name ?? 'Company'}
                    </p>
                    <h3 className="truncate text-sm font-semibold">
                        {deal.campaign.name}
                    </h3>
                </div>
                <Badge variant="secondary" className="shrink-0 capitalize">
                    {label(deal.status)}
                </Badge>
            </div>
            <dl className="mt-3 grid grid-cols-2 gap-x-3 gap-y-1 text-xs">
                <div>
                    <dt className="text-muted-foreground">Source</dt>
                    <dd className="font-medium capitalize">
                        {label(deal.source)}
                    </dd>
                </div>
                <div>
                    <dt className="text-muted-foreground">Objective</dt>
                    <dd className="font-medium capitalize">
                        {label(deal.campaign.objective)}
                    </dd>
                </div>
                <div>
                    <dt className="text-muted-foreground">Price</dt>
                    <dd className="font-medium">
                        {euros(deal.booked_price_cents)}
                    </dd>
                </div>
                <div>
                    <dt className="text-muted-foreground">Posts</dt>
                    <dd className="font-medium">
                        {deal.booked_posts_count ?? '—'}
                    </dd>
                </div>
            </dl>
            <div className="mt-3 flex gap-2">
                <Button type="button" size="sm" variant="outline" asChild>
                    <AppLink href={`/deals/${deal.id}`}>Open</AppLink>
                </Button>
                <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    onClick={() => onMessage(deal)}
                >
                    Message
                </Button>
            </div>
        </article>
    );
}

function label(value: string): string {
    return value.replaceAll('_', ' ');
}
