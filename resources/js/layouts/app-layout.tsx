import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';

export default function AppLayout({
    breadcrumbs = [],
    children,
    homeHref,
}: {
    breadcrumbs?: BreadcrumbItem[];
    children: React.ReactNode;
    homeHref?: string;
}) {
    return (
        <AppLayoutTemplate breadcrumbs={breadcrumbs} homeHref={homeHref}>
            {children}
        </AppLayoutTemplate>
    );
}
