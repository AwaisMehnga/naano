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
