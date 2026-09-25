import { useEffect, useRef, useState } from 'react';
import { LinkedInInsightsPanel } from '@/components/linkedin/insights-panel';
import type { LinkedInInsights } from '@/components/linkedin/types';
import { SoftCard } from '@/components/ds/soft-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { ApiError, creatorApi, http } from '@/lib/api';
import { toast } from 'sonner';

type BusyAction = 'start' | 'verify' | 'refresh' | 'sync' | null;

const POLL_MS = 5000;
const POLL_MAX_MS = 120_000;

export default function CreatorLinkedInPage() {
    const [insights, setInsights] = useState<LinkedInInsights | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [url, setUrl] = useState('');
    const [code, setCode] = useState<string | null>(null);
    const [busy, setBusy] = useState<BusyAction>(null);
    const pollStartedAt = useRef<number | null>(null);

    function applyInsights(data: LinkedInInsights) {
        setInsights(data);
        setError(null);
        if (data.linkedin_url) {
            setUrl(data.linkedin_url);
        }
        setCode(data.verify_code);
    }

    function load() {
        return http
            .get<LinkedInInsights>(creatorApi.linkedinProfile)
            .then(({ data }) => {
                applyInsights(data);
            })
            .catch((caught: unknown) => {
                setError(
                    caught instanceof ApiError
                        ? caught.message
                        : 'Could not load LinkedIn insights.',
                );
            });
    }

    useEffect(() => {
        void load();
    }, []);

    useEffect(() => {
        if (!insights?.verified) {
            pollStartedAt.current = null;
            return;
        }

        const shouldPoll =
            insights.posts_status === 'syncing' ||
            (insights.posts_count === 0 &&
                insights.posts_status !== 'ready' &&
                insights.posts_status !== 'failed');

        if (!shouldPoll) {
            pollStartedAt.current = null;
            return;
        }

        if (pollStartedAt.current === null) {
            pollStartedAt.current = Date.now();
        }

        const timer = window.setInterval(() => {
            const started = pollStartedAt.current ?? Date.now();

            if (Date.now() - started > POLL_MAX_MS) {
                window.clearInterval(timer);
                return;
            }

            void http
                .get<LinkedInInsights>(creatorApi.linkedinProfile)
                .then(({ data }) => applyInsights(data))
                .catch(() => undefined);
        }, POLL_MS);

        return () => window.clearInterval(timer);
    }, [
        insights?.verified,
        insights?.posts_status,
        insights?.posts_count,
    ]);

    async function startVerification() {
        setBusy('start');

        try {
            const { data } = await http.post<{
                verify_code: string;
                linkedin_url: string;
            }>(creatorApi.linkedinStart, { linkedin_url: url });
            setCode(data.verify_code);
            toast.success('Add this code at the end of your LinkedIn headline.');
            await load();
        } catch (caught) {
            toast.error(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not start verification.',
            );
        } finally {
            setBusy(null);
        }
    }

    async function verify() {
        setBusy('verify');

        try {
            const { data } = await http.post<LinkedInInsights>(
                creatorApi.linkedinVerify,
            );
            applyInsights(data);
            setCode(null);
            toast.success(
                'LinkedIn verified. Posts and audience are syncing.',
            );
        } catch (caught) {
            toast.error(
                caught instanceof ApiError
                    ? caught.message
                    : 'Verification failed.',
            );
        } finally {
            setBusy(null);
        }
    }

    async function refresh() {
        setBusy('refresh');

        try {
            const { data } = await http.post<LinkedInInsights>(
                creatorApi.linkedinRefresh,
            );
            applyInsights(data);
            toast.success('LinkedIn posts and audience updated.');
        } catch (caught) {
            toast.error(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not refresh LinkedIn.',
            );
        } finally {
            setBusy(null);
        }
    }

    async function syncPosts() {
        setBusy('sync');
        pollStartedAt.current = Date.now();

        try {
            const { data } = await http.post<LinkedInInsights>(
                creatorApi.linkedinPostsSync,
            );
            applyInsights(data);
            toast.success('Posts sync started.');
        } catch (caught) {
            toast.error(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not sync LinkedIn posts.',
            );
        } finally {
            setBusy(null);
        }
    }

    if (error && !insights) {
        return <p className="text-sm text-destructive">{error}</p>;
    }

    if (!insights) {
        return (
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                <Spinner className="size-4" />
                Loading LinkedIn…
            </div>
        );
    }

    if (!insights.verified) {
        return (
            <div className="flex max-w-xl flex-col gap-6">
                <div>
                    <h1 className="text-heading font-medium tracking-tight">
                        LinkedIn
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Verify ownership, then we sync your public profile and
                        posts for marketplace insights.
                    </p>
                </div>

                <SoftCard title="Verify ownership">
                    <div className="flex flex-col gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="linkedin_url">
                                Public profile URL
                            </Label>
                            <Input
                                id="linkedin_url"
                                value={url}
                                disabled={busy !== null}
                                onChange={(event) => setUrl(event.target.value)}
                                placeholder="https://www.linkedin.com/in/you"
                            />
                        </div>
                        <Button
                            type="button"
                            disabled={busy !== null || url.trim() === ''}
                            onClick={() => void startVerification()}
                        >
                            {busy === 'start' ? (
                                <>
                                    <Spinner />
                                    Getting code…
                                </>
                            ) : (
                                'Get verification code'
                            )}
                        </Button>
                        {code ? (
                            <div className="rounded-2xl bg-muted p-5">
                                <p className="text-sm text-muted-foreground">
                                    Add this code at the{' '}
                                    <span className="font-medium text-foreground">
                                        end
                                    </span>{' '}
                                    of your LinkedIn headline, save, then verify.
                                </p>
                                <p className="mt-2 font-mono text-2xl font-semibold tracking-widest">
                                    {code}
                                </p>
                                <p className="mt-2 text-xs text-muted-foreground">
                                    Example: Your headline here {code}
                                </p>
                                {busy === 'verify' ? (
                                    <div className="mt-4 flex items-start gap-3 rounded-2xl border border-border bg-card p-4 text-sm">
                                        <Spinner className="mt-0.5 size-4 shrink-0" />
                                        <div className="space-y-1">
                                            <p className="font-medium">
                                                Checking LinkedIn…
                                            </p>
                                            <p className="text-muted-foreground">
                                                Fetching your public profile and
                                                confirming the code is in your
                                                headline. This can take up to a
                                                couple of minutes.
                                            </p>
                                        </div>
                                    </div>
                                ) : null}
                                <Button
                                    type="button"
                                    className="mt-4"
                                    disabled={busy !== null}
                                    onClick={() => void verify()}
                                >
                                    {busy === 'verify' ? (
                                        <>
                                            <Spinner />
                                            Checking LinkedIn…
                                        </>
                                    ) : (
                                        'Verify LinkedIn'
                                    )}
                                </Button>
                            </div>
                        ) : null}
                    </div>
                </SoftCard>
            </div>
        );
    }

    return (
        <div className="flex flex-col gap-6">
            <div className="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 className="text-heading font-medium tracking-tight">
                        LinkedIn insights
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Synced{' '}
                        {insights.synced_at
                            ? new Date(insights.synced_at).toLocaleString()
                            : '—'}
                        {insights.posts_count > 0
                            ? ` · ${insights.posts_count} posts`
                            : ''}
                    </p>
                </div>
                <div className="flex flex-wrap gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        disabled={busy !== null}
                        onClick={() => void syncPosts()}
                    >
                        {busy === 'sync' ? (
                            <>
                                <Spinner />
                                Syncing posts…
                            </>
                        ) : (
                            'Sync posts'
                        )}
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        disabled={busy !== null}
                        onClick={() => void refresh()}
                    >
                        {busy === 'refresh' ? (
                            <>
                                <Spinner />
                                Refreshing…
                            </>
                        ) : (
                            'Refresh profile'
                        )}
                    </Button>
                </div>
            </div>
            <LinkedInInsightsPanel
                insights={insights}
                syncing={busy === 'sync' || insights.posts_status === 'syncing'}
                onRetrySync={() => void syncPosts()}
            />
        </div>
    );
}
