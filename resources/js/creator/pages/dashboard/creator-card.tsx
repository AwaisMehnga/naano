import { Linkedin } from 'lucide-react';
import { AppLink } from '@/components/app-link';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
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
        <article className="border-border bg-card flex h-full flex-col overflow-hidden rounded-lg border">
            <div className="bg-muted relative h-24">
                <div className="absolute top-3 left-3">
                    {creator.linkedin_url ? (
                        <a
                            href={creator.linkedin_url}
                            target="_blank"
                            rel="noreferrer"
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
            </div>
            <div className="flex flex-1 flex-col px-5 pb-4">
                <div className="flex flex-col items-center text-center">
                    <Avatar className="border-card -mt-10 size-20 rounded-full border-4">
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
                </div>
                <div className="mt-5 grid grid-cols-2 gap-2 text-center">
                    <div>
                        <p className="text-sm font-semibold">
                            {compact(creator.followers_count)}
                        </p>
                        <p className="text-muted-foreground text-[10px] tracking-wide uppercase">
                            Followers
                        </p>
                    </div>
                    <div>
                        <p className="text-sm font-semibold">
                            {creator.jobs_done}
                        </p>
                        <p className="text-muted-foreground text-[10px] tracking-wide uppercase">
                            Jobs done
                        </p>
                    </div>
                </div>
                <Separator className="my-4" />
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
        </article>
    );
}
