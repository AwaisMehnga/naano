import { cn } from '@/lib/utils';

type ProgressRowProps = {
    label: string;
    value: number;
    className?: string;
};

export function ProgressRow({ label, value, className }: ProgressRowProps) {
    const clamped = Math.max(0, Math.min(100, value));

    return (
        <div className={cn('grid grid-cols-[1fr_auto] items-center gap-3', className)}>
            <div className="min-w-0">
                <div className="mb-1.5 flex items-center justify-between gap-2">
                    <span className="truncate text-sm font-medium">{label}</span>
                </div>
                <div className="h-2 overflow-hidden rounded-pill bg-muted">
                    <div
                        className="h-full rounded-pill bg-foreground/70"
                        style={{ width: `${clamped}%` }}
                    />
                </div>
            </div>
            <span className="text-sm text-muted-foreground tabular-nums">
                {clamped}%
            </span>
        </div>
    );
}
