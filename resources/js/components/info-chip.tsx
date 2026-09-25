import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type InfoChipProps = {
    children: ReactNode;
    className?: string;
};

/** Compact outline chip for card metadata (country, match, status). */
export function InfoChip({ children, className }: InfoChipProps) {
    return (
        <span
            className={cn(
                'inline-flex max-w-full items-center truncate rounded-pill border border-border bg-card px-2.5 py-1 text-[11px] font-medium text-foreground',
                className,
            )}
        >
            {children}
        </span>
    );
}
