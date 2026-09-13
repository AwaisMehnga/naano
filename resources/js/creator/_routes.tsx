import DashboardLayout from '@/layouts/dashboard';
import { Navigate } from 'react-router';
import CreatorDashboardPage from './pages/dashboard';
import CreatorDealsPage from './pages/deals';
import CreatorContractPage from './pages/deals/contract';
import CreatorDealShowPage from './pages/deals/show';
import CreatorMetricsPage from './pages/metrics';
import CreatorOpportunitiesPage from './pages/opportunities';
import CreatorOpportunityShowPage from './pages/opportunities/show';
import CreatorSettingLayout from './pages/setting';
import CreatorAccountPage from './pages/setting/account';
import CreatorAudiencePage from './pages/setting/audience';
import CreatorBillingPage from './pages/setting/billing';
import CreatorProfilePage from './pages/setting/profile';
import NotificationsPage from '@/pages/notifications';
import NotificationSettingsPage from '@/pages/setting/notifications';

export const routes = [
    {
        path: '/',
        element: <DashboardLayout />,
        children: [
            {
                index: true,
                element: <CreatorDashboardPage />,
            },
            {
                path: 'opportunities',
                element: <CreatorOpportunitiesPage />,
            },
            {
                path: 'opportunities/:id',
                element: <CreatorOpportunityShowPage />,
            },
            {
                path: 'deals',
                element: <CreatorDealsPage />,
            },
            {
                path: 'deals/:id',
                element: <CreatorDealShowPage />,
            },
            {
                path: 'metrics',
                element: <CreatorMetricsPage />,
            },
            {
                path: 'collaborations/:id/contract',
                element: <CreatorContractPage />,
            },
            {
                path: 'notifications',
                element: <NotificationsPage />,
            },
            {
                path: 'setting',
                element: <CreatorSettingLayout />,
                children: [
                    {
                        index: true,
                        element: <Navigate to="profile" replace />,
                    },
                    {
                        path: 'profile',
                        element: <CreatorProfilePage />,
                    },
                    {
                        path: 'audience',
                        element: <CreatorAudiencePage />,
                    },
                    {
                        path: 'billing',
                        element: <CreatorBillingPage />,
                    },
                    {
                        path: 'account',
                        element: <CreatorAccountPage />,
                    },
                    {
                        path: 'notifications',
                        element: <NotificationSettingsPage />,
                    },
                ],
            },
        ],
    },
];
