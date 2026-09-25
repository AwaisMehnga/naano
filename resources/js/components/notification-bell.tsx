import { Bell } from 'lucide-react';
import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router';
import { AppLink } from '@/components/app-link';
import { IconButton } from '@/components/ds';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { http, sharedApi } from '@/lib/api';
import { setUnreadNotificationsCount } from '@/lib/current-user';
import { cn } from '@/lib/utils';

type NotificationRow = {
    id: string;
    type: string;
    data: {
        title?: string;
        body?: string;
        href?: string;
    };
    read_at: string | null;
};

type Inbox = {
    notifications: NotificationRow[];
    unread_count: number;
};

export function NotificationBell() {
    const navigate = useNavigate();
    const [count, setCount] = useState(
        window.Naano?.user?.unread_notifications_count ?? 0,
    );
    const [rows, setRows] = useState<NotificationRow[]>([]);

    useEffect(() => {
        function sync(): void {
            setCount(window.Naano?.user?.unread_notifications_count ?? 0);
        }

        window.addEventListener('naano:user', sync);

        return () => window.removeEventListener('naano:user', sync);
    }, []);

    function refresh(): void {
        http.get<Inbox>(sharedApi.notifications)
            .then(({ data }) => {
                setRows(data.notifications);
                setCount(data.unread_count);
                setUnreadNotificationsCount(data.unread_count);
            })
            .catch(() => undefined);
    }

    async function open(row: NotificationRow): Promise<void> {
        if (row.read_at === null) {
            try {
                await http.post(sharedApi.notificationRead(row.id));
                setRows((current) =>
                    current.map((item) =>
                        item.id === row.id
                            ? { ...item, read_at: new Date().toISOString() }
                            : item,
                    ),
                );
                setCount((current) => {
                    const next = Math.max(0, current - 1);
                    setUnreadNotificationsCount(next);

                    return next;
                });
            } catch {
                // Still navigate even if mark-read fails.
            }
        }

        if (row.data.href) {
            void navigate(row.data.href);
        }
    }

    const latest = rows.slice(0, 8);

    return (
        <DropdownMenu onOpenChange={(open) => open && refresh()}>
            <DropdownMenuTrigger asChild>
                <IconButton
                    variant="outline"
                    size="default"
                    className="relative"
                    aria-label="Notifications"
                >
                    <Bell />
                    {count > 0 && (
                        <span className="absolute -top-0.5 -right-0.5 flex min-w-4 items-center justify-center rounded-full bg-accent px-1 text-[10px] leading-4 text-accent-foreground">
                            {count > 99 ? '99+' : count}
                        </span>
                    )}
                </IconButton>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-80">
                {latest.length === 0 ? (
                    <p className="text-muted-foreground px-2 py-6 text-center text-sm">
                        No notifications yet.
                    </p>
                ) : (
                    latest.map((row) => (
                        <DropdownMenuItem
                            key={row.id}
                            className="flex-col items-start gap-0.5"
                            onSelect={() => void open(row)}
                        >
                            <span
                                className={cn(
                                    'font-medium',
                                    row.read_at !== null &&
                                        'text-muted-foreground font-normal',
                                )}
                            >
                                {row.data.title ?? row.type}
                            </span>
                            {row.data.body && (
                                <span className="text-muted-foreground line-clamp-2 text-xs">
                                    {row.data.body}
                                </span>
                            )}
                        </DropdownMenuItem>
                    ))
                )}
                <DropdownMenuSeparator />
                <DropdownMenuItem asChild>
                    <AppLink href="/notifications">See all</AppLink>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
