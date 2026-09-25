import { useEffect, useMemo, useState } from 'react';
import { useNavigate, useParams } from 'react-router';
import { ArrowUpDown, Globe, ListFilter, Tags } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { FilterDropdown, FilterDropdownGroup } from '@/components/filter-select';
import { SearchPill, SegmentedNav } from '@/components/ds';
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

type Tab = 'all' | 'shortlist';
type Sort = 'match' | 'price' | 'followers';
type Intent = 'book' | 'negotiate';
type PriceRange = 'all' | 'under-250' | '250-500' | '500-1000' | '1000-plus';

type Filters = {
    q: string;
    nicheId: string;
    country: string;
    priceRange: PriceRange;
};

const emptyFilters: Filters = {
    q: '',
    nicheId: 'all',
    country: 'all',
    priceRange: 'all',
};

const priceRangeItems: { value: PriceRange; label: string }[] = [
    { value: 'all', label: 'Any price' },
    { value: 'under-250', label: 'Under €250' },
    { value: '250-500', label: '€250–€500' },
    { value: '500-1000', label: '€500–€1,000' },
    { value: '1000-plus', label: '€1,000+' },
];

const sortItems: { value: Sort; label: string }[] = [
    { value: 'match', label: 'Best match' },
    { value: 'price', label: 'Price' },
    { value: 'followers', label: 'Followers' },
];

function priceBounds(range: PriceRange): {
    minPrice: string;
    maxPrice: string;
} {
    switch (range) {
        case 'under-250':
            return { minPrice: '', maxPrice: '250' };
        case '250-500':
            return { minPrice: '250', maxPrice: '500' };
        case '500-1000':
            return { minPrice: '500', maxPrice: '1000' };
        case '1000-plus':
            return { minPrice: '1000', maxPrice: '' };
        default:
            return { minPrice: '', maxPrice: '' };
    }
}

export default function CompanyCreatorsPage() {
    const navigate = useNavigate();
    const { id } = useParams();
    const [niches, setNiches] = useState<Niche[]>([]);
    const [list, setList] = useState<CreatorList | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [draftQuery, setDraftQuery] = useState('');
    const [filters, setFilters] = useState<Filters>(emptyFilters);
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
        const { minPrice, maxPrice } = priceBounds(filters.priceRange);

        api<CreatorList>(
            companyApi.creators({
                q: filters.q || undefined,
                niche_id:
                    filters.nicheId === 'all'
                        ? undefined
                        : Number(filters.nicheId),
                country:
                    filters.country === 'all' ? undefined : filters.country,
                min_price_cents: centsFromEuros(minPrice),
                max_price_cents: centsFromEuros(maxPrice),
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
    }, [filters, page]);

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

    const filtersActive =
        filters.q !== '' ||
        filters.nicheId !== 'all' ||
        filters.country !== 'all' ||
        filters.priceRange !== 'all' ||
        sort !== 'match';

    function patchFilters(patch: Partial<Filters>) {
        setError(null);
        setPage(1);
        setTab('all');
        setFilters((current) => ({ ...current, ...patch }));
    }

    function applySearch() {
        patchFilters({ q: draftQuery.trim() });
    }

    function clearFilters() {
        setDraftQuery('');
        setSort('match');
        setError(null);
        setPage(1);
        setTab('all');
        setFilters(emptyFilters);
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
        <div className="flex w-full flex-1 flex-col gap-8">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div className="space-y-2">
                    <h1 className="text-heading font-medium tracking-tight">
                        All creators
                    </h1>
                    <p className="max-w-xl text-sm text-muted-foreground">
                        Browse vetted LinkedIn creators. Star a profile to
                        shortlist it. Book at the listed price from your wallet.
                    </p>
                </div>
                {list ? (
                    <p className="text-sm text-muted-foreground">
                        <span className="font-medium text-foreground">
                            {list.total}
                        </span>{' '}
                        {list.total === 1 ? 'creator' : 'creators'}
                    </p>
                ) : null}
            </div>

            <SegmentedNav
                items={[
                    {
                        id: 'all',
                        label: `All creators${list ? ` ${list.total}` : ''}`,
                    },
                    {
                        id: 'shortlist',
                        label: `Shortlist ${shortlist.length}`,
                    },
                ]}
                value={tab}
                onChange={(id) => setTab(id as Tab)}
                className="w-fit max-w-full flex-wrap"
            />

            <div className="flex flex-wrap items-center gap-3">
                <SearchPill
                    value={draftQuery}
                    onChange={setDraftQuery}
                    onSubmit={applySearch}
                    placeholder="Search name or email…"
                    className="min-w-[16rem] max-w-md flex-1"
                />

                <FilterDropdownGroup className="contents">
                    <FilterDropdown
                        label="Industry"
                        icon={<Tags />}
                        value={filters.nicheId}
                        idleValue="all"
                        onChange={(value) => patchFilters({ nicheId: value })}
                        items={[
                            { value: 'all', label: 'Any industry' },
                            ...niches.map((niche) => ({
                                value: String(niche.id),
                                label: niche.name,
                            })),
                        ]}
                    />

                    <FilterDropdown
                        label="Country"
                        icon={<Globe />}
                        value={filters.country}
                        idleValue="all"
                        onChange={(value) => patchFilters({ country: value })}
                        items={[
                            { value: 'all', label: 'Any country' },
                            ...countries.map((item) => ({
                                value: item.value,
                                label: item.label,
                            })),
                        ]}
                    />

                    <FilterDropdown
                        label="Price"
                        icon={<ListFilter />}
                        value={filters.priceRange}
                        idleValue="all"
                        onChange={(value) =>
                            patchFilters({ priceRange: value as PriceRange })
                        }
                        items={priceRangeItems}
                    />

                    <FilterDropdown
                        label="Sort"
                        icon={<ArrowUpDown />}
                        value={sort}
                        idleValue="match"
                        onChange={(value) => setSort(value as Sort)}
                        items={sortItems}
                    />
                </FilterDropdownGroup>

                {filtersActive ? (
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        className="h-14 rounded-pill bg-card px-5"
                        onClick={clearFilters}
                    >
                        Clear
                    </Button>
                ) : null}
            </div>

            <InputError message={error ?? undefined} />

            {visible.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    {tab === 'shortlist'
                        ? 'Star creators to save them on your shortlist.'
                        : 'No vetted creators match these filters.'}
                </p>
            ) : (
                <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
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

            {tab === 'all' && list && list.last_page > 1 ? (
                <div className="flex items-center gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        className="rounded-pill"
                        disabled={list.current_page <= 1}
                        onClick={() => setPage((current) => current - 1)}
                    >
                        Previous
                    </Button>
                    <p className="text-sm text-muted-foreground">
                        Page {list.current_page} of {list.last_page}
                    </p>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        className="rounded-pill"
                        disabled={list.current_page >= list.last_page}
                        onClick={() => setPage((current) => current + 1)}
                    >
                        Next
                    </Button>
                </div>
            ) : null}

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
