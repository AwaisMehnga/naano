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
import { SoftCard } from '@/components/ds/soft-card';
import { MetricStat } from '@/components/ds/metric-stat';
import { ProgressRow } from '@/components/ds/progress-row';
import type { LinkedInInsights, LinkedInTopPost } from '@/components/linkedin/types';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
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

function truncate(text: string | null, max = 220): string {
    if (!text) {
        return 'Untitled post';
    }

    return text.length > max ? `${text.slice(0, max).trim()}…` : text;
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
        <ul className="flex flex-col gap-4">
            {posts.map((post) => (
                <li
                    key={post.id ?? post.linkedin_url ?? post.content}
                    className="border-b border-border pb-4 last:border-0 last:pb-0"
                >
                    <p className="text-sm leading-relaxed whitespace-pre-wrap">
                        {truncate(post.content, 320)}
                    </p>
                    <p className="mt-2 text-xs text-muted-foreground">
                        {compact(post.likes)} reactions · {compact(post.comments)}{' '}
                        comments
                        {post.shares > 0 ? ` · ${compact(post.shares)} shares` : ''}
                        {post.posted_at ? ` · ${formatDate(post.posted_at)}` : ''}
                        {post.linkedin_url ? (
                            <>
                                {' · '}
                                <a
                                    href={post.linkedin_url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="underline-offset-2 hover:underline"
                                >
                                    View
                                </a>
                            </>
                        ) : null}
                    </p>
                </li>
            ))}
        </ul>
    );
}

export function LinkedInInsightsPanel({
    insights,
    className,
    compactHeader = false,
}: {
    insights: LinkedInInsights;
    className?: string;
    compactHeader?: boolean;
}) {
    const {
        header,
        stats,
        engagement_series,
        top_posts,
        recent_posts = [],
        engagers,
        background,
    } = insights;

    const avgMetric =
        stats.avg_reactions !== null ? compact(stats.avg_reactions) : '—';

    return (
        <div className={cn('flex flex-col gap-6', className)}>
            {!compactHeader ? (
                <div className="flex flex-wrap items-start gap-4">
                    <Avatar className="size-16 shrink-0 rounded-full">
                        {header.picture_url ? (
                            <AvatarImage src={header.picture_url} alt="" />
                        ) : null}
                        <AvatarFallback>
                            {initials(header.name)}
                        </AvatarFallback>
                    </Avatar>
                    <div className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div className="min-w-0">
                                <p className="text-heading font-medium tracking-tight">
                                    {header.name ?? 'LinkedIn profile'}
                                </p>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {header.headline ?? '—'}
                                </p>
                                {header.job_title ? (
                                    <p className="mt-1 text-sm">
                                        {header.job_title}
                                        {header.company?.name
                                            ? ` at ${header.company.name}`
                                            : ''}
                                    </p>
                                ) : null}
                                <p className="mt-2 text-xs text-muted-foreground">
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
                                    className="text-sm font-medium text-foreground underline-offset-4 hover:underline"
                                >
                                    Open LinkedIn
                                </a>
                            ) : null}
                        </div>
                    </div>
                </div>
            ) : (
                <div className="flex items-center gap-3">
                    <Avatar className="size-12 shrink-0 rounded-full">
                        {header.picture_url ? (
                            <AvatarImage src={header.picture_url} alt="" />
                        ) : null}
                        <AvatarFallback>
                            {initials(header.name)}
                        </AvatarFallback>
                    </Avatar>
                    <div className="min-w-0">
                        <p className="text-sm font-medium">
                            {header.name ?? 'LinkedIn profile'}
                        </p>
                        <p className="truncate text-xs text-muted-foreground">
                            {header.headline ?? '—'}
                        </p>
                    </div>
                </div>
            )}

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
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

            {engagement_series.length > 0 ? (
                <SoftCard title="Engagement trend">
                    <MetricStat
                        value={avgMetric}
                        label="Avg reactions / post"
                        hint={
                            stats.avg_comments !== null
                                ? `Avg ${stats.avg_comments} comments across ${stats.posts_count} posts`
                                : undefined
                        }
                    />
                    <div className="mt-4 h-56">
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
                </SoftCard>
            ) : (
                <SoftCard title="Engagement trend">
                    <p className="text-sm text-muted-foreground">
                        Posts are still syncing. Refresh once the scrape
                        finishes to see your engagement trend.
                    </p>
                </SoftCard>
            )}

            <div className="grid gap-4 lg:grid-cols-2">
                <SoftCard title="Top posts">
                    <PostList
                        posts={top_posts}
                        empty="No posts synced yet."
                    />
                </SoftCard>
                <SoftCard title="Recent posts">
                    <PostList
                        posts={recent_posts}
                        empty="No posts synced yet."
                    />
                </SoftCard>
            </div>

            {engagers ? (
                <div className="grid gap-4 lg:grid-cols-2">
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
                <SoftCard title="Background">
                    {background.summary ? (
                        <p className="mb-6 whitespace-pre-wrap text-sm leading-relaxed text-muted-foreground">
                            {background.summary}
                        </p>
                    ) : null}
                    <div className="grid gap-8 lg:grid-cols-2">
                        <div>
                            <p className="mb-3 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Experience
                            </p>
                            <ul className="flex flex-col gap-4">
                                {background.positions.map((row, i) => (
                                    <li key={`${row.title}-${row.company}-${i}`}>
                                        <p className="text-sm font-medium">
                                            {row.title ?? 'Role'}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {[
                                                row.company,
                                                row.location,
                                                [row.start, row.end || 'Present']
                                                    .filter(Boolean)
                                                    .join(' – '),
                                            ]
                                                .filter(Boolean)
                                                .join(' · ')}
                                        </p>
                                        {row.description ? (
                                            <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                                                {truncate(row.description, 280)}
                                            </p>
                                        ) : null}
                                    </li>
                                ))}
                            </ul>
                        </div>
                        <div className="space-y-6">
                            <div>
                                <p className="mb-3 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                    Education
                                </p>
                                <ul className="flex flex-col gap-3">
                                    {background.educations.map((row, i) => (
                                        <li key={`${row.school}-${i}`}>
                                            <p className="text-sm font-medium">
                                                {row.school ?? 'School'}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
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
                                    ))}
                                </ul>
                            </div>
                            <div>
                                <p className="mb-3 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                    Skills
                                </p>
                                <div className="flex flex-wrap gap-2">
                                    {background.skills.map((skill) => (
                                        <span
                                            key={skill}
                                            className="rounded-full bg-muted px-2.5 py-1 text-xs text-muted-foreground"
                                        >
                                            {skill}
                                        </span>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </div>
                </SoftCard>
            )}
        </div>
    );
}
