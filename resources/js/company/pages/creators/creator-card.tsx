import { Zap } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    compact,
    countryLabel,
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
    const niche =
        creator.niches[0]?.name ??
        creator.headline ??
        'LinkedIn creator';
    const location = countryLabel(creator.country);
    const meta =
        location !== '—'
            ? `${niche} · ${location}`
            : niche;

    return (
        <article className="relative flex min-h-112 flex-col overflow-hidden rounded-3xl border border-border bg-primary text-primary-foreground">
            <div className="absolute inset-0">
                {creator.photo_url ? (
                    <img
                        src={creator.photo_url}
                        alt=""
                        loading="lazy"
                        decoding="async"
                        className="size-full object-cover object-top"
                    />
                ) : (
                    <div className="flex size-full items-center justify-center bg-accent text-6xl font-semibold text-accent-foreground">
                        {initials(creator.display_name)}
                    </div>
                )}
                <div className="absolute inset-0 bg-linear-to-t from-primary via-primary/75 to-primary/10" />
            </div>

            <div className="relative z-10 flex flex-1 flex-col justify-end gap-5 p-5">
                <span className="absolute top-4 left-4 inline-flex items-center gap-1.5 rounded-pill bg-primary/70 px-3 py-1.5 text-xs font-medium text-primary-foreground backdrop-blur-sm">
                    <Zap className="size-3.5 text-accent" />
                    Vetted
                </span>

                <button
                    type="button"
                    className="flex w-full flex-col gap-5 text-center"
                    onClick={onOpen}
                >
                    <div className="space-y-1">
                        <h3 className="text-2xl font-semibold tracking-tight text-balance">
                            {creator.display_name ?? 'Untitled creator'}
                        </h3>
                        <p className="line-clamp-1 text-sm text-primary-foreground/70">
                            {meta}
                        </p>
                    </div>

                    <div className="grid grid-cols-3 divide-x divide-primary-foreground/20">
                        <Stat
                            value={compact(creator.followers_count)}
                            label="Followers"
                        />
                        <Stat
                            value={compact(creator.connections_count)}
                            label="Connections"
                        />
                        <Stat
                            value={euros(creator.from_price_cents)}
                            label="Per post"
                        />
                    </div>
                </button>

                <div className="grid grid-cols-2 gap-2">
                    <Button
                        type="button"
                        variant="accent"
                        className="rounded-pill"
                        onClick={(event) => {
                            event.stopPropagation();
                            onBook();
                        }}
                    >
                        Invite
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        className={cn(
                            'rounded-pill border-primary-foreground/30 bg-transparent text-primary-foreground hover:bg-primary-foreground/10 hover:text-primary-foreground',
                            starred &&
                                'border-accent bg-accent/25 text-primary-foreground hover:bg-accent/35 hover:text-primary-foreground',
                        )}
                        onClick={(event) => {
                            event.stopPropagation();
                            onStar();
                        }}
                    >
                        {starred ? 'Shortlisted' : 'Shortlist'}
                    </Button>
                </div>
            </div>
        </article>
    );
}

function Stat({ label, value }: { label: string; value: string }) {
    return (
        <div className="px-2">
            <p className="text-lg font-semibold tracking-tight">{value}</p>
            <p className="text-xs text-primary-foreground/60">{label}</p>
        </div>
    );
}
