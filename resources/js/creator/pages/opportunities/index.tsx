import { useEffect, useMemo, useState } from 'react';
import { useSearchParams } from 'react-router';
import { ArrowUpDown, Globe2, Percent, Target } from 'lucide-react';
import InputError from '@/components/input-error';
import { FilterDropdown, FilterDropdownGroup } from '@/components/filter-select';
import { SearchPill } from '@/components/ds';
import { Button } from '@/components/ui/button';
import OpportunityCard from '@/creator/pages/opportunities/opportunity-card';
import type { Opportunity } from '@/creator/pages/opportunities/types';
import { ApiError, creatorApi, http } from '@/lib/api';
import { countries } from '@/lib/lookups';

const objectives = [
    'awareness',
    'pipeline',
    'talent',
    'community',
] as const;

type MatchFilter = 'all' | '70' | '50';
type SortFilter = 'match' | 'deadline' | 'name';

function readParam(
    params: URLSearchParams,
    key: string,
    fallback: string,
): string {
    return params.get(key)?.trim() || fallback;
}

export default function CreatorOpportunitiesPage() {
    const [searchParams, setSearchParams] = useSearchParams();

    const q = readParam(searchParams, 'q', '');
    const objective = readParam(searchParams, 'objective', 'all');
    const country = readParam(searchParams, 'country', 'all');
    const match = readParam(searchParams, 'match', 'all') as MatchFilter;
    const sort = readParam(searchParams, 'sort', 'match') as SortFilter;

    const [draftQuery, setDraftQuery] = useState(q);
    const [items, setItems] = useState<Opportunity[]>([]);
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState(true);
    const [reloadKey, setReloadKey] = useState(0);

    useEffect(() => {
        setDraftQuery(q);
    }, [q]);

    useEffect(() => {
        setLoading(true);

        const controller = new AbortController();

        http.get<Opportunity[]>(
            creatorApi.opportunities({
                q: q || undefined,
                objective: objective === 'all' ? undefined : objective,
                country: country === 'all' ? undefined : country,
                match: match === 'all' ? undefined : match,
                sort: sort === 'match' ? undefined : sort,
            }),
            { signal: controller.signal },
        )
            .then(({ data }) => {
                setItems(data);
                setError(null);
            })
            .catch((caught: unknown) => {
                if (
                    caught instanceof ApiError === false &&
                    typeof caught === 'object' &&
                    caught !== null &&
                    'code' in caught &&
                    (caught as { code?: string }).code === 'ERR_CANCELED'
                ) {
                    return;
                }

                setError(
                    caught instanceof ApiError
                        ? caught.message
                        : 'Could not load opportunities.',
                );
            })
            .finally(() => setLoading(false));

        return () => controller.abort();
    }, [q, objective, country, match, sort, reloadKey]);

    const objectiveItems = useMemo(
        () => [
            { value: 'all', label: 'All objectives' },
            ...objectives.map((value) => ({
                value,
                label: titleCase(value),
            })),
        ],
        [],
    );

    const countryItems = useMemo(
        () => [
            { value: 'all', label: 'All countries' },
            ...countries.map((item) => ({
                value: item.value,
                label: item.label,
            })),
        ],
        [],
    );

    const matchItems = useMemo(
        () => [
            { value: 'all', label: 'Any match' },
            { value: '70', label: '70%+ match' },
            { value: '50', label: '50%+ match' },
        ],
        [],
    );

    const sortItems = useMemo(
        () => [
            { value: 'match', label: 'Best match' },
            { value: 'deadline', label: 'Deadline' },
            { value: 'name', label: 'Name' },
        ],
        [],
    );

    function patchParams(patch: Record<string, string | null>) {
        setSearchParams(
            (current) => {
                const next = new URLSearchParams(current);

                for (const [key, value] of Object.entries(patch)) {
                    if (
                        value === null ||
                        value === '' ||
                        value === 'all' ||
                        (key === 'sort' && value === 'match')
                    ) {
                        next.delete(key);
                    } else {
                        next.set(key, value);
                    }
                }

                return next;
            },
            { replace: true },
        );
    }

    function clearFilters() {
        setDraftQuery('');
        setSearchParams({}, { replace: true });
    }

    const filtersActive =
        q !== '' ||
        objective !== 'all' ||
        country !== 'all' ||
        match !== 'all' ||
        sort !== 'match';

    return (
        <div className="flex w-full flex-1 flex-col gap-8">
            <div className="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div className="space-y-2">
                    <p className="text-sm text-muted-foreground">Creator</p>
                    <h1 className="text-heading font-medium tracking-tight">
                        Opportunities
                    </h1>
                    <p className="max-w-xl text-sm text-muted-foreground">
                        Campaigns that match your niches, country, and audience.
                    </p>
                </div>
                {!loading ? (
                    <p className="text-sm text-muted-foreground">
                        <span className="font-medium text-foreground">
                            {items.length}
                        </span>{' '}
                        {items.length === 1 ? 'result' : 'results'}
                    </p>
                ) : null}
            </div>

            <div className="flex flex-wrap items-center gap-3">
                <SearchPill
                    value={draftQuery}
                    onChange={setDraftQuery}
                    onSubmit={() =>
                        patchParams({ q: draftQuery.trim() || null })
                    }
                    placeholder="Search and filter…"
                    className="min-w-[16rem] max-w-md flex-1"
                />

                <FilterDropdownGroup className="contents">
                    <FilterDropdown
                        label="Objective"
                        icon={<Target />}
                        value={objective}
                        onChange={(value) =>
                            patchParams({ objective: value })
                        }
                        items={objectiveItems}
                    />

                    <FilterDropdown
                        label="Country"
                        icon={<Globe2 />}
                        value={country}
                        onChange={(value) => patchParams({ country: value })}
                        items={countryItems}
                    />

                    <FilterDropdown
                        label="Match"
                        icon={<Percent />}
                        value={match}
                        onChange={(value) => patchParams({ match: value })}
                        items={matchItems}
                    />

                    <FilterDropdown
                        label="Sort"
                        icon={<ArrowUpDown />}
                        value={sort}
                        onChange={(value) => patchParams({ sort: value })}
                        items={sortItems}
                        idleValue="match"
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

            {loading ? (
                <p className="text-sm text-muted-foreground">
                    Loading opportunities…
                </p>
            ) : items.length === 0 ? (
                <div className="space-y-3 rounded-3xl bg-muted p-6">
                    <h2 className="text-lg font-medium">No matches</h2>
                    <p className="text-sm text-muted-foreground">
                        Try clearing filters or searching another company.
                    </p>
                    {filtersActive ? (
                        <Button
                            type="button"
                            variant="outline"
                            className="rounded-pill bg-card"
                            onClick={clearFilters}
                        >
                            Clear
                        </Button>
                    ) : null}
                </div>
            ) : (
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    {items.map((item) => (
                        <OpportunityCard
                            key={item.id}
                            opportunity={item}
                            onApplied={() =>
                                setReloadKey((key) => key + 1)
                            }
                        />
                    ))}
                </div>
            )}
        </div>
    );
}

function titleCase(value: string): string {
    return value
        .replaceAll('_', ' ')
        .replace(/^\w/, (letter) => letter.toUpperCase());
}
