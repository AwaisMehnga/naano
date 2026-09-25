import { useEffect, useState } from 'react';
import { useLocation, useNavigate } from 'react-router';
import { AppLink } from '@/components/app-link';
import AppLogoIcon from '@/components/app-logo-icon';
import { NotificationBell } from '@/components/notification-bell';
import { UserMenuContent } from '@/components/user-menu-content';
import { AvatarGroup, IconButton, SegmentedNav, StatusPill } from '@/components/ds';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { User } from '@/types';

const topNav = [
    {
        title: 'Dashboard',
        href: '/',
        match: (pathname: string) => pathname === '/',
    },
    {
        title: 'Opportunities',
        href: '/opportunities',
        match: (pathname: string) => pathname.startsWith('/opportunities'),
    },
    {
        title: 'Deals',
        href: '/deals',
        match: (pathname: string) =>
            pathname.startsWith('/deals') ||
            pathname.startsWith('/collaborations'),
    },
    {
        title: 'Metrics',
        href: '/metrics',
        match: (pathname: string) => pathname.startsWith('/metrics'),
    },
] as const;

function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}

export function CreatorTopBar() {
    const { pathname } = useLocation();
    const navigate = useNavigate();
    const [user, setUser] = useState<User | undefined>(window.Naano?.user);

    useEffect(() => {
        function sync(): void {
            setUser(window.Naano?.user);
        }

        window.addEventListener('naano:user', sync);

        return () => window.removeEventListener('naano:user', sync);
    }, []);

    const profiles = user?.profiles ?? [];
    const activeNav =
        topNav.find((item) => item.match(pathname))?.href ?? '/';

    return (
        <header className="flex items-center justify-between gap-6 px-6 py-5 lg:px-8">
            <div className="flex min-w-0 items-center gap-6 lg:gap-10">
                <IconButton variant="default" size="default" asChild>
                    <AppLink href="/" aria-label="Naano home">
                        <AppLogoIcon className="size-5 fill-current" />
                    </AppLink>
                </IconButton>

                <SegmentedNav
                    className="hidden overflow-x-auto sm:inline-flex"
                    items={topNav.map((item) => ({
                        id: item.href,
                        label: item.title,
                    }))}
                    value={activeNav}
                    onChange={(id) => void navigate(id)}
                />
            </div>

            <div className="flex shrink-0 items-center gap-3">
                {profiles.length > 1 ? (
                    <AppLink
                        href="/setting/profiles"
                        className="hidden items-center gap-2 rounded-pill border border-border bg-card py-1 pr-3 pl-1 text-sm font-medium sm:inline-flex"
                    >
                        <AvatarGroup
                            items={profiles.map((profile) => ({
                                fallback: profile.label.slice(0, 2),
                                alt: profile.label,
                            }))}
                            max={3}
                            size="sm"
                        />
                        <span>Profiles</span>
                    </AppLink>
                ) : null}

                <NotificationBell />

                {user ? (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <StatusPill
                                label={user.name.split(/\s+/)[0] ?? user.name}
                                avatarSrc={user.avatar}
                                avatarFallback={initials(user.name)}
                                data-test="creator-user-menu"
                            />
                        </DropdownMenuTrigger>
                        <DropdownMenuContent
                            className="min-w-56 rounded-3xl"
                            align="end"
                        >
                            <UserMenuContent user={user} />
                        </DropdownMenuContent>
                    </DropdownMenu>
                ) : null}
            </div>
        </header>
    );
}
