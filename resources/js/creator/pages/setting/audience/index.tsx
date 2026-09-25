import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { SoftCard } from '@/components/ds/soft-card';
import { MetricStat } from '@/components/ds/metric-stat';
import { ProgressRow } from '@/components/ds/progress-row';
import { Button } from '@/components/ui/button';
import { api, ApiError, creatorApi } from '@/lib/api';
import { compact } from '@/company/pages/creators/format';

type AudienceMix = Record<string, Record<string, number>>;

type Engagers = {
    people_count: number;
    reply_rate: number | null;
    seniority: Array<{ label: string; count: number }>;
    job_title?: Array<{ label: string; count: number }>;
    locations: Array<{ label: string; count: number }>;
    top: Array<{
        name: string | null;
        headline: string | null;
        profile_url: string | null;
    }>;
};

type Audience = {
    followers_count: number | null;
    connections_count: number | null;
    network: string;
    audience_mix: AudienceMix | Record<string, unknown>;
    engagers: Engagers | null;
    captured_at: string | null;
};

function formatCaptured(value: string | null): string {
    if (!value) {
        return 'No snapshot yet';
    }

    try {
        return `Captured ${new Date(value).toLocaleString()}`;
    } catch {
        return `Captured ${value}`;
    }
}

function MixGroup({
    title,
    shares,
}: {
    title: string;
    shares: Record<string, number>;
}) {
    const entries = Object.entries(shares).sort((a, b) => b[1] - a[1]);

    if (entries.length === 0) {
        return null;
    }

    return (
        <SoftCard title={title.replaceAll('_', ' ')}>
            <div className="flex flex-col gap-3">
                {entries.slice(0, 8).map(([label, share]) => (
                    <ProgressRow
                        key={label}
                        label={label}
                        value={Number(share)}
                    />
                ))}            </div>
        </SoftCard>
    );
}

export default function CreatorAudiencePage() {
    const [audience, setAudience] = useState<Audience | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [refreshing, setRefreshing] = useState(false);

    useEffect(() => {
        api<Audience>(creatorApi.audience)
            .then(setAudience)
            .catch((caught: unknown) => {
                setError(
                    caught instanceof ApiError
                        ? caught.message
                        : 'Could not load audience.',
                );
            });
    }, []);

    async function refresh() {
        setRefreshing(true);
        setError(null);
        try {
            const data = await api<Audience>(creatorApi.audience, {
                method: 'POST',
            });
            setAudience(data);
            toast.success('Audience snapshot updated.');
        } catch (caught: unknown) {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not refresh audience.',
            );
        } finally {
            setRefreshing(false);
        }
    }

    const mix = audience?.audience_mix ?? {};
    const mixGroups = Object.entries(mix).filter(
        (entry): entry is [string, Record<string, number>] =>
            typeof entry[1] === 'object' &&
            entry[1] !== null &&
            !Array.isArray(entry[1]),
    );
    const engagers = audience?.engagers;

    return (
        <div className="space-y-6">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <Heading
                    title="Audience"
                    description="Followers, connections, and engager mix from your LinkedIn sync. Brands use this for fit."
                />
                <Button
                    type="button"
                    onClick={refresh}
                    disabled={refreshing}
                >
                    {refreshing ? 'Refreshing…' : 'Refresh snapshot'}
                </Button>
            </div>

            <InputError message={error ?? undefined} />

            <p className="text-sm text-muted-foreground">
                {formatCaptured(audience?.captured_at ?? null)}
                {audience?.network ? ` · ${audience.network}` : ''}
            </p>

            <div className="grid gap-4 sm:grid-cols-2">
                <SoftCard>
                    <MetricStat
                        value={
                            audience?.followers_count != null
                                ? compact(audience.followers_count)
                                : '—'
                        }
                        label="Followers"
                    />
                </SoftCard>
                <SoftCard>
                    <MetricStat
                        value={
                            audience?.connections_count != null
                                ? compact(audience.connections_count)
                                : '—'
                        }
                        label="Connections"
                    />
                </SoftCard>
            </div>

            {mixGroups.length > 0 ? (
                <div className="grid gap-4 md:grid-cols-2">
                    {mixGroups.map(([title, shares]) => (
                        <MixGroup key={title} title={title} shares={shares} />
                    ))}
                </div>
            ) : (
                <SoftCard title="Audience mix">
                    <p className="text-sm text-muted-foreground">
                        Mix fills in after LinkedIn posts sync with comments.
                        Verify LinkedIn, wait for posts, then refresh.
                    </p>
                </SoftCard>
            )}

            {engagers && engagers.top.length > 0 ? (
                <SoftCard title="Top engagers">
                    <ul className="flex flex-col gap-3">
                        {engagers.top.slice(0, 10).map((person, index) => (
                            <li
                                key={person.profile_url ?? person.name ?? index}
                                className="border-b border-border pb-3 last:border-0 last:pb-0"
                            >
                                <p className="text-sm font-medium">
                                    {person.profile_url ? (
                                        <a
                                            href={person.profile_url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="underline-offset-2 hover:underline"
                                        >
                                            {person.name ?? 'Unknown'}
                                        </a>
                                    ) : (
                                        (person.name ?? 'Unknown')
                                    )}
                                </p>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    {person.headline ?? '—'}
                                </p>
                            </li>
                        ))}
                    </ul>
                </SoftCard>
            ) : null}
        </div>
    );
}
