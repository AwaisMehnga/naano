import type { ReactNode } from 'react';
import { ExternalLink, Zap } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

export type MediaCardStat = {
    icon: ReactNode;
    value: string;
    label: string;
};

type CampaignOpportunityCardProps = {
    badgeLabel: string;
    badgeIcon?: ReactNode;
    companyName: string;
    companyLogoUrl?: string | null;
    companyWebsite?: string | null;
    title: string;
    tagline?: string | null;
    stats: [MediaCardStat, MediaCardStat, MediaCardStat];
    secondaryLabel: string;
    primaryLabel: string;
    onSecondary: () => void;
    onPrimary: () => void;
    className?: string;
};

/**
 * Soft-canvas media card: image hero, lime badge, company link, 3 stats, dual CTAs.
 */
export function CampaignOpportunityCard({
    badgeLabel,
    badgeIcon,
    companyName,
    companyLogoUrl,
    companyWebsite,
    title,
    tagline,
    stats,
    secondaryLabel,
    primaryLabel,
    onSecondary,
    onPrimary,
    className,
}: CampaignOpportunityCardProps) {
    const websiteHref = absoluteUrl(companyWebsite);
    const [left, middle, right] = stats;
    const initial = companyName.slice(0, 1).toUpperCase();

    return (
        <article
            className={cn(
                'flex flex-col overflow-hidden rounded-3xl border border-border bg-card text-card-foreground',
                className,
            )}
        >
            <div className="relative isolate min-h-48 overflow-hidden bg-muted">
                {companyLogoUrl ? (
                    <img
                        src={companyLogoUrl}
                        alt=""
                        loading="lazy"
                        decoding="async"
                        className="absolute inset-0 size-full object-cover"
                    />
                ) : (
                    <>
                        <div className="absolute inset-0 bg-lime-soft" />
                        <span
                            aria-hidden
                            className="pointer-events-none absolute -right-2 bottom-0 text-[7.5rem] leading-none font-semibold tracking-tight text-primary/10 select-none"
                        >
                            {initial}
                        </span>
                    </>
                )}

                <div className="relative flex h-full min-h-48 flex-col p-5">
                    <span className="inline-flex w-fit items-center gap-1.5 rounded-pill bg-accent px-2.5 py-1 text-xs font-medium text-accent-foreground">
                        {badgeIcon ?? <Zap className="size-3.5" />}
                        {badgeLabel}
                    </span>
                </div>
            </div>

            <div className="flex flex-1 flex-col gap-5 p-5 pt-3">
                <div className="space-y-3">
                    

                    

                    <div className="space-y-1">
                        <h2 className="text-xl font-semibold tracking-tight text-balance">
                            {title}
                        </h2>

                        <div className="flex justify-between">
                        {tagline ? (
                            <p className="text-sm text-muted-foreground text-balance">
                                {tagline}
                            </p>
                        ) : null}
                        <div>
                        {websiteHref ? (
                            <a
                                href={websiteHref}
                                target="_blank"
                                rel="noreferrer"
                                className="text-muted-foreground transition-colors hover:text-foreground flex items-center justify-center gap-1.5"
                                aria-label={`${companyName} website`}
                            >
                                <p className="text-xs font-medium tracking-[0.12em] text-muted-foreground uppercase">
                                    {companyName}
                                </p>
                                <ExternalLink className="size-3.5" />
                            </a>
                        ) : null}
                        </div>
                    </div>
                    </div>
                </div>

                <div className="grid grid-cols-3 gap-2 border-y border-border py-4">
                    <Stat {...left} />
                    <Stat {...middle} className="border-x border-border px-2" />
                    <Stat {...right} />
                </div>

                <div className="mt-auto flex gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        className="flex-1 rounded-pill"
                        onClick={onSecondary}
                    >
                        {secondaryLabel}
                    </Button>
                    <Button
                        type="button"
                        variant="accent"
                        className="flex-1 rounded-pill"
                        onClick={onPrimary}
                    >
                        {primaryLabel}
                    </Button>
                </div>
            </div>
        </article>
    );
}

function Stat({
    icon,
    value,
    label,
    className,
}: MediaCardStat & { className?: string }) {
    return (
        <div className={cn('flex items-start gap-2', className)}>
            <span className="mt-0.5 text-muted-foreground">{icon}</span>
            <div className="min-w-0">
                <p className="truncate text-sm font-semibold">{value}</p>
                <p className="text-xs text-muted-foreground">{label}</p>
            </div>
        </div>
    );
}

function absoluteUrl(value: string | null | undefined): string | null {
    if (!value) {
        return null;
    }

    const trimmed = value.trim();

    if (!trimmed) {
        return null;
    }

    if (/^https?:\/\//i.test(trimmed)) {
        return trimmed;
    }

    return `https://${trimmed}`;
}
