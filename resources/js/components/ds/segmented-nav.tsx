import { cn } from '@/lib/utils';

type SegmentedNavItem = {
    id: string;
    label: string;
};

type SegmentedNavProps = {
    items: SegmentedNavItem[];
    value: string;
    onChange?: (id: string) => void;
    className?: string;
};

export function SegmentedNav({
    items,
    value,
    onChange,
    className,
}: SegmentedNavProps) {
    return (
        <div
            className={cn(
                'inline-flex items-center gap-2 rounded-pill border border-border bg-card p-2',
                className,
            )}
            role="tablist"
        >
            {items.map((item) => {
                const active = item.id === value;

                return (
                    <button
                        key={item.id}
                        type="button"
                        role="tab"
                        aria-selected={active}
                        onClick={() => onChange?.(item.id)}
                        className={cn(
                            'rounded-pill px-6 py-2.5 text-sm font-medium transition-colors',
                            active
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                        )}
                    >
                        {item.label}
                    </button>
                );
            })}
        </div>
    );
}
