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
        <SoftCard title={title} showExpand className={cn(className)}>
            <div className="flex gap-6">
                {sideStats.length > 0 ? (
                    <div className="hidden w-24 shrink-0 flex-col gap-3 sm:flex">
                        {sideStats.map((stat) => (
                            <div key={stat.label}>
                                <p className="text-sm font-medium">{stat.value}</p>
                                <p className="text-xs text-muted-foreground">
                                    {stat.label}
                                </p>
                            </div>
                        ))}
                    </div>
                ) : null}
                <div className="min-w-0 flex-1">
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
                    <div className="relative mt-4 h-36">
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
                                <Line
                                    type="monotone"
                                    dataKey="value"
                                    stroke="var(--chart-2)"
                                    strokeWidth={2}
                                    dot={{
                                        r: 4,
                                        fill: 'var(--card)',
                                        stroke: 'var(--chart-2)',
                                        strokeWidth: 2,
                                    }}
                                    activeDot={{
                                        r: 5,
                                        fill: 'var(--chart-1)',
                                        stroke: 'var(--chart-1)',
                                    }}
                                />
                            </LineChart>
                        </ResponsiveContainer>
                    </div>
                </div>
            </div>
        </SoftCard>
    );
}
