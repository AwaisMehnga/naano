import {
    CartesianGrid,
    Legend,
    Line,
    LineChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import { ExternalLink } from 'lucide-react';
import { SoftCard } from '@/components/ds/soft-card';
import { MetricStat } from '@/components/ds/metric-stat';
import { ProgressRow } from '@/components/ds/progress-row';
import type {
    LinkedInInsights,
    LinkedInPostsStatus,
    LinkedInTopPost,
} from '@/components/linkedin/types';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { compact, euros, initials } from '@/company/pages/creators/format';
import { cn } from '@/lib/utils';

function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    try {
        return new Date(value).toLocaleDateString(undefined, {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
        });
    } catch {
        return '—';
    }
}

function postsEmptyMessage(status: LinkedInPostsStatus): string {
    switch (status) {
        case 'syncing':
            return 'Posts are still syncing. This usually finishes within a couple of minutes.';
        case 'failed':
            return 'Posts sync failed. Retry sync to pull your public posts again.';
        case 'ready':
            return 'No public posts were found on this LinkedIn profile.';
        default:
            return 'No posts synced yet. Run a posts sync to load engagement.';
    }
}

function PostList({
    posts,
    empty,
}: {
    posts: LinkedInTopPost[];
    empty: string;
}) {
    if (posts.length === 0) {
        return <p className="text-sm text-muted-foreground">{empty}</p>;
    }

    return (
        <ul className="flex flex-col gap-5">
            {posts.map((post) => (
                <li
                    key={post.id ?? post.linkedin_url ?? post.content}
                    className="border-b border-border pb-5 last:border-0 last:pb-0"
                >
                    <p className="text-sm leading-relaxed whitespace-pre-wrap">
                        {post.content?.trim() || 'Untitled post'}
                    </p>
                    <div className="mt-3 flex flex-wrap items-center gap-2">
                        <span className="rounded-pill bg-muted px-3 py-1 text-xs font-medium tabular-nums">
                            {compact(post.likes)} reactions
                        </span>
                        <span className="rounded-pill bg-muted px-3 py-1 text-xs font-medium tabular-nums">
                            {compact(post.comments)} comments
                        </span>
                        {post.shares > 0 ? (
                            <span className="rounded-pill bg-muted px-3 py-1 text-xs font-medium tabular-nums">
                                {compact(post.shares)} shares
                            </span>
                        ) : null}
                        {post.posted_at ? (
                            <span className="text-xs text-muted-foreground">
                                {formatDate(post.posted_at)}
                            </span>
                        ) : null}
                        {post.linkedin_url ? (
                            <a
                                href={post.linkedin_url}
                                target="_blank"
                                rel="noreferrer"
                                className="inline-flex items-center gap-1.5 rounded-pill bg-accent px-3 py-1 text-xs font-medium text-accent-foreground"
                            >
                                <ExternalLink className="size-3" />
                                View on LinkedIn
                            </a>
                        ) : null}
                    </div>
                </li>
            ))}
        </ul>
    );
}

export function LinkedInInsightsPanel({
    insights,
    className,
    compactHeader = false,
    onRetrySync,
    syncing = false,
}: {
    insights: LinkedInInsights;
    className?: string;
    compactHeader?: boolean;
    onRetrySync?: () => void;
    syncing?: boolean;
}) {
    const {
        header,
        stats,
        engagement_series,
        top_posts,
        recent_posts = [],
        engagers,
        background,
        posts_status,
    } = insights;

    const postsStatus = posts_status ?? 'idle';
    const emptyCopy = postsEmptyMessage(postsStatus);
    const avgMetric =
        stats.avg_reactions !== null ? compact(stats.avg_reactions) : '—';
    const showSyncBanner =
        postsStatus === 'syncing' ||
        postsStatus === 'failed' ||
        (postsStatus !== 'ready' && stats.posts_count === 0);

    return (
        <div className={cn('flex flex-col gap-6', className)}>
            {showSyncBanner ? (
                <SoftCard
                    className={cn(
                        postsStatus === 'syncing' &&
                            'border-transparent bg-lime-soft',
                    )}
                >
                    <div className="flex flex-wrap items-center justify-between gap-4">
                        <div className="flex items-start gap-3">
                            {postsStatus === 'syncing' || syncing ? (
                                <Spinner className="mt-0.5 size-4 shrink-0" />
                            ) : null}
                            <div>
                                <p className="text-sm font-medium">
                                    {postsStatus === 'failed'
                                        ? 'Posts sync failed'
                                        : postsStatus === 'syncing'
                                          ? 'Syncing LinkedIn posts'
                                          : 'Posts not loaded yet'}
                                </p>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {emptyCopy}
                                </p>
                            </div>
                        </div>
                        {onRetrySync && postsStatus !== 'syncing' ? (
                            <Button
                                type="button"
                                variant="accent"
                                size="sm"
                                disabled={syncing}
                                onClick={onRetrySync}
                            >
                                Retry sync
                            </Button>
                        ) : null}
                    </div>
                </SoftCard>
            ) : null}

            {!compactHeader ? (
                <SoftCard>
                    <div className="flex flex-wrap items-start gap-5">
                        <Avatar className="size-20 shrink-0 rounded-full">
                            {header.picture_url ? (
                                <AvatarImage src={header.picture_url} alt="" />
                            ) : null}
                            <AvatarFallback className="bg-lime-soft text-lime-soft-foreground">
                                {initials(header.name)}
                            </AvatarFallback>
                        </Avatar>
                        <div className="min-w-0 flex-1 space-y-2">
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div className="min-w-0 space-y-2">
                                    <p className="text-heading font-medium tracking-tight">
                                        {header.name ?? 'LinkedIn profile'}
                                    </p>
                                    <p className="text-sm leading-relaxed whitespace-pre-wrap text-muted-foreground">
                                        {header.headline ?? '—'}
                                    </p>
                                    {header.job_title ? (
                                        <p className="text-sm">
                                            {header.job_title}
                                            {header.company?.name
                                                ? ` at ${header.company.name}`
                                                : ''}
                                        </p>
                                    ) : null}
                                    <p className="text-sm text-muted-foreground">
                                        {[header.location, header.company?.name]
                                            .filter(Boolean)
                                            .join(' · ') || '—'}
                                        {header.captured_at
                                            ? ` · Captured ${formatDate(header.captured_at)}`
                                            : ''}
                                    </p>
                                </div>
                                {insights.linkedin_url ? (
                                    <a
                                        href={insights.linkedin_url}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="inline-flex items-center gap-1.5 rounded-pill bg-accent px-4 py-2 text-sm font-medium text-accent-foreground"
                                    >
                                        <ExternalLink className="size-3.5" />
                                        Open LinkedIn
                                    </a>
                                ) : null}
                            </div>
                        </div>
                    </div>
                </SoftCard>
            ) : (
                <div className="flex items-center gap-3">
                    <Avatar className="size-12 shrink-0 rounded-full">
                        {header.picture_url ? (
                            <AvatarImage src={header.picture_url} alt="" />
                        ) : null}
                        <AvatarFallback className="bg-lime-soft text-lime-soft-foreground">
                            {initials(header.name)}
                        </AvatarFallback>
                    </Avatar>
                    <div className="min-w-0">
                        <p className="text-sm font-medium">
                            {header.name ?? 'LinkedIn profile'}
                        </p>
                        <p className="text-xs leading-relaxed text-muted-foreground whitespace-pre-wrap">
                            {header.headline ?? '—'}
                        </p>
                    </div>
                </div>
            )}

            <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                <SoftCard>
                    <MetricStat
                        value={
                            stats.followers !== null
                                ? compact(stats.followers)
                                : '—'
                        }
                        label="Followers"
                    />
                </SoftCard>
                <SoftCard>
                    <MetricStat
                        value={
                            stats.connections !== null
                                ? compact(stats.connections)
                                : '—'
                        }
                        label="Connections"
                    />
                </SoftCard>
                <SoftCard>
                    <MetricStat
                        value={String(stats.posts_count)}
                        label="Posts synced"
                        hint={`Last posted ${formatDate(stats.last_posted_at)}`}
                    />
                </SoftCard>
                <SoftCard>
                    <MetricStat
                        value={
                            stats.asking_rate_cents !== null
                                ? euros(stats.asking_rate_cents)
                                : '—'
                        }
                        label="Asking rate"
                        hint={
                            stats.avg_comments !== null
                                ? `Avg ${stats.avg_comments} comments`
                                : undefined
                        }
                    />
                </SoftCard>
            </div>

            <SoftCard title="Engagement trend">
                {engagement_series.length > 0 ? (
                    <>
                        <MetricStat
                            value={avgMetric}
                            label="Avg reactions / post"
                            hint={
                                stats.avg_comments !== null
                                    ? `Avg ${stats.avg_comments} comments across ${stats.posts_count} posts`
                                    : undefined
                            }
                        />
                        <div className="mt-6 h-56">
                            <ResponsiveContainer width="100%" height="100%">
                                <LineChart data={engagement_series}>
                                    <CartesianGrid
                                        vertical={false}
                                        stroke="var(--border)"
                                        strokeDasharray="3 3"
                                    />
                                    <XAxis
                                        dataKey="label"
                                        axisLine={false}
                                        tickLine={false}
                                        interval="preserveStartEnd"
                                        tick={{
                                            fill: 'var(--muted-foreground)',
                                            fontSize: 11,
                                        }}
                                    />
                                    <YAxis
                                        axisLine={false}
                                        tickLine={false}
                                        width={36}
                                        tick={{
                                            fill: 'var(--muted-foreground)',
                                            fontSize: 12,
                                        }}
                                    />
                                    <Tooltip
                                        contentStyle={{
                                            borderRadius: 12,
                                            border: '1px solid var(--border)',
                                            background: 'var(--card)',
                                        }}
                                    />
                                    <Legend />
                                    <Line
                                        type="monotone"
                                        dataKey="reactions"
                                        name="Reactions"
                                        stroke="var(--chart-1)"
                                        strokeWidth={2}
                                        dot={false}
                                    />
                                    <Line
                                        type="monotone"
                                        dataKey="comments"
                                        name="Comments"
                                        stroke="var(--chart-2)"
                                        strokeWidth={2}
                                        dot={false}
                                    />
                                </LineChart>
                            </ResponsiveContainer>
                        </div>
                    </>
                ) : (
                    <p className="text-sm text-muted-foreground">{emptyCopy}</p>
                )}
            </SoftCard>

            <div className="grid gap-5 lg:grid-cols-2">
                <SoftCard title="Top posts">
                    <PostList posts={top_posts} empty={emptyCopy} />
                </SoftCard>
                <SoftCard title="Recent posts">
                    <PostList posts={recent_posts} empty={emptyCopy} />
                </SoftCard>
            </div>

            {engagers ? (
                <div className="grid gap-5 lg:grid-cols-2">
                    <SoftCard title="Who engages">
                        <p className="mb-4 text-sm text-muted-foreground">
                            {compact(engagers.people_count)} people
                            {engagers.reply_rate !== null
                                ? ` · ${(engagers.reply_rate * 100).toFixed(0)}% posts with comments`
                                : ''}
                        </p>
                        <div className="flex flex-col gap-3">
                            {engagers.seniority.map((row) => (
                                <ProgressRow
                                    key={row.label}
                                    label={row.label}
                                    value={Math.round(
                                        (row.count /
                                            (engagers.people_count || 1)) *
                                            100,
                                    )}
                                />
                            ))}
                        </div>
                    </SoftCard>
                    <SoftCard title="Top engagers">
                        <ul className="flex flex-col gap-3">
                            {engagers.top.slice(0, 8).map((person, index) => (
                                <li
                                    key={
                                        person.profile_url ??
                                        person.name ??
                                        index
                                    }
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
                                    <p className="text-xs text-muted-foreground">
                                        {person.headline ?? '—'}
                                    </p>
                                </li>
                            ))}
                            {engagers.top.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Comment engagers appear after a posts sync
                                    with comments enabled.
                                </p>
                            ) : null}
                        </ul>
                    </SoftCard>
                </div>
            ) : null}

            {(background.summary ||
                background.positions.length > 0 ||
                background.educations.length > 0 ||
                background.skills.length > 0) && (
                <div className="grid gap-5">
                    {background.summary ? (
                        <SoftCard title="About">
                            <p className="whitespace-pre-wrap text-sm leading-7">
                                {background.summary}
                            </p>
                        </SoftCard>
                    ) : null}

                    <div className="grid gap-5 lg:grid-cols-2">
                        <SoftCard title="Experience">
                            {background.positions.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No experience listed.
                                </p>
                            ) : (
                                <ul className="flex flex-col gap-6">
                                    {background.positions.map((row, i) => (
                                        <li
                                            key={`${row.title}-${row.company}-${i}`}
                                            className="space-y-2"
                                        >
                                            <p className="text-sm font-medium">
                                                {row.title ?? 'Role'}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {[
                                                    row.company,
                                                    row.location,
                                                    [
                                                        row.start,
                                                        row.end || 'Present',
                                                    ]
                                                        .filter(Boolean)
                                                        .join(' – '),
                                                ]
                                                    .filter(Boolean)
                                                    .join(' · ')}
                                            </p>
                                            {row.description ? (
                                                <p className="whitespace-pre-wrap text-sm leading-7">
                                                    {row.description}
                                                </p>
                                            ) : null}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </SoftCard>

                        <div className="flex flex-col gap-5">
                            <SoftCard title="Education">
                                {background.educations.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        No education listed.
                                    </p>
                                ) : (
                                    <ul className="flex flex-col gap-4">
                                        {background.educations.map(
                                            (row, i) => (
                                                <li
                                                    key={`${row.school}-${i}`}
                                                    className="space-y-1"
                                                >
                                                    <p className="text-sm font-medium">
                                                        {row.school ?? 'School'}
                                                    </p>
                                                    <p className="text-sm text-muted-foreground">
                                                        {[
                                                            row.degree,
                                                            row.field,
                                                            [row.start, row.end]
                                                                .filter(Boolean)
                                                                .join(' – '),
                                                        ]
                                                            .filter(Boolean)
                                                            .join(' · ')}
                                                    </p>
                                                </li>
                                            ),
                                        )}
                                    </ul>
                                )}
                            </SoftCard>

                            <SoftCard title="Skills">
                                {background.skills.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        No skills listed.
                                    </p>
                                ) : (
                                    <div className="flex flex-wrap gap-2">
                                        {background.skills.map((skill) => (
                                            <Badge key={skill} variant="soft">
                                                {skill}
                                            </Badge>
                                        ))}
                                    </div>
                                )}
                            </SoftCard>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
