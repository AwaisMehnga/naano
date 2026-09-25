import { Linkedin } from 'lucide-react';
import { AppLink } from '@/components/app-link';
import { IconButton, SoftCard } from '@/components/ds';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    compact,
    countryLabel,
    initials,
} from '@/company/pages/creators/format';

export type DashboardCreator = {
    display_name: string | null;
    linkedin_url: string | null;
    headline: string | null;
    photo_url: string | null;
    country: string | null;
    niches: Array<{ id: number; name: string }>;
    followers_count: number | null;
    jobs_done: number;
};

export default function DashboardCreatorCard({
    creator,
}: {
    creator: DashboardCreator;
}) {
    return (
        <SoftCard className="flex h-full flex-col overflow-hidden p-0">
            <div className="relative h-24 bg-accent">
                <div className="absolute top-3 left-3">
                    {creator.linkedin_url ? (
                        <IconButton variant="outline" size="sm" asChild>
                            <a
                                href={creator.linkedin_url}
                                target="_blank"
                                rel="noreferrer"
                                aria-label="LinkedIn"
                            >
                                <Linkedin />
                            </a>
                        </IconButton>
                    ) : (
                        <IconButton
                            variant="outline"
                            size="sm"
                            disabled
                            aria-label="LinkedIn"
                        >
                            <Linkedin />
                        </IconButton>
                    )}
                </div>
            </div>
            <div className="flex flex-1 flex-col px-5 pb-4">
                <div className="flex flex-col items-center text-center">
                    <Avatar className="-mt-10 size-20 rounded-full border-4 border-card">
                        {creator.photo_url && (
                            <AvatarImage src={creator.photo_url} alt="" />
                        )}
                        <AvatarFallback className="text-lg">
                            {initials(creator.display_name)}
                        </AvatarFallback>
                    </Avatar>
                    <h3 className="mt-3 text-base font-semibold">
                        {creator.display_name ?? 'Your profile'}
                    </h3>
                    <p className="mt-1 line-clamp-1 text-sm text-muted-foreground">
                        {creator.headline ?? countryLabel(creator.country)}
                    </p>
                    {creator.niches.length > 0 && (
                        <p className="mt-1 line-clamp-1 text-xs text-muted-foreground">
                            {creator.niches
                                .map((niche) => niche.name)
                                .join(' · ')}
                        </p>
                    )}
                </div>
                <div className="mt-5 grid grid-cols-2 gap-4 text-center">
                    <div>
                        <p className="text-title font-medium tracking-tight">
                            {compact(creator.followers_count)}
                        </p>
                        <p className="text-sm text-muted-foreground">
                            Followers
                        </p>
                    </div>
                    <div>
                        <p className="text-title font-medium tracking-tight">
                            {creator.jobs_done}
                        </p>
                        <p className="text-sm text-muted-foreground">
                            Jobs done
                        </p>
                    </div>
                </div>
                <div className="mt-4 border-t border-border pt-4">
                    <Button
                        type="button"
                        variant="ghost"
                        className="w-full justify-between px-1 text-sm"
                        asChild
                    >
                        <AppLink href="/setting/profile">
                            View profile
                            <span aria-hidden>→</span>
                        </AppLink>
                    </Button>
                </div>
            </div>
        </SoftCard>
    );
}
