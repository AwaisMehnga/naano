import {
    CartesianGrid,
    Line,
    LineChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
} from 'recharts';
import { Badge } from '@/components/ui/badge';
import { MetricStat } from '@/components/ds/metric-stat';
import { SoftCard } from '@/components/ds/soft-card';
import { cn } from '@/lib/utils';

export type SpendPoint = {
    day: string;
    value: number;
};

type SpendLineChartProps = {
    title?: string;
    metric: string;
    compare?: string;
    sideStats?: { label: string; value: string }[];
    data: SpendPoint[];
    callout?: string;
    className?: string;
};

export function SpendLineChart({
    title = 'Total Spend',
    metric,
    compare,
    sideStats = [],
    data,
    callout,
    className,
}: SpendLineChartProps) {
    return (
        <SoftCard title={title} className={cn('h-full', className)}>
            <div className="flex flex-col gap-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <MetricStat
                        value={metric}
                        hint={
                            compare ? (
                                <span className="text-muted-foreground">
                                    {compare}
                                </span>
                            ) : null
                        }
                    />
                    {sideStats.length > 0 ? (
                        <div className="flex flex-wrap items-center gap-2">
                            {sideStats.map((stat) => (
                                <div
                                    key={stat.label}
                                    className="inline-flex items-center gap-2 rounded-pill bg-muted px-4 py-2"
                                >
                                    <span className="text-sm font-medium tabular-nums">
                                        {stat.value}
                                    </span>
                                    <span className="text-sm text-muted-foreground">
                                        {stat.label}
                                    </span>
                                </div>
                            ))}
                        </div>
                    ) : null}
                </div>
                <div className="relative h-44">
                    {callout ? (
                        <Badge
                            variant="accent"
                            className="absolute top-0 right-4 z-10"
                        >
                            {callout}
                        </Badge>
                    ) : null}
                    <ResponsiveContainer width="100%" height="100%">
                        <LineChart data={data}>
                            <CartesianGrid
                                vertical={false}
                                stroke="var(--border)"
                                strokeDasharray="4 4"
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
                                    borderRadius: 16,
                                    border: '1px solid var(--border)',
                                    background: 'var(--card)',
                                }}
                            />
                            <Line
                                type="monotone"
                                dataKey="value"
                                stroke="var(--chart-2)"
                                strokeWidth={2}
                                dot={{
                                    r: 5,
                                    fill: 'var(--card)',
                                    stroke: 'var(--chart-2)',
                                    strokeWidth: 2,
                                }}
                                activeDot={{
                                    r: 6,
                                    fill: 'var(--chart-1)',
                                    stroke: 'var(--chart-1)',
                                }}
                            />
                        </LineChart>
                    </ResponsiveContainer>
                </div>
            </div>
        </SoftCard>
    );
}
