import { ChevronDown } from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { cn } from '@/lib/utils';

type StatusPillProps = {
    label: string;
    avatarSrc?: string | null;
    avatarFallback?: string;
    className?: string;
    onClick?: () => void;
};

export function StatusPill({
    label,
    avatarSrc,
    avatarFallback = '?',
    className,
    onClick,
}: StatusPillProps) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={cn(
                'inline-flex items-center gap-2 rounded-pill border border-border bg-card py-1 pr-3 pl-1 text-sm font-medium',
                className,
            )}
        >
            <Avatar size="sm">
                {avatarSrc ? <AvatarImage src={avatarSrc} alt="" /> : null}
                <AvatarFallback>{avatarFallback.slice(0, 2)}</AvatarFallback>
            </Avatar>
            <span>{label}</span>
            <ChevronDown className="size-3.5 text-muted-foreground" />
        </button>
    );
}
