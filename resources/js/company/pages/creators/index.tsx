import { useEffect, useMemo, useState, type FormEvent } from 'react';
import { useNavigate, useParams } from 'react-router';
import {
    ChevronDown,
    Globe,
    ListFilter,
    Search,
    SlidersHorizontal,
    Tags,
} from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import CreatorCard from '@/company/pages/creators/creator-card';
import CreatorProfileDialog from '@/company/pages/creators/creator-profile-dialog';
import { centsFromEuros } from '@/company/pages/creators/format';
import type {
    CreatorList,
    CreatorListItem,
    Niche,
} from '@/company/pages/creators/types';
import {
    isShortlisted,
    readShortlist,
    toggleShortlist,
} from '@/lib/creator-shortlist';
import { api, ApiError, companyApi, sharedApi } from '@/lib/api';
import { countries } from '@/lib/lookups';
import { cn } from '@/lib/utils';

type Tab = 'all' | 'shortlist';
type Sort = 'match' | 'price' | 'followers';
type Intent = 'book' | 'negotiate';

type Filters = {
    q: string;
    nicheId: string;
    country: string;
    minPrice: string;
    maxPrice: string;
};

const emptyFilters: Filters = {
    q: '',
    nicheId: 'all',
    country: 'all',
    minPrice: '',
    maxPrice: '',
};

export default function CompanyCreatorsPage() {
    const navigate = useNavigate();
    const { id } = useParams();
    const [niches, setNiches] = useState<Niche[]>([]);
    const [list, setList] = useState<CreatorList | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [filters, setFilters] = useState<Filters>(emptyFilters);
    const [applied, setApplied] = useState<Filters>(emptyFilters);
    const [page, setPage] = useState(1);
    const [tab, setTab] = useState<Tab>('all');
    const [sort, setSort] = useState<Sort>('match');
    const [shortlist, setShortlist] = useState<CreatorListItem[]>(() =>
        readShortlist(),
    );
    const [openId, setOpenId] = useState<number | null>(id ? Number(id) : null);
    const [intent, setIntent] = useState<Intent>('book');

    useEffect(() => {
        api<Niche[]>(sharedApi.niches)
            .then(setNiches)
            .catch(() => undefined);
    }, []);

    useEffect(() => {
        const next = id ? Number(id) : null;
        setOpenId(Number.isFinite(next) ? next : null);
    }, [id]);

    useEffect(() => {
        api<CreatorList>(
            companyApi.creators({
                q: applied.q || undefined,
                niche_id:
                    applied.nicheId === 'all'
                        ? undefined
                        : Number(applied.nicheId),
                country:
                    applied.country === 'all' ? undefined : applied.country,
                min_price_cents: centsFromEuros(applied.minPrice),
                max_price_cents: centsFromEuros(applied.maxPrice),
                page,
            }),
        )
            .then(setList)
            .catch((caught: unknown) => {
                setError(
                    caught instanceof ApiError
                        ? caught.message
                        : 'Could not load creators.',
                );
            });
    }, [applied, page]);

    const visible = useMemo(() => {
        const source = tab === 'shortlist' ? shortlist : (list?.items ?? []);
        const rows = [...source];

        if (sort === 'price') {
            rows.sort(
                (left, right) =>
                    (left.from_price_cents ?? Number.MAX_SAFE_INTEGER) -
                    (right.from_price_cents ?? Number.MAX_SAFE_INTEGER),
            );
        }

        if (sort === 'followers') {
            rows.sort(
                (left, right) =>
                    (right.followers_count ?? 0) - (left.followers_count ?? 0),
            );
        }

        return rows;
    }, [list, shortlist, sort, tab]);

    function applyFilters(next: Filters) {
        setError(null);
        setPage(1);
        setApplied(next);
        setFilters(next);
        setTab('all');
    }

    function search(event: FormEvent) {
        event.preventDefault();
        applyFilters(filters);
    }

    function openProfile(creatorId: number, nextIntent: Intent = 'book') {
        setIntent(nextIntent);
        setOpenId(creatorId);
        void navigate(`/creators/${creatorId}`);
    }

    function closeProfile() {
        setOpenId(null);
        void navigate('/creators', { replace: true });
    }

    function star(creator: CreatorListItem) {
        setShortlist((current) => toggleShortlist(current, creator));
    }

    return (
        <div className="flex flex-1 flex-col gap-6 p-4 lg:p-6">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        All creators
                    </h1>
                    <p className="text-muted-foreground mt-1 max-w-2xl text-sm">
                        Browse vetted LinkedIn creators. Star a profile to
                        shortlist it. Booking and wallet top-up come next.
                    </p>
                </div>
                {list && (
                    <p className="text-muted-foreground text-sm">
                        {list.total} creators
                    </p>
                )}
            </div>
            <div className="border-border flex gap-6 border-b">
                <TabButton
                    active={tab === 'all'}
                    onClick={() => setTab('all')}
                    label="All creators"
                    count={list?.total}
                />
                <TabButton
                    active={tab === 'shortlist'}
                    onClick={() => setTab('shortlist')}
                    label="Shortlist"
                    count={shortlist.length}
                />
            </div>
            <InputError message={error ?? undefined} />
            <form
                onSubmit={search}
                className="border-border bg-card flex flex-col gap-3 rounded-2xl border p-3 shadow-sm"
            >
                <div className="flex flex-col gap-3 lg:flex-row">
                    <div className="relative min-w-0 flex-1">
                        <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                        <Input
                            value={filters.q}
                            onChange={(event) =>
                                setFilters((current) => ({
                                    ...current,
                                    q: event.target.value,
                                }))
                            }
                            placeholder="Search for a creator…"
                            className="bg-muted/60 h-10 border-0 pl-9 shadow-none"
                        />
                    </div>
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button
                                type="button"
                                variant="outline"
                                className="h-10 shrink-0"
                            >
                                Sort:{' '}
                                {sort === 'match'
                                    ? 'Best match'
                                    : sort === 'price'
                                      ? 'Price'
                                      : 'Followers'}
                                <ChevronDown className="size-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuRadioGroup
                                value={sort}
                                onValueChange={(value) =>
                                    setSort(value as Sort)
                                }
                            >
                                <DropdownMenuRadioItem value="match">
                                    Best match
                                </DropdownMenuRadioItem>
                                <DropdownMenuRadioItem value="price">
                                    Price
                                </DropdownMenuRadioItem>
                                <DropdownMenuRadioItem value="followers">
                                    Followers
                                </DropdownMenuRadioItem>
                            </DropdownMenuRadioGroup>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
                <div className="flex flex-wrap items-center gap-2">
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                className="rounded-full"
                            >
                                <Tags className="size-3.5" />
                                Industry
                                <ChevronDown className="size-3.5" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent className="max-h-72 overflow-y-auto">
                            <DropdownMenuItem
                                onClick={() =>
                                    applyFilters({ ...filters, nicheId: 'all' })
                                }
                            >
                                Any industry
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            {niches.map((niche) => (
                                <DropdownMenuItem
                                    key={niche.id}
                                    onClick={() =>
                                        applyFilters({
                                            ...filters,
                                            nicheId: String(niche.id),
                                        })
                                    }
                                >
                                    {niche.name}
                                </DropdownMenuItem>
                            ))}
                        </DropdownMenuContent>
                    </DropdownMenu>
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                className="rounded-full"
                            >
                                <Globe className="size-3.5" />
                                Country
                                <ChevronDown className="size-3.5" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent className="max-h-72 overflow-y-auto">
                            <DropdownMenuRadioGroup
                                value={filters.country}
                                onValueChange={(value) =>
                                    applyFilters({
                                        ...filters,
                                        country: value,
                                    })
                                }
                            >
                                <DropdownMenuRadioItem value="all">
                                    Any country
                                </DropdownMenuRadioItem>
                                {countries.map((item) => (
                                    <DropdownMenuRadioItem
                                        key={item.value}
                                        value={item.value}
                                    >
                                        {item.label}
                                    </DropdownMenuRadioItem>
                                ))}
                            </DropdownMenuRadioGroup>
                        </DropdownMenuContent>
                    </DropdownMenu>
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                className="rounded-full"
                            >
                                <SlidersHorizontal className="size-3.5" />
                                Price
                                <ChevronDown className="size-3.5" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent className="w-64 p-3">
                            <DropdownMenuLabel>Rate in €</DropdownMenuLabel>
                            <div className="mt-2 grid grid-cols-2 gap-2">
                                <div className="space-y-1">
                                    <Label htmlFor="min_price">Min</Label>
                                    <Input
                                        id="min_price"
                                        type="number"
                                        min={0}
                                        value={filters.minPrice}
                                        onChange={(event) =>
                                            setFilters((current) => ({
                                                ...current,
                                                minPrice: event.target.value,
                                            }))
                                        }
                                    />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="max_price">Max</Label>
                                    <Input
                                        id="max_price"
                                        type="number"
                                        min={0}
                                        value={filters.maxPrice}
                                        onChange={(event) =>
                                            setFilters((current) => ({
                                                ...current,
                                                maxPrice: event.target.value,
                                            }))
                                        }
                                    />
                                </div>
                            </div>
                            <Button
                                type="button"
                                size="sm"
                                className="mt-3 w-full"
                                onClick={() => applyFilters(filters)}
                            >
                                Apply
                            </Button>
                        </DropdownMenuContent>
                    </DropdownMenu>
                    <Button
                        type="submit"
                        size="sm"
                        variant="ghost"
                        className="rounded-full"
                    >
                        <ListFilter className="size-3.5" />
                        Search
                    </Button>
                </div>
            </form>
            {visible.length === 0 ? (
                <p className="text-muted-foreground text-sm">
                    {tab === 'shortlist'
                        ? 'Star creators to save them on your shortlist.'
                        : 'No vetted creators match these filters.'}
                </p>
            ) : (
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {visible.map((creator) => (
                        <CreatorCard
                            key={creator.id}
                            creator={creator}
                            starred={isShortlisted(shortlist, creator.id)}
                            onOpen={() => openProfile(creator.id)}
                            onBook={() => openProfile(creator.id, 'book')}
                            onStar={() => star(creator)}
                        />
                    ))}
                </div>
            )}
            {tab === 'all' && list && list.last_page > 1 && (
                <div className="flex items-center gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        disabled={list.current_page <= 1}
                        onClick={() => setPage((current) => current - 1)}
                    >
                        Previous
                    </Button>
                    <p className="text-muted-foreground text-sm">
                        Page {list.current_page} of {list.last_page}
                    </p>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        disabled={list.current_page >= list.last_page}
                        onClick={() => setPage((current) => current + 1)}
                    >
                        Next
                    </Button>
                </div>
            )}
            <CreatorProfileDialog
                creatorId={openId}
                starred={openId !== null && isShortlisted(shortlist, openId)}
                intent={intent}
                onClose={closeProfile}
                onStar={star}
            />
        </div>
    );
}

function TabButton({
    active,
    onClick,
    label,
    count,
}: {
    active: boolean;
    onClick: () => void;
    label: string;
    count?: number;
}) {
    return (
        <button
            type="button"
            className={cn(
                'border-b-2 pb-3 text-sm',
                active
                    ? 'border-primary text-foreground font-medium'
                    : 'text-muted-foreground border-transparent',
            )}
            onClick={onClick}
        >
            {label}
            {count !== undefined && (
                <span className="text-muted-foreground ml-2 text-xs">
                    {count}
                </span>
            )}
        </button>
    );
}
