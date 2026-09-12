import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { CampaignPost } from './types';

export default function CampaignAnalytics({
    leadsCount,
    posts,
}: {
    leadsCount: number;
    posts: CampaignPost[];
}) {
    return (
        <section className="grid gap-6">
            <div>
                <h2 className="text-lg font-semibold">Analytics</h2>
                <p className="text-muted-foreground text-sm">
                    Metrics stay empty until tracking URLs and the analytics
                    engine ship. Leads and posts below are live.
                </p>
            </div>
            <div className="grid gap-4 md:grid-cols-3">
                <StatCard
                    label="Est. reach"
                    value="0"
                    hint="No published posts yet"
                />
                <StatCard
                    label="Qualified clicks"
                    value="0"
                    hint="Since the campaign started"
                />
                <StatCard
                    label="Committed budget"
                    value="€ 0"
                    hint="Since the campaign started"
                />
            </div>
            <div className="border-border bg-card rounded-2xl border p-5">
                <h3 className="font-medium">Performance over time</h3>
                <p className="text-muted-foreground mb-6 text-sm">
                    Daily clicks · last 12 days
                </p>
                <div className="flex h-40 items-end gap-2">
                    {Array.from({ length: 12 }).map((_, index) => (
                        <div
                            key={index}
                            className="bg-muted h-2 flex-1 rounded-sm"
                        />
                    ))}
                </div>
                <div className="text-muted-foreground mt-3 flex justify-between text-xs">
                    <span>1 Sept</span>
                    <span>12 Sept</span>
                </div>
            </div>
            <div className="border-border bg-card grid gap-4 rounded-2xl border p-5">
                <div>
                    <h3 className="font-medium">Post performance</h3>
                    <p className="text-muted-foreground text-sm">
                        Without a pixel. Latest metrics collected from your
                        posts.
                    </p>
                </div>
                <div className="grid grid-cols-3 gap-4 text-center">
                    <MiniStat label="Posts" value="0" />
                    <MiniStat label="reactions" value="0" />
                    <MiniStat label="comments" value="0" />
                </div>
                <div className="border-border bg-muted/40 rounded-xl border p-4">
                    <p className="font-medium">Measure site conversions</p>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Connect the pixel to add visits, sign-ups and revenue to
                        your post results.
                    </p>
                    <Button type="button" variant="outline" className="mt-3" disabled>
                        Install the pixel
                    </Button>
                </div>
            </div>
            <div className="border-border bg-card rounded-2xl border p-5">
                <h3 className="font-medium">Attribution by creator</h3>
                <p className="text-muted-foreground mt-6 text-sm">
                    No attributed activity yet.
                </p>
            </div>
            <div className="grid gap-4 md:grid-cols-3">
                <StatCard label="CPM" value="—" />
                <StatCard label="Creators booked" value="0" />
                <StatCard
                    label="LinkedIn leads"
                    value="0"
                    hint="Calculated on — verified impressions"
                />
            </div>
            <div className="border-border bg-card rounded-2xl border p-5">
                <h3 className="font-medium">Verified reach</h3>
                <p className="text-muted-foreground mt-1 text-sm">
                    0 of 0 posts measured · Available when a verified day-7
                    reading exists
                </p>
                <div className="mt-4 overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-muted-foreground text-left">
                                <th className="py-2 font-medium"></th>
                                <th className="py-2 font-medium">impressions</th>
                                <th className="py-2 font-medium">reactions</th>
                                <th className="py-2 font-medium">comments</th>
                            </tr>
                        </thead>
                        <tbody>
                            {['D+1', 'D+3', 'D+7'].map((row) => (
                                <tr key={row} className="border-border border-t">
                                    <td className="py-2">{row}</td>
                                    <td className="py-2">—</td>
                                    <td className="py-2">—</td>
                                    <td className="py-2">—</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
            <div className="border-border bg-card rounded-2xl border p-5">
                <p className="text-muted-foreground text-sm">Leads</p>
                <p className="mt-1 text-3xl font-semibold">{leadsCount}</p>
                <p className="text-muted-foreground mt-1 text-sm">
                    Attributed leads on this campaign
                </p>
            </div>
            <div className="grid gap-4">
                <h3 className="text-lg font-semibold">Posts</h3>
                {posts.length === 0 ? (
                    <p className="text-muted-foreground text-sm">
                        No posts on this campaign yet.
                    </p>
                ) : (
                    posts.map((post) => (
                        <article
                            key={post.id}
                            className="border-border rounded-xl border p-4"
                        >
                            <div className="flex items-center justify-between gap-3">
                                <p className="font-medium">
                                    {post.creator.display_name}
                                </p>
                                <Badge variant="outline">
                                    {post.status.replace('_', ' ')}
                                </Badge>
                            </div>
                            {post.body && (
                                <p className="text-muted-foreground mt-2 text-sm whitespace-pre-wrap">
                                    {post.body}
                                </p>
                            )}
                            {post.published_url && (
                                <a
                                    href={post.published_url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="text-primary mt-2 inline-block text-sm"
                                >
                                    View post
                                </a>
                            )}
                        </article>
                    ))
                )}
            </div>
        </section>
    );
}

function StatCard({
    label,
    value,
    hint,
}: {
    label: string;
    value: string;
    hint?: string;
}) {
    return (
        <div className="border-border bg-card rounded-2xl border p-5">
            <p className="text-muted-foreground text-sm">{label}</p>
            <p className="mt-2 text-3xl font-semibold">{value}</p>
            {hint && (
                <p className="text-muted-foreground mt-1 text-sm">{hint}</p>
            )}
        </div>
    );
}

function MiniStat({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <p className="text-2xl font-semibold">{value}</p>
            <p className="text-muted-foreground text-xs">{label}</p>
        </div>
    );
}
