import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type SoftCardProps = {
    title?: string;
    action?: ReactNode;
    children: ReactNode;
    className?: string;
};

export function SoftCard({
    title,
    action,
    children,
    className,
}: SoftCardProps) {
    return (
        <div
            className={cn(
                'rounded-3xl border border-border bg-card p-6 text-card-foreground',
                className,
            )}
        >
            {(title || action) && (
                <div className="mb-5 flex items-start justify-between gap-3">
                    {title ? (
                        <h3 className="text-sm font-medium text-muted-foreground">
                            {title}
                        </h3>
                    ) : (
                        <span />
                    )}
                    {action ? (
                        <div className="flex items-center gap-2">{action}</div>
                    ) : null}
                </div>
            )}
            {children}
        </div>
    );
}
