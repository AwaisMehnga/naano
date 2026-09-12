import DashboardLayout from '@/layouts/dashboard';
import SpaHome from '@/pages/spa-home';

export const routes = [
    {
        path: '/',
        element: <DashboardLayout />,
        children: [
            {
                index: true,
                element: <SpaHome title="Company" />,
            },
        ],
    },
];
