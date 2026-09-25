import { Search, Settings2, Star } from 'lucide-react';
import { useMemo, useState } from 'react';
import {
    ActivityBarChart,
    AvatarGroup,
    DateRangePills,
    GlassPanel,
    IconButton,
    MetricStat,
    ProgressRow,
    RevenueAreaChart,
    SegmentedNav,
    SoftCard,
    SpendLineChart,
    StatusPill,
} from '@/components/ds';
import { InfoChip } from '@/components/info-chip';
import { NotchedCard } from '@/components/notched-card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

const sections = [
    { id: 'tokens', label: 'Tokens' },
    { id: 'typography', label: 'Typography' },
    { id: 'buttons', label: 'Buttons' },
    { id: 'badges', label: 'Badges' },
    { id: 'icon-buttons', label: 'Icon buttons' },
    { id: 'avatars', label: 'Avatars' },
    { id: 'forms', label: 'Forms' },
    { id: 'nav', label: 'Nav' },
    { id: 'metrics', label: 'Metrics' },
    { id: 'cards', label: 'Cards' },
    { id: 'charts', label: 'Charts' },
    { id: 'notched', label: 'Notched' },
    { id: 'glass', label: 'Glass' },
] as const;

const demoAvatars = [
    { fallback: 'JD', alt: 'Jane' },
    { fallback: 'AL', alt: 'Alex' },
    { fallback: 'MK', alt: 'Maya' },
    { fallback: 'OW', alt: 'Owen' },
    { fallback: 'HN', alt: 'Hana' },
];

function Section({
    id,
    title,
    children,
}: {
    id: string;
    title: string;
    children: React.ReactNode;
}) {
    return (
        <section id={id} className="scroll-mt-24 space-y-4">
            <h2 className="text-title font-medium tracking-tight">{title}</h2>
            {children}
        </section>
    );
}

function Swatch({
    name,
    className,
    foregroundClassName,
}: {
    name: string;
    className: string;
    foregroundClassName: string;
}) {
    return (
        <div
            className={cn(
                'flex h-28 flex-col justify-between rounded-2xl p-4',
                className,
                foregroundClassName,
            )}
        >
            <span className="text-sm font-medium">{name}</span>
            <span className="text-xs opacity-70">{className}</span>
        </div>
    );
}

export default function ComponentsGalleryPage() {
    const [nav, setNav] = useState('dashboard');

    const activityData = useMemo(
        () => [
            { day: 'Mon', value: 40 },
            { day: 'Tue', value: 55 },
            { day: 'Wed', value: 35 },
            { day: 'Thu', value: 70 },
            { day: 'Fri', value: 95, highlight: true },
            { day: 'Sat', value: 30 },
            { day: 'Sun', value: 45 },
        ],
        [],
    );

    const spendData = useMemo(
        () => [
            { day: 'Mon', value: 120 },
            { day: 'Tue', value: 180 },
            { day: 'Wed', value: 150 },
            { day: 'Thu', value: 210 },
            { day: 'Fri', value: 280 },
            { day: 'Sat', value: 190 },
            { day: 'Sun', value: 160 },
        ],
        [],
    );

    const revenueData = useMemo(
        () => [
            { day: 'Mon', current: 40, previous: 30 },
            { day: 'Tue', current: 55, previous: 42 },
            { day: 'Wed', current: 48, previous: 50 },
            { day: 'Thu', current: 70, previous: 55 },
            { day: 'Fri', current: 90, previous: 68 },
            { day: 'Sat', current: 75, previous: 60 },
            { day: 'Sun', current: 82, previous: 65 },
        ],
        [],
    );

    return (
        <div className="min-h-screen bg-background">
            <header className="sticky top-0 z-20 border-b border-border bg-background/90 backdrop-blur-md">
                <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-4">
                    <div>
                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            Local only
                        </p>
                        <h1 className="text-heading font-medium tracking-tight">
                            Components
                        </h1>
                    </div>
                    <Badge variant="accent">APP_ENV=local</Badge>
                </div>
            </header>

            <div className="mx-auto grid max-w-6xl gap-10 px-6 py-10 lg:grid-cols-[200px_1fr]">
                <nav className="sticky top-28 hidden self-start lg:block">
                    <ul className="space-y-1">
                        {sections.map((section) => (
                            <li key={section.id}>
                                <a
                                    href={`#${section.id}`}
                                    className="block rounded-pill px-3 py-1.5 text-sm text-muted-foreground hover:bg-muted hover:text-foreground"
                                >
                                    {section.label}
                                </a>
                            </li>
                        ))}
                    </ul>
                </nav>

                <div className="space-y-16">
                    <Section id="tokens" title="Tokens">
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <Swatch
                                name="Background"
                                className="bg-background"
                                foregroundClassName="text-foreground"
                            />
                            <Swatch
                                name="Card"
                                className="bg-card"
                                foregroundClassName="text-card-foreground"
                            />
                            <Swatch
                                name="Primary"
                                className="bg-primary"
                                foregroundClassName="text-primary-foreground"
                            />
                            <Swatch
                                name="Accent (lime)"
                                className="bg-accent"
                                foregroundClassName="text-accent-foreground"
                            />
                            <Swatch
                                name="Lime soft"
                                className="bg-lime-soft"
                                foregroundClassName="text-lime-soft-foreground"
                            />
                            <Swatch
                                name="Muted"
                                className="bg-muted"
                                foregroundClassName="text-muted-foreground"
                            />
                        </div>
                    </Section>

                    <Section id="typography" title="Typography">
                        <SoftCard>
                            <p className="text-heading font-medium">
                                Heading 34px
                            </p>
                            <p className="mt-2 text-title font-medium">
                                Title 28px
                            </p>
                            <p className="mt-2 text-body font-normal">
                                Body 16px — Inter light / regular / medium for
                                product surfaces.
                            </p>
                            <p className="mt-2 text-sm text-muted-foreground">
                                Muted supporting copy uses muted-foreground.
                            </p>
                        </SoftCard>
                    </Section>

                    <Section id="buttons" title="Buttons">
                        <div className="flex flex-wrap gap-3">
                            <Button>Primary</Button>
                            <Button variant="accent">Accent</Button>
                            <Button variant="secondary">Secondary</Button>
                            <Button variant="outline">Outline</Button>
                            <Button variant="ghost">Ghost</Button>
                            <Button variant="link">Link</Button>
                            <Button size="sm">Small</Button>
                            <Button size="lg">Large</Button>
                        </div>
                    </Section>

                    <Section id="badges" title="Badges">
                        <div className="flex flex-wrap gap-2">
                            <Badge>Accent</Badge>
                            <Badge variant="soft">Soft</Badge>
                            <Badge variant="default">Primary</Badge>
                            <Badge variant="secondary">Secondary</Badge>
                            <Badge variant="outline">Outline</Badge>
                            <Badge variant="destructive">Destructive</Badge>
                        </div>
                    </Section>

                    <Section id="icon-buttons" title="Icon buttons">
                        <div className="flex flex-wrap gap-3">
                            <IconButton variant="outline" aria-label="Search">
                                <Search />
                            </IconButton>
                            <IconButton variant="default" aria-label="Star">
                                <Star />
                            </IconButton>
                            <IconButton variant="accent" aria-label="Settings">
                                <Settings2 />
                            </IconButton>
                            <IconButton variant="ghost" aria-label="Ghost">
                                <Search />
                            </IconButton>
                        </div>
                    </Section>

                    <Section id="avatars" title="Avatars">
                        <div className="flex flex-wrap items-center gap-6">
                            <Avatar size="sm">
                                <AvatarFallback>SM</AvatarFallback>
                            </Avatar>
                            <Avatar>
                                <AvatarImage src="https://i.pravatar.cc/80?img=12" />
                                <AvatarFallback>MD</AvatarFallback>
                            </Avatar>
                            <Avatar size="lg">
                                <AvatarFallback>LG</AvatarFallback>
                            </Avatar>
                            <AvatarGroup items={demoAvatars} max={3} />
                        </div>
                    </Section>

                    <Section id="forms" title="Forms">
                        <SoftCard className="max-w-md space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="gallery-email">Email</Label>
                                <Input
                                    id="gallery-email"
                                    type="email"
                                    placeholder="you@company.com"
                                />
                            </div>
                            <div className="flex items-center gap-2">
                                <Checkbox id="gallery-check" />
                                <Label htmlFor="gallery-check">
                                    Remember this device
                                </Label>
                            </div>
                        </SoftCard>
                    </Section>

                    <Section id="nav" title="Nav">
                        <div className="flex flex-wrap items-center gap-4">
                            <SegmentedNav
                                items={[
                                    { id: 'dashboard', label: 'Dashboard' },
                                    { id: 'analytics', label: 'Analytics' },
                                    { id: 'pulse', label: 'Pulse' },
                                    { id: 'data', label: 'Data' },
                                ]}
                                value={nav}
                                onChange={setNav}
                            />
                            <DateRangePills start="22 6 2025" end="26 6 2025" />
                            <StatusPill
                                label="Call Scheduled"
                                avatarFallback="JD"
                            />
                        </div>
                    </Section>

                    <Section id="metrics" title="Metrics & progress">
                        <div className="grid gap-4 md:grid-cols-2">
                            <SoftCard>
                                <MetricStat
                                    value="186"
                                    label="Worked this week"
                                />
                            </SoftCard>
                            <SoftCard title="Virtual Cards">
                                <MetricStat
                                    value="$6,010.27"
                                    label="Total Balance"
                                    className="mb-4"
                                />
                                <div className="space-y-3">
                                    <ProgressRow label="Dolar" value={72} />
                                    <ProgressRow label="Tether" value={28} />
                                </div>
                            </SoftCard>
                        </div>
                    </Section>

                    <Section id="cards" title="Soft cards">
                        <div className="grid gap-4 md:grid-cols-2">
                            <SoftCard title="Activity">
                                <p className="text-sm text-muted-foreground">
                                    Soft white surface, large radius, optional
                                    expand control.
                                </p>
                            </SoftCard>
                            <SoftCard
                                title="Custom action"
                                action={<Badge variant="accent">Live</Badge>}
                            >
                                <p className="text-sm text-muted-foreground">
                                    Header can host badges or filters.
                                </p>
                            </SoftCard>
                        </div>
                    </Section>

                    <Section id="charts" title="Charts">
                        <div className="grid gap-4 xl:grid-cols-2">
                            <ActivityBarChart
                                metric="186"
                                data={activityData}
                                callout="12,464"
                            />
                            <SpendLineChart
                                metric="$278.86"
                                compare="$432.00"
                                sideStats={[
                                    { value: '34', label: 'Wallets' },
                                    { value: '28', label: 'Assets' },
                                ]}
                                data={spendData}
                                callout="34,533"
                            />
                            <RevenueAreaChart
                                metric="29,48m"
                                data={revenueData}
                                growth="+9%"
                                className="xl:col-span-2"
                            />
                        </div>
                    </Section>

                    <Section id="notched" title="Notched cards">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                            <NotchedCard
                                name="Jane Doe"
                                role="Marketing Director at Marosft"
                                avatarFallback="JD"
                                title="Schedule Discovery Call"
                                chips={
                                    <>
                                        <InfoChip>France</InfoChip>
                                        <InfoChip>Match 82%</InfoChip>
                                    </>
                                }
                                meta={
                                    <AvatarGroup
                                        items={demoAvatars.slice(0, 2)}
                                        max={3}
                                        size="sm"
                                    />
                                }
                                footer={
                                    <Button size="sm" className="rounded-pill">
                                        Apply
                                    </Button>
                                }
                            />
                            <NotchedCard
                                name="Alexander"
                                role="Product Lead at Helio"
                                avatarFallback="AL"
                                title="Schedule Discovery Call"
                                chips={
                                    <>
                                        <InfoChip>Germany</InfoChip>
                                        <InfoChip>Match 64%</InfoChip>
                                    </>
                                }
                                meta={
                                    <AvatarGroup
                                        items={demoAvatars.slice(1, 3)}
                                        max={3}
                                        size="sm"
                                    />
                                }
                                footer={
                                    <Button size="sm" className="rounded-pill">
                                        Apply
                                    </Button>
                                }
                            />
                        </div>
                    </Section>

                    <Section id="glass" title="Glass panel">
                        <div className="overflow-hidden rounded-2xl bg-lime-soft p-8">
                            <GlassPanel title="Advantages" className="max-w-sm">
                                <div className="grid grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <p className="font-medium">30-45 age</p>
                                        <Badge variant="accent" className="mt-1">
                                            6% Growth
                                        </Badge>
                                    </div>
                                    <div>
                                        <p className="font-medium">12-21 age</p>
                                        <Badge variant="accent" className="mt-1">
                                            9% Growth
                                        </Badge>
                                    </div>
                                </div>
                            </GlassPanel>
                        </div>
                    </Section>
                </div>
            </div>
        </div>
    );
}
