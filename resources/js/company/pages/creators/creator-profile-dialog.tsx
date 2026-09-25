import { useEffect, useState, type FormEvent } from 'react';
import { useNavigate } from 'react-router';
import {
    CalendarDays,
    Linkedin,
    ShieldCheck,
    Star,
    Wallet,
} from 'lucide-react';
import { toast } from 'sonner';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { LinkedInInsightsPanel } from '@/components/linkedin/insights-panel';
import CampaignSearchSelect, {
    type CampaignOption,
} from '@/company/pages/creators/campaign-search-select';
import {
    countryLabel,
    euros,
    initials,
    offerLabel,
} from '@/company/pages/creators/format';
import type {
    CreatorListItem,
    CreatorProfileCard,
} from '@/company/pages/creators/types';
import { canManageMoney } from '@/lib/current-user';
import { api, ApiError, companyApi, http } from '@/lib/api';
import { cn } from '@/lib/utils';

type Tab = 'overview' | 'audience' | 'linkedin' | 'content';
type Intent = 'book' | 'negotiate';

export default function CreatorProfileDialog({
    creatorId,
    starred,
    intent,
    onClose,
    onStar,
}: {
    creatorId: number | null;
    starred: boolean;
    intent: Intent;
    onClose: () => void;
    onStar: (creator: CreatorListItem) => void;
}) {
    const navigate = useNavigate();
    const [creator, setCreator] = useState<CreatorProfileCard | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [tab, setTab] = useState<Tab>('overview');
    const [mode, setMode] = useState<Intent>(intent);
    const [campaign, setCampaign] = useState<CampaignOption | null>(null);
    const [postDate, setPostDate] = useState('');
    const [approveFirst, setApproveFirst] = useState(true);
    const [offerEuros, setOfferEuros] = useState('');
    const canManage = canManageMoney();
    const [availableCents, setAvailableCents] = useState<number | null>(null);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        setMode(intent);
    }, [intent]);

    useEffect(() => {
        if (creatorId === null) {
            setCreator(null);
            setError(null);
            setTab('overview');
            setCampaign(null);
            setPostDate('');
            setApproveFirst(true);
            setOfferEuros('');
            setSaving(false);

            return;
        }

        http.get<{ available_cents: number }>(companyApi.wallet)
            .then(({ data }) => setAvailableCents(data.available_cents))
            .catch(() => setAvailableCents(null));

        api<CreatorProfileCard>(companyApi.creator(creatorId))
            .then((data) => {
                setCreator(data);
                setError(null);
                const listed = data.from_price_cents;

                if (listed !== null) {
                    setOfferEuros(String(Math.round(listed / 100)));
                }
            })
            .catch((caught: unknown) => {
                setCreator(null);
                setError(
                    caught instanceof ApiError
                        ? caught.message
                        : 'Could not load this creator.',
                );
            });
    }, [creatorId]);

    const listedOffer = creator?.offers[0] ?? null;

    async function submit(event: FormEvent) {
        event.preventDefault();

        if (mode === 'negotiate') {
            toast.message('Negotiation is UI-only for now.');

            return;
        }

        if (creatorId === null) {
            return;
        }

        if (campaign === null) {
            toast.error('Select a campaign.');

            return;
        }

        if (!canManage) {
            toast.error('Only owners can book creators.');

            return;
        }

        setSaving(true);

        try {
            await http.post(companyApi.campaignBook(campaign.id), {
                creator_profile_id: creatorId,
                ...(listedOffer
                    ? { creator_offer_id: listedOffer.id }
                    : {}),
            });
            toast.success('Creator booked');
            onClose();
            void navigate(`/collaboration?campaign=${campaign.id}`);
        } catch (caught) {
            const data =
                caught instanceof ApiError
                    ? (caught.payload.data as { checkout_url?: string })
                    : null;

            if (data?.checkout_url) {
                window.location.href = data.checkout_url;

                return;
            }

            toast.error(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not book this creator.',
            );
            setSaving(false);
        }
    }

    return (
        <Dialog
            open={creatorId !== null}
            onOpenChange={(open) => !open && onClose()}
        >
            <DialogContent className="flex max-h-[90vh] max-w-5xl flex-col gap-0 overflow-hidden p-0 sm:max-w-5xl">
                <DialogTitle className="sr-only">
                    {creator?.display_name ?? 'Creator'}
                </DialogTitle>
                <DialogDescription className="sr-only">
                    Creator overview, audience, and booking.
                </DialogDescription>
                {error && (
                    <p className="text-destructive p-6 text-sm">{error}</p>
                )}
                {creator && (
                    <div className="grid min-h-0 flex-1 lg:grid-cols-[1fr_20rem]">
                        <div className="min-h-0 overflow-y-auto">
                            <header className="relative isolate min-h-48 overflow-hidden bg-primary text-primary-foreground">
                                {creator.photo_url ? (
                                    <img
                                        src={creator.photo_url}
                                        alt=""
                                        className="absolute inset-0 size-full object-cover object-top"
                                    />
                                ) : (
                                    <div className="absolute inset-0 bg-accent" />
                                )}
                                <div className="absolute inset-0 bg-linear-to-t from-primary via-primary/70 to-primary/20" />
                                <div className="relative flex items-end gap-4 px-6 pb-5 pt-14 pr-24">
                                    <Avatar className="size-16 rounded-full border-2 border-primary-foreground/30">
                                        {creator.photo_url && (
                                            <AvatarImage
                                                src={creator.photo_url}
                                                alt=""
                                            />
                                        )}
                                        <AvatarFallback className="bg-accent text-accent-foreground">
                                            {initials(creator.display_name)}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div className="min-w-0">
                                        <p className="text-xl font-semibold tracking-tight">
                                            {creator.display_name ??
                                                'Untitled creator'}
                                        </p>
                                        <p className="text-sm text-primary-foreground/75">
                                            {creator.headline ??
                                                countryLabel(creator.country)}
                                        </p>
                                        <p className="mt-1 text-xs text-primary-foreground/60">
                                            {creator.niches
                                                .map((niche) => niche.name)
                                                .join(' · ') ||
                                                'LinkedIn creator'}
                                        </p>
                                    </div>
                                </div>
                                <Button
                                    type="button"
                                    size="icon"
                                    variant="ghost"
                                    className="absolute top-4 right-12 size-8 rounded-full text-primary-foreground hover:bg-primary-foreground/15 hover:text-primary-foreground"
                                    aria-label={
                                        starred
                                            ? 'Remove from shortlist'
                                            : 'Add to shortlist'
                                    }
                                    onClick={() => onStar(creator)}
                                >
                                    <Star
                                        className={cn(
                                            'size-4',
                                            starred &&
                                                'fill-accent text-accent',
                                        )}
                                    />
                                </Button>
                            </header>
                            <nav className="border-border flex gap-6 border-b px-6">
                                {(
                                    [
                                        ['overview', 'Overview'],
                                        ['audience', 'Audience'],
                                        ['linkedin', 'LinkedIn'],
                                        ['content', 'Content'],
                                    ] as const
                                )
                                    .filter(
                                        ([id]) =>
                                            id !== 'linkedin' ||
                                            Boolean(creator.linkedin_insights),
                                    )
                                    .map(([id, label]) => (
                                    <button
                                        key={id}
                                        type="button"
                                        className={cn(
                                            'border-b-2 py-3 text-sm',
                                            tab === id
                                                ? 'border-primary text-foreground font-medium'
                                                : 'text-muted-foreground border-transparent',
                                        )}
                                        onClick={() => setTab(id)}
                                    >
                                        {label}
                                    </button>
                                ))}
                            </nav>
                            <div className="space-y-6 p-6">
                                {tab === 'overview' && (
                                    <div className="space-y-4">
                                        <h3 className="text-sm font-semibold">
                                            Creator overview
                                        </h3>
                                        <p className="text-muted-foreground text-sm">
                                            {creator.bio ??
                                                'Review this creator’s audience before booking.'}
                                        </p>
                                        <div className="text-muted-foreground flex flex-wrap gap-3 text-sm">
                                            <span>
                                                {creator.followers_count?.toLocaleString() ??
                                                    '—'}{' '}
                                                followers
                                            </span>
                                            <span>
                                                {creator.connections_count?.toLocaleString() ??
                                                    '—'}{' '}
                                                connections
                                            </span>
                                            <span>
                                                {countryLabel(creator.country)}
                                            </span>
                                            {creator.linkedin_url && (
                                                <a
                                                    href={creator.linkedin_url}
                                                    className="text-primary inline-flex items-center gap-1 hover:underline"
                                                    target="_blank"
                                                    rel="noreferrer"
                                                >
                                                    <Linkedin className="size-3.5" />
                                                    LinkedIn
                                                </a>
                                            )}
                                        </div>
                                    </div>
                                )}
                                {tab === 'audience' && (
                                    <AudienceSnapshot
                                        mix={creator.audience_mix}
                                        followers={creator.followers_count}
                                        connections={
                                            creator.connections_count ?? null
                                        }
                                    />
                                )}
                                {tab === 'linkedin' &&
                                    creator.linkedin_insights && (
                                        <LinkedInInsightsPanel
                                            insights={creator.linkedin_insights}
                                            compactHeader
                                            showPosts={false}
                                            stacked
                                        />
                                    )}
                                {tab === 'content' && (
                                    <p className="text-muted-foreground text-sm">
                                        Recent post metrics show here after
                                        campaigns run.
                                    </p>
                                )}
                            </div>
                        </div>
                        <aside className="border-border bg-muted/40 border-t p-5 lg:border-t-0 lg:border-l">
                            <h3 className="text-sm font-semibold">
                                Book this creator
                            </h3>
                            <div className="border-border bg-card mt-4 rounded-xl border p-4">
                                <p className="text-muted-foreground text-xs">
                                    {listedOffer
                                        ? offerLabel(listedOffer.label)
                                        : 'Single post'}
                                </p>
                                <p className="mt-1 text-2xl font-semibold">
                                    {euros(
                                        listedOffer?.price_cents ??
                                            creator.from_price_cents,
                                    )}
                                </p>
                            </div>
                            <div className="mt-4 grid gap-2">
                                <Button
                                    type="button"
                                    variant={
                                        mode === 'book' ? 'default' : 'outline'
                                    }
                                    size="sm"
                                    onClick={() => setMode('book')}
                                >
                                    Book
                                </Button>
                                {/* <Button
                                    type="button"
                                    variant={
                                        mode === 'negotiate'
                                            ? 'default'
                                            : 'outline'
                                    }
                                    size="sm"
                                    onClick={() => setMode('negotiate')}
                                >
                                    Negotiate
                                </Button> */}
                            </div>
                            <form className="mt-4 space-y-4" onSubmit={submit}>
                                <div className="space-y-2">
                                    <Label>Campaign</Label>
                                    <CampaignSearchSelect
                                        value={campaign}
                                        onChange={setCampaign}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="post_date">
                                        <span className="inline-flex items-center gap-1.5">
                                            <CalendarDays className="size-3.5" />
                                            Post date
                                        </span>
                                    </Label>
                                    <Input
                                        id="post_date"
                                        type="date"
                                        value={postDate}
                                        onChange={(event) =>
                                            setPostDate(event.target.value)
                                        }
                                    />
                                </div>
                                {mode === 'negotiate' && (
                                    <div className="space-y-2">
                                        <Label htmlFor="offer">
                                            Your offer (€)
                                        </Label>
                                        <Input
                                            id="offer"
                                            type="number"
                                            min={0}
                                            value={offerEuros}
                                            onChange={(event) =>
                                                setOfferEuros(
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                )}
                                <label className="flex items-start gap-2 text-sm">
                                    <Checkbox
                                        checked={approveFirst}
                                        onCheckedChange={(value) =>
                                            setApproveFirst(value === true)
                                        }
                                    />
                                    <span className="text-muted-foreground inline-flex items-start gap-1.5">
                                        <ShieldCheck className="mt-0.5 size-3.5 shrink-0" />
                                        Company approves the post first
                                    </span>
                                </label>
                                <Separator />
                                <p className="text-muted-foreground flex items-center gap-1.5 text-xs">
                                    <Wallet className="size-3.5" />
                                    {availableCents === null
                                        ? 'Booking holds the listed price from your wallet.'
                                        : `${euros(availableCents)} available. Booking holds the listed price.`}
                                </p>
                                <Button
                                    type="submit"
                                    className="w-full"
                                    disabled={
                                        saving ||
                                        (mode === 'book' && !canManage)
                                    }
                                >
                                    {mode === 'negotiate'
                                        ? `Send offer to ${creator.display_name ?? 'creator'}`
                                        : saving
                                          ? 'Booking…'
                                          : `Collaborate with ${creator.display_name ?? 'creator'}`}
                                </Button>
                            </form>
                        </aside>
                    </div>
                )}
            </DialogContent>
        </Dialog>
    );
}

function AudienceSnapshot({
    mix,
    followers,
    connections,
}: {
    mix: Record<string, unknown> | unknown[];
    followers: number | null;
    connections: number | null;
}) {
    const hasMix =
        !Array.isArray(mix) &&
        mix !== null &&
        typeof mix === 'object' &&
        Object.entries(mix).some(
            (entry) =>
                typeof entry[1] === 'object' &&
                entry[1] !== null &&
                !Array.isArray(entry[1]),
        );

    const groups =
        !Array.isArray(mix) && mix !== null && typeof mix === 'object'
            ? Object.entries(mix).filter(
                  (entry): entry is [string, Record<string, number>] =>
                      typeof entry[1] === 'object' &&
                      entry[1] !== null &&
                      !Array.isArray(entry[1]),
              )
            : [];

    return (
        <div className="space-y-6">
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="rounded-2xl bg-muted/50 p-4">
                    <p className="text-xs text-muted-foreground">Followers</p>
                    <p className="mt-1 text-xl font-medium">
                        {followers?.toLocaleString() ?? '—'}
                    </p>
                </div>
                <div className="rounded-2xl bg-muted/50 p-4">
                    <p className="text-xs text-muted-foreground">Connections</p>
                    <p className="mt-1 text-xl font-medium">
                        {connections?.toLocaleString() ?? '—'}
                    </p>
                </div>
            </div>

            {!hasMix ? (
                <p className="text-muted-foreground text-sm">
                    No audience mix yet. It appears after the creator’s LinkedIn
                    posts sync with engagers.
                </p>
            ) : (
                <>
                    <h3 className="text-sm font-semibold">Audience snapshot</h3>
                    <div className="grid gap-6 md:grid-cols-2">
                        {groups.map(([title, shares]) => (
                            <div key={title} className="space-y-3">
                                <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">
                                    {title.replaceAll('_', ' ')}
                                </p>
                                {Object.entries(shares)
                                    .sort((left, right) => right[1] - left[1])
                                    .slice(0, 6)
                                    .map(([label, share]) => (
                                        <div key={label} className="space-y-1">
                                            <div className="flex justify-between text-xs">
                                                <span>{label}</span>
                                                <span className="text-muted-foreground">
                                                    {share}%
                                                </span>
                                            </div>
                                            <div className="bg-muted h-1.5 overflow-hidden rounded-full">
                                                <div
                                                    className="bg-primary h-full rounded-full"
                                                    style={{
                                                        width: `${Math.min(100, Number(share))}%`,
                                                    }}
                                                />
                                            </div>
                                        </div>
                                    ))}
                            </div>
                        ))}
                    </div>
                </>
            )}
        </div>
    );
}
