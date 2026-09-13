import { Bell } from 'lucide-react';
import { useEffect, useState } from 'react';
import { AppLink } from '@/components/app-link';
import { Button } from '@/components/ui/button';
import { http, sharedApi } from '@/lib/api';
import { setUnreadNotificationsCount } from '@/lib/current-user';

type Inbox = {
    unread_count: number;
};

export function NotificationBell() {
    const [count, setCount] = useState(
        window.Naano?.user?.unread_notifications_count ?? 0,
    );

    useEffect(() => {
        function sync(): void {
            setCount(window.Naano?.user?.unread_notifications_count ?? 0);
        }

        window.addEventListener('naano:user', sync);

        return () => window.removeEventListener('naano:user', sync);
    }, []);

    useEffect(() => {
        function refresh(): void {
            http.get<Inbox>(sharedApi.notifications)
                .then(({ data }) => {
                    setCount(data.unread_count);
                    setUnreadNotificationsCount(data.unread_count);
                })
                .catch(() => undefined);
        }

        refresh();
        const timer = window.setInterval(refresh, 30000);

        return () => window.clearInterval(timer);
    }, []);

    return (
        <Button type="button" variant="ghost" size="icon" className="relative" asChild>
            <AppLink href="/notifications" aria-label="Notifications">
                <Bell className="size-4" />
                {count > 0 && (
                    <span className="bg-primary text-primary-foreground absolute -top-0.5 -right-0.5 flex min-w-4 items-center justify-center rounded-full px-1 text-[10px] leading-4">
                        {count > 99 ? '99+' : count}
                    </span>
                )}
            </AppLink>
        </Button>
    );
}
