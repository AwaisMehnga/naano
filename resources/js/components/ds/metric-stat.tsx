import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type MetricStatProps = {
    value: string;
    label?: string;
    hint?: ReactNode;
    className?: string;
};

export function MetricStat({ value, label, hint, className }: MetricStatProps) {
    return (
        <div className={cn('flex flex-col gap-1', className)}>
            <p className="text-heading font-medium tracking-tight">{value}</p>
            {label ? (
                <p className="text-sm text-muted-foreground">{label}</p>
            ) : null}
            {hint ? (
                <div className="text-sm text-muted-foreground">{hint}</div>
            ) : null}
        </div>
    );
}
