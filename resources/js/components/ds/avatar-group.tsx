import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { cn } from '@/lib/utils';

export type AvatarGroupItem = {
    src?: string | null;
    alt?: string;
    fallback?: string;
};

type AvatarGroupProps = {
    items: AvatarGroupItem[];
    max?: number;
    size?: 'sm' | 'default' | 'md';
    className?: string;
};

export function AvatarGroup({
    items,
    max = 3,
    size = 'default',
    className,
}: AvatarGroupProps) {
    const visible = items.slice(0, max);
    const overflow = Math.max(0, items.length - max);

    return (
        <div className={cn('flex items-center', className)}>
            {visible.map((item, index) => (
                <Avatar
                    key={`${item.alt ?? item.fallback ?? index}-${index}`}
                    size={size}
                    className={cn(
                        'ring-background ring-2',
                        index > 0 && '-ml-2',
                    )}
                >
                    {item.src ? (
                        <AvatarImage src={item.src} alt={item.alt ?? ''} />
                    ) : null}
                    <AvatarFallback>
                        {(item.fallback ?? item.alt ?? '?').slice(0, 2)}
                    </AvatarFallback>
                </Avatar>
            ))}
            {overflow > 0 ? (
                <Avatar
                    size={size}
                    className="-ml-2 bg-primary text-primary-foreground ring-2 ring-background"
                >
                    <AvatarFallback className="bg-primary text-primary-foreground text-[10px]">
                        +{overflow}
                    </AvatarFallback>
                </Avatar>
            ) : null}
        </div>
    );
}
