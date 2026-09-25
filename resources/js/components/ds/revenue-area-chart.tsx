import {
    Area,
    AreaChart,
    CartesianGrid,
    ResponsiveContainer,
    Tooltip,
    XAxis,
} from 'recharts';
import { Badge } from '@/components/ui/badge';
import { MetricStat } from '@/components/ds/metric-stat';
import { SoftCard } from '@/components/ds/soft-card';
import { cn } from '@/lib/utils';

export type RevenuePoint = {
    day: string;
    current: number;
    previous: number;
};

type RevenueAreaChartProps = {
    title?: string;
    metric: string;
    metricLabel?: string;
    data: RevenuePoint[];
    growth?: string;
    className?: string;
};

export function RevenueAreaChart({
    title = 'Comparison of Revenue',
    metric,
    metricLabel = 'For all time',
    data,
    growth,
    className,
}: RevenueAreaChartProps) {
    return (
        <SoftCard title={title} showExpand className={cn(className)}>
            <div className="flex items-start justify-between gap-3">
                <MetricStat value={metric} label={metricLabel} />
                {growth ? <Badge variant="accent">{growth}</Badge> : null}
            </div>
            <div className="mt-4 h-40">
                <ResponsiveContainer width="100%" height="100%">
                    <AreaChart data={data}>
                        <defs>
                            <pattern
                                id="revenue-hatch"
                                width="6"
                                height="6"
                                patternUnits="userSpaceOnUse"
                                patternTransform="rotate(45)"
                            >
                                <line
                                    x1="0"
                                    y1="0"
                                    x2="0"
                                    y2="6"
                                    stroke="var(--chart-3)"
                                    strokeWidth="1.5"
                                />
                            </pattern>
                        </defs>
                        <CartesianGrid
                            vertical={false}
                            stroke="var(--border)"
                            strokeDasharray="3 3"
                        />
                        <XAxis
                            dataKey="day"
                            axisLine={false}
                            tickLine={false}
                            tick={{
                                fill: 'var(--muted-foreground)',
                                fontSize: 12,
                            }}
                        />
                        <Tooltip
                            contentStyle={{
                                borderRadius: 12,
                                border: '1px solid var(--border)',
                                background: 'var(--card)',
                            }}
                        />
                        <Area
                            type="monotone"
                            dataKey="previous"
                            stroke="var(--chart-3)"
                            fill="url(#revenue-hatch)"
                            strokeWidth={1.5}
                        />
                        <Area
                            type="monotone"
                            dataKey="current"
                            stroke="var(--chart-2)"
                            fill="var(--lime-soft)"
                            fillOpacity={0.35}
                            strokeWidth={2}
                        />
                    </AreaChart>
                </ResponsiveContainer>
            </div>
        </SoftCard>
    );
}
