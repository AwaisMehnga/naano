import { Linkedin, Star } from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import {
    compact,
    countryLabel,
    estimatedCpm,
    euros,
    initials,
} from '@/company/pages/creators/format';
import type { CreatorListItem } from '@/company/pages/creators/types';
import { cn } from '@/lib/utils';

export default function CreatorCard({
    creator,
    starred,
    onOpen,
    onBook,
    onStar,
}: {
    creator: CreatorListItem;
    starred: boolean;
    onOpen: () => void;
    onBook: () => void;
    onStar: () => void;
}) {
    const cpm = estimatedCpm(creator.from_price_cents, creator.followers_count);

    return (
        <article className="border-border bg-card flex h-full flex-col overflow-hidden rounded-2xl border">
            <div className="bg-muted relative h-24">
                <div className="absolute top-3 left-3 flex items-center gap-2">
                    {creator.linkedin_url ? (
                        <a
                            href={creator.linkedin_url}
                            target="_blank"
                            rel="noreferrer"
                            onClick={(event) => event.stopPropagation()}
                            className="border-border bg-card text-foreground inline-flex size-8 items-center justify-center rounded-full border"
                            aria-label="LinkedIn"
                        >
                            <Linkedin className="size-3.5" />
                        </a>
                    ) : (
                        <span className="border-border bg-card text-muted-foreground inline-flex size-8 items-center justify-center rounded-full border">
                            <Linkedin className="size-3.5" />
                        </span>
                    )}
                </div>
                <div className="absolute top-3 right-3 flex items-center gap-2">
                    <Button
                        type="button"
                        size="icon"
                        variant="ghost"
                        className="bg-card size-8 rounded-full"
                        aria-label={
                            starred
                                ? 'Remove from shortlist'
                                : 'Add to shortlist'
                        }
                        onClick={(event) => {
                            event.stopPropagation();
                            onStar();
                        }}
                    >
                        <Star
                            className={cn(
                                'size-4',
                                starred && 'fill-primary text-primary',
                            )}
                        />
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        className="h-8 rounded-full px-3"
                        onClick={(event) => {
                            event.stopPropagation();
                            onBook();
                        }}
                    >
                        Book
                    </Button>
                </div>
            </div>
            <div className="flex flex-1 flex-col px-5 pb-4">
                <button
                    type="button"
                    className="flex w-full flex-col items-center text-center"
                    onClick={onOpen}
                >
                    <Avatar className="border-card -mt-10 size-20 rounded-full border-4">
                        {creator.photo_url && (
                            <AvatarImage src={creator.photo_url} alt="" />
                        )}
                        <AvatarFallback className="text-lg">
                            {initials(creator.display_name)}
                        </AvatarFallback>
                    </Avatar>
                    <h3 className="mt-3 text-base font-semibold">
                        {creator.display_name ?? 'Untitled creator'}
                    </h3>
                    <p className="text-muted-foreground mt-1 line-clamp-1 text-sm">
                        {creator.headline ?? countryLabel(creator.country)}
                    </p>
                    {creator.niches.length > 0 && (
                        <p className="text-muted-foreground mt-1 line-clamp-1 text-xs">
                            {creator.niches
                                .map((niche) => niche.name)
                                .join(' · ')}
                        </p>
                    )}
                </button>
                <div className="mt-5 grid grid-cols-4 gap-2 text-center">
                    <Stat
                        label="Followers"
                        value={compact(creator.followers_count)}
                    />
                    <Stat label="Jobs done" value="—" />
                    <Stat label="Est. CPM" value={euros(cpm)} />
                    <Stat
                        label="From"
                        value={euros(creator.from_price_cents)}
                    />
                </div>
                <Separator className="my-4" />
                <Button
                    type="button"
                    variant="ghost"
                    className="w-full justify-between px-1 text-sm"
                    onClick={onOpen}
                >
                    View profile
                    <span aria-hidden>→</span>
                </Button>
            </div>
        </article>
    );
}

function Stat({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <p className="text-sm font-semibold">{value}</p>
            <p className="text-muted-foreground text-[10px] tracking-wide uppercase">
                {label}
            </p>
        </div>
    );
}
