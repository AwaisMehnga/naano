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
        <SoftCard title={title} className={cn('h-full', className)}>
            <div className="relative">
                <MetricStat
                    value={metric}
                    label={metricLabel}
                    hint={
                        callout ? (
                            <Badge variant="accent" className="mt-2">
                                {callout}
                            </Badge>
                        ) : null
                    }
                />
            </div>
            <div className="mt-6 h-48">
                <ResponsiveContainer width="100%" height="100%">
                    <BarChart data={data} barCategoryGap="32%">
                        <defs>
                            <pattern
                                id="activity-hatch"
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
                                    stroke="var(--chart-2)"
                                    strokeWidth="1.5"
                                />
                            </pattern>
                        </defs>
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
                            cursor={{ fill: 'var(--muted)' }}
                            contentStyle={{
                                borderRadius: 16,
                                border: '1px solid var(--border)',
                                background: 'var(--card)',
                            }}
                        />
                        <Bar dataKey="value" radius={[10, 10, 10, 10]}>
                            {data.map((entry) => (
                                <Cell
                                    key={entry.day}
                                    fill={
                                        entry.highlight
                                            ? 'url(#activity-hatch)'
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
