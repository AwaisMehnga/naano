import { Search } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { IconButton } from '@/components/ds/icon-button';
import { cn } from '@/lib/utils';

type SearchPillProps = {
    value: string;
    onChange: (value: string) => void;
    onSubmit?: () => void;
    placeholder?: string;
    className?: string;
    trailing?: ReactNode;
};

/** Pill search with primary circular action. */
export function SearchPill({
    value,
    onChange,
    onSubmit,
    placeholder = 'Search and filter…',
    className,
    trailing,
}: SearchPillProps) {
    function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        onSubmit?.();
    }

    return (
        <form
            onSubmit={handleSubmit}
            className={cn(
                'flex h-14 items-center gap-2 rounded-pill bg-card py-1.5 pr-1.5 pl-6',
                className,
            )}
        >
            <input
                value={value}
                onChange={(event) => onChange(event.target.value)}
                placeholder={placeholder}
                className="min-w-0 flex-1 bg-transparent text-sm text-foreground outline-none placeholder:text-muted-foreground"
            />
            {trailing}
            <IconButton type="submit" variant="default" aria-label="Search">
                <Search />
            </IconButton>
        </form>
    );
}

type FilterToolbarProps = {
    className?: string;
    children?: ReactNode;
};

/** Horizontal row for separate filter pills / circular actions — never a single bundled dropdown. */
export function FilterToolbar({ className, children }: FilterToolbarProps) {
    return (
        <div className={cn('flex flex-wrap items-center gap-2', className)}>
            {children}
        </div>
    );
}
