import { cn } from '@/lib/utils';

type ProgressRowProps = {
    label: string;
    value: number;
    className?: string;
};

export function ProgressRow({ label, value, className }: ProgressRowProps) {
    const clamped = Math.max(0, Math.min(100, value));

    return (
        <div
            className={cn(
                'flex items-center gap-4 rounded-pill bg-muted px-4 py-3',
                className,
            )}
        >
            <span className="min-w-16 shrink-0 text-sm font-medium text-foreground">
                {label}
            </span>
            <div className="h-2.5 min-w-0 flex-1 overflow-hidden rounded-pill bg-card">
                <div
                    className="h-full rounded-pill bg-primary"
                    style={{ width: `${clamped}%` }}
                />
            </div>
            <span className="shrink-0 text-sm font-medium tabular-nums text-foreground">
                {clamped}%
            </span>
        </div>
    );
}
