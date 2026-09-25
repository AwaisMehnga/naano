import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type GlassPanelProps = {
    title?: string;
    children: ReactNode;
    className?: string;
};

export function GlassPanel({ title, children, className }: GlassPanelProps) {
    return (
        <div
            className={cn(
                'rounded-2xl border border-border/60 bg-card/70 p-4 shadow-[var(--shadow-soft)] backdrop-blur-md',
                className,
            )}
        >
            {title ? (
                <p className="mb-3 text-sm font-medium text-muted-foreground">
                    {title}
                </p>
            ) : null}
            {children}
        </div>
    );
}
