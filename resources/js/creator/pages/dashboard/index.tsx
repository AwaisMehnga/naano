import { useEffect, useState } from 'react';
import { AppLink } from '@/components/app-link';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { compact, euros } from '@/company/pages/creators/format';
import DashboardCreatorCard, {
    type DashboardCreator,
} from '@/creator/pages/dashboard/creator-card';
import OpportunityCard from '@/creator/pages/opportunities/opportunity-card';
import type { Opportunity } from '@/creator/pages/opportunities/types';
import { ApiError, creatorApi, http } from '@/lib/api';

type Overview = {
    impressions: number;
    engagement: number;
    public_posts_count: number;
    followers_count: number | null;
    earnings_cents: number;
};

type Profile = {
    display_name: string | null;
    linkedin_url: string | null;
    headline: string | null;
    photo_url: string | null;
    country: string | null;
    niches: Array<{ id: number; name: string }>;
};

type Audience = {
    followers_count: number | null;
};

type Deal = {
    id: number;
    status: string;
    campaign: { name: string };
    company: { name: string | null };
};

const activeStatuses = new Set(['booked', 'selected']);

export default function CreatorDashboardPage() {
    const [overview, setOverview] = useState<Overview | null>(null);
    const [creator, setCreator] = useState<DashboardCreator | null>(null);
    const [recommended, setRecommended] = useState<Opportunity[]>([]);
    const [deals, setDeals] = useState<Deal[]>([]);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        Promise.all([
            http.get<Overview>(creatorApi.analyticsOverview),
            http.get<Profile>(creatorApi.profile),
            http.get<Audience>(creatorApi.audience),
            http.get<Opportunity[]>(creatorApi.opportunities({ limit: 3 })),
            http.get<Deal[]>(creatorApi.collaborations()),
        ])
            .then(
                ([
                    { data: nextOverview },
                    { data: profile },
                    { data: audience },
                    { data: opportunities },
                    { data: collaborations },
                ]) => {
                    setOverview(nextOverview);
                    setCreator({
                        display_name: profile.display_name,
                        linkedin_url: profile.linkedin_url,
                        headline: profile.headline,
                        photo_url: profile.photo_url,
                        country: profile.country,
                        niches: profile.niches,
                        followers_count:
                            nextOverview.followers_count ??
                            audience.followers_count,
                        jobs_done: collaborations.filter(
                            (deal) => deal.status === 'completed',
                        ).length,
                    });
                    setRecommended(opportunities);
                    setDeals(
                        collaborations.filter((deal) =>
                            activeStatuses.has(deal.status),
                        ),
                    );
                    setError(null);
                },
            )
            .catch((caught: unknown) => {
                setError(
                    caught instanceof ApiError
                        ? caught.message
                        : 'Could not load your dashboard.',
                );
            });
    }, []);

    return (
        <div className="flex w-full flex-1 flex-col gap-8">
            <div>
                <h1 className="text-2xl font-semibold tracking-tight">
                    Dashboard
                </h1>
                <p className="text-muted-foreground mt-1 text-sm">
                    Public LinkedIn posts, reach, and booked work.
                </p>
            </div>
            <InputError message={error ?? undefined} />
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <Stat
                    label="Public posts"
                    value={formatNumber(overview?.public_posts_count)}
                />
                <Stat
                    label="Reach"
                    value={formatNumber(overview?.impressions)}
                />
                <Stat
                    label="Public engagement"
                    value={formatNumber(overview?.engagement)}
                />
                <Stat
                    label="Followers"
                    value={compact(overview?.followers_count)}
                />
                <Stat
                    label="Earnings"
                    value={
                        overview
                            ? euros(overview.earnings_cents)
                            : '—'
                    }
                />
            </div>
            <div className="grid gap-6 xl:grid-cols-[minmax(16rem,20rem)_1fr]">
                {creator && <DashboardCreatorCard creator={creator} />}
                <section className="flex min-w-0 flex-col gap-4">
                    <div className="flex items-end justify-between gap-3">
                        <div>
                            <h2 className="text-lg font-semibold">
                                Recommended
                            </h2>
                            <p className="text-muted-foreground text-sm">
                                Top matches for your profile.
                            </p>
                        </div>
                        <Button variant="ghost" size="sm" asChild>
                            <AppLink href="/opportunities">See all</AppLink>
                        </Button>
                    </div>
                    {recommended.length === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            No matching campaigns yet.
                        </p>
                    ) : (
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            {recommended.map((item) => (
                                <OpportunityCard
                                    key={item.id}
                                    opportunity={item}
                                />
                            ))}
                        </div>
                    )}
                </section>
            </div>
            <section className="flex flex-col gap-4">
                <h2 className="text-lg font-semibold">
                    Active collaborations
                </h2>
                {deals.length === 0 ? (
                    <p className="text-muted-foreground text-sm">
                        No booked or selected deals yet.
                    </p>
                ) : (
                    <div className="grid gap-2">
                        {deals.map((deal) => (
                            <AppLink
                                key={deal.id}
                                href={`/deals/${deal.id}`}
                                className="border-border hover:bg-muted/40 flex flex-wrap items-center justify-between gap-3 rounded-lg border px-4 py-3"
                            >
                                <div>
                                    <p className="font-medium">
                                        {deal.campaign.name}
                                    </p>
                                    <p className="text-muted-foreground text-sm capitalize">
                                        {deal.company.name} · {deal.status}
                                    </p>
                                </div>
                                <span className="text-sm">Open</span>
                            </AppLink>
                        ))}
                    </div>
                )}
            </section>
        </div>
    );
}

function Stat({ label, value }: { label: string; value: string }) {
    return (
        <div className="border-border bg-card rounded-lg border p-5">
            <p className="text-muted-foreground text-sm">{label}</p>
            <p className="mt-2 text-2xl font-semibold">{value}</p>
        </div>
    );
}

function formatNumber(value: number | undefined): string {
    if (value === undefined) {
        return '—';
    }

    return new Intl.NumberFormat('en-GB').format(value);
}
