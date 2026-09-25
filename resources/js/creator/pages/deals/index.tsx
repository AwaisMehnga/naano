import { useEffect, useMemo, useState } from 'react';
import { useSearchParams } from 'react-router';
import { GitBranch, ListFilter } from 'lucide-react';
import InputError from '@/components/input-error';
import { FilterDropdown, FilterDropdownGroup } from '@/components/filter-select';
import { SearchPill } from '@/components/ds';
import { AppLink } from '@/components/app-link';
import { Button } from '@/components/ui/button';
import DealCard from '@/creator/pages/deals/deal-card';
import type { Deal } from '@/creator/pages/deals/types';
import { ApiError, creatorApi, http } from '@/lib/api';

const statuses = [
    'invited',
    'applied',
    'selected',
    'booked',
    'completed',
    'declined',
    'cancelled',
] as const;

const sources = ['invite', 'apply', 'sourced'] as const;

function readParam(
    params: URLSearchParams,
    key: string,
    fallback: string,
): string {
    return params.get(key)?.trim() || fallback;
}

export default function CreatorDealsPage() {
    const [searchParams, setSearchParams] = useSearchParams();

    const q = readParam(searchParams, 'q', '');
    const status = readParam(searchParams, 'status', 'all');
    const source = readParam(searchParams, 'source', 'all');

    const [draftQuery, setDraftQuery] = useState(q);
    const [items, setItems] = useState<Deal[]>([]);
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState(true);

    const statusItems = useMemo(
        () => [
            { value: 'all', label: 'All statuses' },
            ...statuses.map((value) => ({
                value,
                label: titleCase(value),
            })),
        ],
        [],
    );

    const sourceItems = useMemo(
        () => [
            { value: 'all', label: 'All sources' },
            ...sources.map((value) => ({
                value,
                label: titleCase(value),
            })),
        ],
        [],
    );

    useEffect(() => {
        setDraftQuery(q);
    }, [q]);

    useEffect(() => {
        setLoading(true);

        const controller = new AbortController();

        http.get<Deal[]>(
            creatorApi.collaborations({
                q: q || undefined,
                status: status === 'all' ? undefined : status,
                source: source === 'all' ? undefined : source,
            }),
            { signal: controller.signal },
        )
            .then(({ data }) => {
                setItems(data);
                setError(null);
            })
            .catch((caught: unknown) => {
                if (
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
                        : 'Could not load deals.',
                );
            })
            .finally(() => setLoading(false));

        return () => controller.abort();
    }, [q, status, source]);

    function patchParams(patch: Record<string, string | null>) {
        setSearchParams(
            (current) => {
                const next = new URLSearchParams(current);

                for (const [key, value] of Object.entries(patch)) {
                    if (value === null || value === '' || value === 'all') {
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

    const filtersActive = q !== '' || status !== 'all' || source !== 'all';

    return (
        <div className="flex w-full flex-1 flex-col gap-8">
            <div className="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div className="space-y-2">
                    <p className="text-sm text-muted-foreground">Creator</p>
                    <h1 className="text-heading font-medium tracking-tight">
                        Deals
                    </h1>
                    <p className="max-w-xl text-sm text-muted-foreground">
                        Invites, applications, and booked collaborations in one
                        place.
                    </p>
                </div>
                {!loading ? (
                    <p className="text-sm text-muted-foreground">
                        <span className="font-medium text-foreground">
                            {items.length}
                        </span>{' '}
                        {items.length === 1 ? 'deal' : 'deals'}
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
                        label="Status"
                        icon={<ListFilter />}
                        value={status}
                        onChange={(value) => patchParams({ status: value })}
                        items={statusItems}
                    />

                    <FilterDropdown
                        label="Source"
                        icon={<GitBranch />}
                        value={source}
                        onChange={(value) => patchParams({ source: value })}
                        items={sourceItems}
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
                <p className="text-sm text-muted-foreground">Loading deals…</p>
            ) : items.length === 0 ? (
                <div className="space-y-3 rounded-3xl bg-muted p-6">
                    <h2 className="text-lg font-medium">No deals yet</h2>
                    <p className="text-sm text-muted-foreground">
                        Apply to an opportunity or wait for an invite.
                    </p>
                    <Button className="rounded-pill" asChild>
                        <AppLink href="/opportunities">
                            Browse opportunities
                        </AppLink>
                    </Button>
                </div>
            ) : (
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    {items.map((item) => (
                        <DealCard key={item.id} deal={item} />
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
