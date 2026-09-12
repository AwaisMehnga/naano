import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { api, ApiError, creatorApi } from '@/lib/api';

type Audience = {
    followers_count: number | null;
    network: string;
    audience_mix: Record<string, unknown>;
    captured_at: string | null;
};

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
    const geo =
        mix.geo && typeof mix.geo === 'object'
            ? (mix.geo as Record<string, number>)
            : {};

    return (
        <div className="space-y-6">
            <Heading
                title="Audience"
                description="LinkedIn mix used for brand fit. Live pull ships later."
            />
            <InputError message={error ?? undefined} />
            <Card>
                <CardHeader className="flex flex-row items-center justify-between gap-4">
                    <div>
                        <CardTitle>Latest snapshot</CardTitle>
                        <CardDescription>
                            {audience?.captured_at
                                ? `Captured ${audience.captured_at}`
                                : 'No snapshot yet'}
                        </CardDescription>
                    </div>
                    <Button
                        type="button"
                        onClick={refresh}
                        disabled={refreshing}
                    >
                        {refreshing ? 'Refreshing…' : 'Refresh'}
                    </Button>
                </CardHeader>
                <CardContent className="space-y-3">
                    <p className="text-sm">
                        Followers:{' '}
                        {audience?.followers_count?.toLocaleString() ?? '—'}
                    </p>
                    <p className="text-sm text-muted-foreground">
                        Network: {audience?.network ?? 'linkedin'}
                    </p>
                    {Object.keys(geo).length > 0 ? (
                        <ul className="space-y-1 text-sm">
                            {Object.entries(geo).map(([code, share]) => (
                                <li key={code}>
                                    {code}: {share}%
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            No audience mix stored yet.
                        </p>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}
