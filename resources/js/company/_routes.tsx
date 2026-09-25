import CompanyShellLayout from '@/layouts/company-shell';
import { Navigate, useParams, useSearchParams } from 'react-router';
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

function BriefRedirect() {
    const [params] = useSearchParams();
    const campaignId = params.get('campaign');

    if (campaignId) {
        return <Navigate to={`/campaigns/${campaignId}/brief`} replace />;
    }

    return <Navigate to="/campaigns" replace />;
}

function CampaignBriefRoute() {
    const { id } = useParams();

    return <CompanyBriefPage campaignId={Number(id)} />;
}

export const routes = [
    {
        path: '/',
        element: <CompanyShellLayout />,
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
                path: 'campaigns/:id/brief',
                element: <CampaignBriefRoute />,
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
                element: <BriefRedirect />,
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
