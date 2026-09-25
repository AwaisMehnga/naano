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
                'inline-flex items-center gap-1 rounded-pill bg-muted p-1',
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
                            'rounded-pill px-4 py-1.5 text-sm font-medium transition-colors',
                            active
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:text-foreground',
                        )}
                    >
                        {item.label}
                    </button>
                );
            })}
        </div>
    );
}
