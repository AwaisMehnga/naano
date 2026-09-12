import type { ReactNode } from 'react';
import { SidebarProvider } from '@/components/ui/sidebar';
import type { AppVariant } from '@/types';

type Props = {
    children: ReactNode;
    variant?: AppVariant;
};

function sidebarOpenFromCookie(): boolean {
    if (typeof document === 'undefined') {
        return true;
    }

    const match = document.cookie.match(/(?:^|; )sidebar_state=([^;]*)/);

    return match ? match[1] === 'true' : true;
}

export function AppShell({ children, variant = 'sidebar' }: Props) {
    const isOpen = sidebarOpenFromCookie();

    if (variant === 'header') {
        return (
            <div className="flex min-h-screen w-full flex-col">{children}</div>
        );
    }

    return <SidebarProvider defaultOpen={isOpen}>{children}</SidebarProvider>;
}
