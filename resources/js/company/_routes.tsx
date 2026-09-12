import DashboardLayout from '@/layouts/dashboard';
import SpaHome from '@/pages/spa-home';
import { Navigate } from 'react-router';
import CompanyCampaignsPage from './pages/campaigns';
import CompanyContractPage from './pages/campaigns/contract';
import CompanyCampaignShowPage from './pages/campaigns/show';
import CompanyCreatorsPage from './pages/creators';
import CompanySettingLayout from './pages/setting';
import CompanyAudiencePage from './pages/setting/audience';
import CompanyProfilePage from './pages/setting/profile';
import CompanyTeamAccessPage from './pages/setting/team-access';
import CompanyWalletPage from './pages/wallet';

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
                path: 'campaigns',
                element: <CompanyCampaignsPage />,
            },
            {
                path: 'campaigns/:id',
                element: <CompanyCampaignShowPage />,
            },
            {
                path: 'wallet',
                element: <CompanyWalletPage />,
            },
            {
                path: 'collaborations/:id/contract',
                element: <CompanyContractPage />,
            },
            {
                path: 'creators',
                element: <CompanyCreatorsPage />,
            },
            {
                path: 'creators/:id',
                element: <CompanyCreatorsPage />,
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
