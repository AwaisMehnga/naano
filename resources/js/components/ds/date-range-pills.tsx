import { cn } from '@/lib/utils';

type DateRangePillsProps = {
    start: string;
    end: string;
    className?: string;
};

export function DateRangePills({ start, end, className }: DateRangePillsProps) {
    return (
        <div className={cn('flex items-center gap-2', className)}>
            <span className="rounded-pill border border-border bg-card px-5 py-2.5 text-sm tabular-nums">
                {start}
            </span>
            <span className="text-xs text-muted-foreground">→</span>
            <span className="rounded-pill border border-border bg-card px-5 py-2.5 text-sm tabular-nums">
                {end}
            </span>
        </div>
    );
}
