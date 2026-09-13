import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { ApiError, http, sharedApi } from '@/lib/api';
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
    created_at: string | null;
};

type Inbox = {
    notifications: NotificationRow[];
    unread_count: number;
};

export default function NotificationsPage() {
    const navigate = useNavigate();
    const [inbox, setInbox] = useState<Inbox | null>(null);

    async function load() {
        const { data } = await http.get<Inbox>(sharedApi.notifications);
        setInbox(data);
        setUnreadNotificationsCount(data.unread_count);
    }

    useEffect(() => {
        load().catch((caught: unknown) => {
            toast.error(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not load notifications.',
            );
        });
    }, []);

    async function markAll() {
        try {
            const { data } = await http.post<Inbox>(sharedApi.notificationsReadAll);
            setInbox(data);
            setUnreadNotificationsCount(data.unread_count);
        } catch (caught) {
            toast.error(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not mark notifications as read.',
            );
        }
    }

    async function open(row: NotificationRow) {
        if (row.read_at === null) {
            try {
                await http.post(sharedApi.notificationRead(row.id));
            } catch {
                // Still navigate even if mark-read fails.
            }
        }

        if (row.data.href) {
            void navigate(row.data.href);
            return;
        }

        await load();
    }

    return (
        <div className="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 p-4 lg:p-6">
            <div className="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Notifications
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Invites, applications, campaign updates, and messages.
                    </p>
                </div>
                <Button
                    type="button"
                    variant="outline"
                    disabled={(inbox?.unread_count ?? 0) === 0}
                    onClick={() => void markAll()}
                >
                    Mark all read
                </Button>
            </div>
            {inbox && inbox.notifications.length === 0 ? (
                <p className="text-muted-foreground text-sm">
                    No notifications yet.
                </p>
            ) : (
                <div className="grid gap-2">
                    {inbox?.notifications.map((row) => (
                        <button
                            key={row.id}
                            type="button"
                            className={cn(
                                'border-border rounded-xl border px-4 py-3 text-left',
                                row.read_at === null
                                    ? 'bg-card'
                                    : 'bg-muted/40',
                            )}
                            onClick={() => void open(row)}
                        >
                            <p className="font-medium">
                                {row.data.title ?? row.type}
                            </p>
                            {row.data.body && (
                                <p className="text-muted-foreground mt-1 text-sm">
                                    {row.data.body}
                                </p>
                            )}
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}
