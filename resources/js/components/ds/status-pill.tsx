import { ChevronDown } from 'lucide-react';
import { forwardRef, type ButtonHTMLAttributes } from 'react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { cn } from '@/lib/utils';

type StatusPillProps = {
    label: string;
    avatarSrc?: string | null;
    avatarFallback?: string;
} & Omit<ButtonHTMLAttributes<HTMLButtonElement>, 'children'>;

export const StatusPill = forwardRef<HTMLButtonElement, StatusPillProps>(
    function StatusPill(
        {
            label,
            avatarSrc,
            avatarFallback = '?',
            className,
            type = 'button',
            ...props
        },
        ref,
    ) {
        return (
            <button
                ref={ref}
                type={type}
                className={cn(
                    'inline-flex items-center gap-2.5 rounded-pill border border-border bg-card py-1.5 pr-4 pl-1.5 text-sm font-medium',
                    className,
                )}
                {...props}
            >
                <Avatar size="sm">
                    {avatarSrc ? <AvatarImage src={avatarSrc} alt="" /> : null}
                    <AvatarFallback>
                        {avatarFallback.slice(0, 2)}
                    </AvatarFallback>
                </Avatar>
                <span>{label}</span>
                <ChevronDown className="size-3.5 text-muted-foreground" />
            </button>
        );
    },
);
