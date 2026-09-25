import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    ResponsiveContainer,
    Tooltip,
    XAxis,
} from 'recharts';
import { MetricStat } from '@/components/ds/metric-stat';
import { SoftCard } from '@/components/ds/soft-card';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

export type ActivityPoint = {
    day: string;
    value: number;
    highlight?: boolean;
};

type ActivityBarChartProps = {
    title?: string;
    metric: string;
    metricLabel?: string;
    data: ActivityPoint[];
    callout?: string;
    className?: string;
};

export function ActivityBarChart({
    title = 'Activity',
    metric,
    metricLabel = 'Worked this week',
    data,
    callout,
    className,
}: ActivityBarChartProps) {
    return (
        <SoftCard title={title} showExpand className={cn(className)}>
            <MetricStat value={metric} label={metricLabel} />
            <div className="relative mt-4 h-40">
                {callout ? (
                    <Badge
                        variant="accent"
                        className="absolute top-0 left-1/2 z-10 -translate-x-1/2"
                    >
                        {callout}
                    </Badge>
                ) : null}
                <ResponsiveContainer width="100%" height="100%">
                    <BarChart data={data} barCategoryGap="28%">
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
                            cursor={{ fill: 'var(--muted)' }}
                            contentStyle={{
                                borderRadius: 12,
                                border: '1px solid var(--border)',
                                background: 'var(--card)',
                            }}
                        />
                        <Bar dataKey="value" radius={[8, 8, 8, 8]}>
                            {data.map((entry) => (
                                <Cell
                                    key={entry.day}
                                    fill={
                                        entry.highlight
                                            ? 'var(--chart-1)'
                                            : 'var(--chart-5)'
                                    }
                                />
                            ))}
                        </Bar>
                    </BarChart>
                </ResponsiveContainer>
            </div>
        </SoftCard>
    );
}
