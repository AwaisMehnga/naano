import { Outlet, useLocation } from 'react-router';
import { SettingsNav } from '@/components/settings-nav';

const items = [
    { title: 'Profile', href: '/setting/profile' },
    { title: 'Audience', href: '/setting/audience' },
    { title: 'Billing', href: '/setting/billing' },
    { title: 'Account', href: '/setting/account' },
    { title: 'Notifications', href: '/setting/notifications' },
];

export default function CreatorSettingLayout() {
    const { pathname } = useLocation();

    return (
        <div className="flex flex-1 flex-col gap-8 lg:flex-row">
            <aside className="w-full shrink-0 lg:w-56">
                <p className="mb-3 px-3 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                    Settings
                </p>
                <SettingsNav items={items} pathname={pathname} />
            </aside>
            <div className="min-w-0 flex-1">
                <Outlet />
            </div>
        </div>
    );
}
