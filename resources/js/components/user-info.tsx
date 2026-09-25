import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { cn } from '@/lib/utils';
import type { User } from '@/types';

function initials(name: string): string {
    const parts = name.trim().split(/\s+/u).filter(Boolean);

    if (parts.length === 0) {
        return '';
    }

    if (parts.length === 1) {
        return (Array.from(parts[0])[0] ?? '').toUpperCase();
    }

    const first = Array.from(parts[0])[0] ?? '';
    const last = Array.from(parts[parts.length - 1])[0] ?? '';

    return `${first}${last}`.toUpperCase();
}

export function UserInfo({
    user,
    showEmail = false,
    size = 'default',
}: {
    user: User;
    showEmail?: boolean;
    size?: 'default' | 'lg';
}) {
    return (
        <>
            <Avatar
                className={cn(
                    'overflow-hidden rounded-full',
                    size === 'lg' ? 'size-10' : 'size-8',
                )}
            >
                <AvatarImage src={user.avatar ?? undefined} alt={user.name} />
                <AvatarFallback className="rounded-full bg-primary text-sm text-primary-foreground">
                    {initials(user.name)}
                </AvatarFallback>
            </Avatar>
            <div className="grid min-w-0 flex-1 text-left text-sm leading-tight">
                <span className="truncate font-medium text-foreground">
                    {user.name}
                </span>
                {showEmail && (
                    <span className="truncate text-xs text-muted-foreground">
                        {user.email}
                    </span>
                )}
            </div>
        </>
    );
}
