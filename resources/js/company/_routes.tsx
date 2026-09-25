import DashboardLayout from '@/layouts/dashboard';
import { Navigate } from 'react-router';
import CompanyAnalyticsPage from './pages/analytics';
import CompanyBriefPage from './pages/brief';
import CompanyCampaignsPage from './pages/campaigns';
import CompanyCampaignAnalyticsPage from './pages/campaigns/analytics';
import CompanyContractPage from './pages/campaigns/contract';
import CompanyPostReviewPage from './pages/campaigns/post-review';
import CompanyCampaignShowPage from './pages/campaigns/show';
import CompanyCollaborationsPage from './pages/collaborations';
import CompanyCreatorsPage from './pages/creators';
import CompanySettingLayout from './pages/setting';
import CompanyAudiencePage from './pages/setting/audience';
import CompanyProfilePage from './pages/setting/profile';
import CompanyWalletPage from './pages/wallet';
import NotificationsPage from '@/pages/notifications';
import NotificationSettingsPage from '@/pages/setting/notifications';
import ProfilesPage from '@/pages/setting/profiles';

export const routes = [
    {
        path: '/',
        element: <DashboardLayout />,
        children: [
            {
                index: true,
                element: <CompanyAnalyticsPage />,
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
                path: 'campaigns/:id/analytics',
                element: <CompanyCampaignAnalyticsPage />,
            },
            {
                path: 'campaigns/:campaignId/posts/:postId',
                element: <CompanyPostReviewPage />,
            },
            {
                path: 'collaboration',
                element: <CompanyCollaborationsPage />,
            },
            {
                path: 'brief',
                element: <CompanyBriefPage />,
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
                path: 'notifications',
                element: <NotificationsPage />,
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
                        path: 'profiles',
                        element: <ProfilesPage />,
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
