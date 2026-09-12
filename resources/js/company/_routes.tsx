import DashboardLayout from '@/layouts/dashboard';
import SpaHome from '@/pages/spa-home';
import { Navigate } from 'react-router';
import CompanySettingLayout from './pages/setting';
import CompanyAudiencePage from './pages/setting/audience';
import CompanyProfilePage from './pages/setting/profile';
import CompanyTeamAccessPage from './pages/setting/team-access';

export const routes = [
    {
        path: '/',
        element: <DashboardLayout />,
        children: [
            {
                index: true,
                element: <SpaHome title="Company" />,
            },
            {
                path: 'setting',
                element: <CompanySettingLayout />,
                children: [
                    {
                        index: true,
                        element: <Navigate to="profile" replace />,
                    },
                    {
                        path: 'profile',
                        element: <CompanyProfilePage />,
                    },
                    {
                        path: 'audience',
                        element: <CompanyAudiencePage />,
                    },
                    {
                        path: 'team-access',
                        element: <CompanyTeamAccessPage />,
                    },
                ],
            },
        ],
    },
];
