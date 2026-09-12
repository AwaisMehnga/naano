import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export function ChipToggle({
    selected,
    onToggle,
    children,
}: {
    selected: boolean;
    onToggle: () => void;
    children: ReactNode;
}) {
    return (
        <button
            type="button"
            onClick={onToggle}
            className={cn(
                'rounded-full border px-3 py-1 text-sm transition-colors',
                selected
                    ? 'border-primary bg-primary text-primary-foreground'
                    : 'border-border bg-background text-foreground hover:bg-accent',
            )}
        >
            {children}
        </button>
    );
}
