import { Outlet } from 'react-router';
import AppLayout from '@/layouts/app-layout';

export default function DashboardLayout() {
    return (
        <AppLayout homeHref="/">
            <Outlet />
        </AppLayout>
    );
}
