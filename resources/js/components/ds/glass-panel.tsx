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
                'rounded-3xl border border-border/50 bg-card/75 p-5 backdrop-blur-md',
                className,
            )}
        >
            {title ? (
                <p className="mb-4 text-sm font-medium text-muted-foreground">
                    {title}
                </p>
            ) : null}
            {children}
        </div>
    );
}
