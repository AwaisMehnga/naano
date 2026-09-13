export function setCurrentUserAvatar(avatar: string | null): void {
    if (!window.Naano?.user) {
        return;
    }

    window.Naano.user = {
        ...window.Naano.user,
        avatar: avatar ?? undefined,
    };

    window.dispatchEvent(new Event('naano:user'));
}

export function setUnreadNotificationsCount(count: number): void {
    if (!window.Naano?.user) {
        return;
    }

    window.Naano.user = {
        ...window.Naano.user,
        unread_notifications_count: count,
    };

    window.dispatchEvent(new Event('naano:user'));
}
