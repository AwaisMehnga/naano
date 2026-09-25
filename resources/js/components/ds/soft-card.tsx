import { ArrowUpRight } from 'lucide-react';
import type { ReactNode } from 'react';
import { IconButton } from '@/components/ds/icon-button';
import { cn } from '@/lib/utils';

type SoftCardProps = {
    title?: string;
    action?: ReactNode;
    showExpand?: boolean;
    onExpand?: () => void;
    children: ReactNode;
    className?: string;
};

export function SoftCard({
    title,
    action,
    showExpand = false,
    onExpand,
    children,
    className,
}: SoftCardProps) {
    return (
        <div
            className={cn(
                'rounded-2xl bg-card p-5 text-card-foreground shadow-[var(--shadow-soft)]',
                className,
            )}
        >
            {(title || action || showExpand) && (
                <div className="mb-4 flex items-start justify-between gap-3">
                    {title ? (
                        <h3 className="text-sm font-medium">{title}</h3>
                    ) : (
                        <span />
                    )}
                    <div className="flex items-center gap-2">
                        {action}
                        {showExpand ? (
                            <IconButton
                                variant="outline"
                                size="sm"
                                aria-label="Expand"
                                onClick={onExpand}
                            >
                                <ArrowUpRight />
                            </IconButton>
                        ) : null}
                    </div>
                </div>
            )}
            {children}
        </div>
    );
}
