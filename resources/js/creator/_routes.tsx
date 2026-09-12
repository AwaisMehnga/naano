import DashboardLayout from '@/layouts/dashboard';
import SpaHome from '@/pages/spa-home';
import { Navigate } from 'react-router';
import CreatorDealsPage from './pages/deals';
import CreatorContractPage from './pages/deals/contract';
import CreatorOpportunitiesPage from './pages/opportunities';
import CreatorOpportunityShowPage from './pages/opportunities/show';
import CreatorSettingLayout from './pages/setting';
import CreatorAccountPage from './pages/setting/account';
import CreatorAudiencePage from './pages/setting/audience';
import CreatorBillingPage from './pages/setting/billing';
import CreatorProfilePage from './pages/setting/profile';

export const routes = [
    {
        path: '/',
        element: <DashboardLayout />,
        children: [
            {
                index: true,
                element: <SpaHome title="Creator" />,
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
                path: 'collaborations/:id/contract',
                element: <CreatorContractPage />,
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
                ],
            },
        ],
    },
];
