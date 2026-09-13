import {
    useEffect,
    useMemo,
    useState,
    type ReactNode,
} from 'react';
import { Filter, Search } from 'lucide-react';
import { Button, buttonVariants } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';
import type { VariantProps } from 'class-variance-authority';

export type LaravelPage<T> = {
    data?: T[];
    items?: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

export type DataTableColumn<T> = {
    key: string;
    header: string;
    cell: (row: T) => ReactNode;
};

export type DataTableAction = {
    label: string;
    onClick: () => void;
    variant?: VariantProps<typeof buttonVariants>['variant'];
    icon?: ReactNode;
};

export type FilterGroup = {
    label: string;
    filters: Array<{
        key: string;
        label: string;
        options: Array<{ value: string; label: string }>;
    }>;
};

export type DataTableQuery = {
    page: number;
    per_page: number;
    q: string;
    filters: Record<string, string | null>;
};

const perPageOptions = [10, 25, 50];

export function pageRows<T>(page: LaravelPage<T> | T[] | null): T[] {
    if (Array.isArray(page)) {
        return page;
    }

    const rows = page?.data ?? page?.items ?? [];

    if (Array.isArray(rows)) {
        return rows;
    }

    if (rows && typeof rows === 'object') {
        const nested = rows as LaravelPage<T>;

        if (Array.isArray(nested.data)) {
            return nested.data;
        }

        if (Array.isArray(nested.items)) {
            return nested.items;
        }
    }

    return [];
}

export function DataTable<T>({
    page,
    columns,
    rowKey,
    query,
    onQueryChange,
    search = true,
    searchPlaceholder = 'Search',
    filterGroups = [],
    actions = [],
    loading = false,
    empty = 'No results.',
}: {
    page: LaravelPage<T> | null;
    columns: DataTableColumn<T>[];
    rowKey: (row: T) => string | number;
    query: DataTableQuery;
    onQueryChange: (query: DataTableQuery) => void;
    search?: boolean;
    searchPlaceholder?: string;
    filterGroups?: FilterGroup[];
    actions?: DataTableAction[];
    loading?: boolean;
    empty?: ReactNode;
}) {
    const rows = pageRows(page);
    const [searchValue, setSearchValue] = useState(query.q);
    const [jump, setJump] = useState(String(query.page));

    useEffect(() => {
        setSearchValue(query.q);
    }, [query.q]);

    useEffect(() => {
        setJump(String(query.page));
    }, [query.page]);

    useEffect(() => {
        const handle = window.setTimeout(() => {
            if (searchValue === query.q) {
                return;
            }

            onQueryChange({ ...query, q: searchValue, page: 1 });
        }, 300);

        return () => window.clearTimeout(handle);
    }, [onQueryChange, query, searchValue]);

    const activeFilterCount = Object.values(query.filters).filter(
        (value) => value !== null && value !== '',
    ).length;
    const filtersActive = query.q.trim() !== '' || activeFilterCount > 0;
    const lastPage = Math.max(page?.last_page ?? 1, 1);
    const pages = useMemo(
        () => pageWindow(query.page, lastPage),
        [lastPage, query.page],
    );

    function setFilter(key: string, value: string | null) {
        onQueryChange({
            ...query,
            page: 1,
            filters: { ...query.filters, [key]: value },
        });
    }

    function clearFilters() {
        const filters = Object.fromEntries(
            Object.keys(query.filters).map((key) => [key, null]),
        );

        setSearchValue('');
        onQueryChange({ ...query, page: 1, q: '', filters });
    }

    function goTo(next: number) {
        const pageNumber = Math.min(lastPage, Math.max(1, next));

        if (pageNumber !== query.page) {
            onQueryChange({ ...query, page: pageNumber });
        }
    }

    return (
        <div className="flex flex-col gap-3">
            <div className="flex flex-wrap items-center gap-2">
                {search && (
                    <div className="relative min-w-48 flex-1">
                        <Search className="pointer-events-none absolute top-1/2 left-2.5 size-3.5 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={searchValue}
                            onChange={(event) =>
                                setSearchValue(event.target.value)
                            }
                            placeholder={searchPlaceholder}
                            autoComplete="off"
                            className="pl-8"
                        />
                    </div>
                )}
                <div className="ml-auto flex flex-wrap items-center gap-2">
                    {filtersActive && (
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={clearFilters}
                        >
                            Clear filters
                        </Button>
                    )}
                    {filterGroups.length > 0 && (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button type="button" variant="outline" size="sm">
                                    <Filter className="size-3.5" />
                                    Filters
                                    {activeFilterCount > 0
                                        ? ` (${activeFilterCount})`
                                        : ''}
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-56">
                                {filterGroups.map((group, index) => (
                                    <div key={group.label}>
                                        {index > 0 && <DropdownMenuSeparator />}
                                        <DropdownMenuLabel>
                                            {group.label}
                                        </DropdownMenuLabel>
                                        {group.filters.map((filter) => (
                                            <DropdownMenuRadioGroup
                                                key={filter.key}
                                                value={
                                                    query.filters[filter.key] ??
                                                    'all'
                                                }
                                                onValueChange={(value) =>
                                                    setFilter(
                                                        filter.key,
                                                        value === 'all'
                                                            ? null
                                                            : value,
                                                    )
                                                }
                                            >
                                                <DropdownMenuRadioItem value="all">
                                                    Any {filter.label.toLowerCase()}
                                                </DropdownMenuRadioItem>
                                                {filter.options.map((option) => (
                                                    <DropdownMenuRadioItem
                                                        key={option.value}
                                                        value={option.value}
                                                    >
                                                        {option.label}
                                                    </DropdownMenuRadioItem>
                                                ))}
                                            </DropdownMenuRadioGroup>
                                        ))}
                                    </div>
                                ))}
                            </DropdownMenuContent>
                        </DropdownMenu>
                    )}
                    {actions.map((action) => (
                        <Button
                            key={action.label}
                            type="button"
                            variant={action.variant ?? 'default'}
                            size="sm"
                            onClick={action.onClick}
                        >
                            {action.icon}
                            {action.label}
                        </Button>
                    ))}
                </div>
            </div>
            <div className="overflow-hidden rounded-lg border border-border bg-card">
                <Table>
                    <TableHeader>
                        <TableRow className="hover:bg-transparent">
                            {columns.map((column) => (
                                <TableHead key={column.key}>
                                    {column.header}
                                </TableHead>
                            ))}
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {loading && rows.length === 0 ? (
                            <TableRow>
                                <TableCell
                                    colSpan={columns.length}
                                    className="text-muted-foreground py-10 text-center"
                                >
                                    Loading…
                                </TableCell>
                            </TableRow>
                        ) : rows.length === 0 ? (
                            <TableRow>
                                <TableCell
                                    colSpan={columns.length}
                                    className="text-muted-foreground py-10 text-center"
                                >
                                    {empty}
                                </TableCell>
                            </TableRow>
                        ) : (
                            rows.map((row) => (
                                <TableRow key={rowKey(row)}>
                                    {columns.map((column) => (
                                        <TableCell key={column.key}>
                                            {column.cell(row)}
                                        </TableCell>
                                    ))}
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                </Table>
            </div>
            <div className="flex flex-wrap items-center gap-3 text-sm">
                <p className="text-muted-foreground">
                    {page
                        ? `${page.from ?? 0}–${page.to ?? 0} of ${page.total}`
                        : '—'}
                </p>
                <div className="flex items-center gap-1">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        disabled={query.page <= 1}
                        onClick={() => goTo(query.page - 1)}
                    >
                        Previous
                    </Button>
                    {pages.map((item) => (
                        <Button
                            key={item}
                            type="button"
                            variant={
                                item === query.page ? 'default' : 'outline'
                            }
                            size="sm"
                            className={cn(item === query.page && 'pointer-events-none')}
                            onClick={() => goTo(item)}
                        >
                            {item}
                        </Button>
                    ))}
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        disabled={query.page >= lastPage}
                        onClick={() => goTo(query.page + 1)}
                    >
                        Next
                    </Button>
                </div>
                <div className="ml-auto flex flex-wrap items-center gap-3">
                    <div className="flex items-center gap-2">
                        <Label className="text-muted-foreground">Per page</Label>
                        <Select
                            value={String(query.per_page)}
                            onValueChange={(value) =>
                                onQueryChange({
                                    ...query,
                                    page: 1,
                                    per_page: Number(value),
                                })
                            }
                        >
                            <SelectTrigger className="h-8 w-18">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {perPageOptions.map((option) => (
                                    <SelectItem
                                        key={option}
                                        value={String(option)}
                                    >
                                        {option}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <form
                        className="flex items-center gap-2"
                        onSubmit={(event) => {
                            event.preventDefault();
                            goTo(Number(jump) || 1);
                        }}
                    >
                        <Label
                            htmlFor="data-table-jump"
                            className="text-muted-foreground"
                        >
                            Page
                        </Label>
                        <Input
                            id="data-table-jump"
                            value={jump}
                            onChange={(event) => setJump(event.target.value)}
                            className="h-8 w-16"
                            inputMode="numeric"
                        />
                        <Button type="submit" variant="outline" size="sm">
                            Go
                        </Button>
                    </form>
                </div>
            </div>
        </div>
    );
}

function pageWindow(current: number, last: number): number[] {
    const start = Math.max(1, current - 2);
    const end = Math.min(last, current + 2);

    return Array.from({ length: end - start + 1 }, (_, i) => start + i);
}
