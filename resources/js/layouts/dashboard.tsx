import { Outlet } from 'react-router';
import { useDashboardBreadcrumbs } from '@/hooks/use-dashboard-breadcrumbs';
import AppLayout from '@/layouts/app-layout';

export default function DashboardLayout() {
    const breadcrumbs = useDashboardBreadcrumbs();

    return (
        <AppLayout homeHref="/" breadcrumbs={breadcrumbs}>
            <div className="flex w-full min-w-0 flex-1 flex-col p-4 lg:p-6">
                <Outlet />
            </div>
        </AppLayout>
    );
}
