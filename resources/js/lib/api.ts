import axios, { AxiosError, type AxiosRequestConfig } from 'axios';

export type AjaxPayload<T> = {
    status: 'success' | 'error' | 'failure';
    message: string;
    data: T;
};

export class ApiError extends Error {
    constructor(
        public httpStatus: number,
        public payload: AjaxPayload<unknown>,
    ) {
        super(payload.message);
    }
}

function csrfToken(): string {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? ''
    );
}

export const http = axios.create({
    withCredentials: true,
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

http.interceptors.request.use((config) => {
    config.headers.set('X-CSRF-TOKEN', csrfToken());

    return config;
});

http.interceptors.response.use(
    (response) => {
        const payload = response.data as AjaxPayload<unknown>;

        if (payload.status !== 'success') {
            throw new ApiError(response.status, payload);
        }

        response.data = payload.data;

        return response;
    },
    (error: AxiosError<AjaxPayload<unknown>>) => {
        if (error.response?.data) {
            throw new ApiError(error.response.status, error.response.data);
        }

        throw error;
    },
);

export async function api<T>(url: string, init: RequestInit = {}): Promise<T> {
    const method = (init.method ?? 'GET').toLowerCase();
    const config: AxiosRequestConfig = { url, method };

    if (init.body instanceof FormData) {
        config.data = init.body;
    } else if (typeof init.body === 'string' && init.body !== '') {
        config.data = JSON.parse(init.body) as unknown;
    }

    const response = await http.request<T>(config);

    return response.data;
}

function withQuery(
    path: string,
    query: Record<string, string | number | undefined> = {},
): string {
    const params = new URLSearchParams();

    for (const [key, value] of Object.entries(query)) {
        if (value === undefined || value === '') {
            continue;
        }

        params.set(key, String(value));
    }

    const encoded = params.toString();

    return encoded === '' ? path : `${path}?${encoded}`;
}

export const companyApi = {
    profile: '/api/company/profile',
    audience: '/api/company/audience',
    icps: '/api/company/icps',
    icp: (id: number) => `/api/company/icps/${id}`,
    members: '/api/company/members',
    member: (id: number) => `/api/company/members/${id}`,
    creators: (query: Record<string, string | number | undefined> = {}) =>
        withQuery('/api/company/creators', query),
    creator: (id: number) => `/api/company/creators/${id}`,
    campaigns: (query: Record<string, string | number | undefined> = {}) =>
        withQuery('/api/company/campaigns', query),
    campaign: (id: number) => `/api/company/campaigns/${id}`,
    campaignLaunch: (id: number) => `/api/company/campaigns/${id}/launch`,
    campaignPause: (id: number) => `/api/company/campaigns/${id}/pause`,
    campaignResume: (id: number) => `/api/company/campaigns/${id}/resume`,
    campaignReopen: (id: number) => `/api/company/campaigns/${id}/reopen`,
    campaignComplete: (id: number) => `/api/company/campaigns/${id}/complete`,
    campaignCancel: (id: number) => `/api/company/campaigns/${id}/cancel`,
    campaignBriefChat: (id: number) =>
        `/api/company/campaigns/${id}/brief/chat`,
    campaignCollaborations: (
        id: number,
        query: Record<string, string | number | undefined> = {},
    ) => withQuery(`/api/company/campaigns/${id}/collaborations`, query),
    campaignInvites: (id: number) => `/api/company/campaigns/${id}/invites`,
    campaignSourcing: (id: number) => `/api/company/campaigns/${id}/sourcing`,
    campaignBook: (id: number) => `/api/company/campaigns/${id}/book`,
    collaborations: (query: Record<string, string | number | undefined> = {}) =>
        withQuery('/api/company/collaborations', query),
    collaboration: (id: number) => `/api/company/collaborations/${id}`,
    collaborationSelect: (id: number) =>
        `/api/company/collaborations/${id}/select`,
    collaborationBook: (id: number) => `/api/company/collaborations/${id}/book`,
    collaborationCancel: (id: number) =>
        `/api/company/collaborations/${id}/cancel`,
    collaborationFollowUps: (id: number) =>
        `/api/company/collaborations/${id}/follow-ups`,
    collaborationEvents: (id: number) =>
        `/api/company/collaborations/${id}/events`,
    collaborationContract: (id: number) =>
        `/api/company/collaborations/${id}/contract`,
    collaborationPosts: (id: number) =>
        `/api/company/collaborations/${id}/posts`,
    collaborationMessages: (id: number) =>
        `/api/company/collaborations/${id}/messages`,
    collaborationMessagesRead: (id: number) =>
        `/api/company/collaborations/${id}/messages/read`,
    post: (id: number) => `/api/company/posts/${id}`,
    postApprove: (id: number) => `/api/company/posts/${id}/approve`,
    postChanges: (id: number) => `/api/company/posts/${id}/changes`,
    postReject: (id: number) => `/api/company/posts/${id}/reject`,
    campaignTrackingLinks: (id: number) =>
        `/api/company/campaigns/${id}/tracking-links`,
    trackingLink: (id: number) => `/api/company/tracking-links/${id}`,
    analyticsOverview: (
        query: Record<string, string | number | undefined> = {},
    ) => withQuery('/api/company/analytics/overview', query),
    campaignAnalytics: (id: number) =>
        `/api/company/campaigns/${id}/analytics`,
    campaignAnalyticsCreators: (id: number) =>
        `/api/company/campaigns/${id}/analytics/creators`,
    campaignLeads: (id: number) => `/api/company/campaigns/${id}/leads`,
    lead: (id: number) => `/api/company/leads/${id}`,
    postMetrics: (id: number) => `/api/company/posts/${id}/metrics`,
    campaignReport: (id: number) => `/api/company/reports/campaigns/${id}`,
    wallet: '/api/company/wallet',
    walletTransactions: (
        query: Record<string, string | number | undefined> = {},
    ) => withQuery('/api/company/wallet/transactions', query),
    walletTopups: '/api/company/wallet/topups',
    walletTopup: (id: number) => `/api/company/wallet/topups/${id}`,
};

export const creatorApi = {
    profile: '/api/creator/profile',
    linkedinStart: '/api/creator/linkedin/start',
    linkedinVerify: '/api/creator/linkedin/verify',
    linkedinRefresh: '/api/creator/linkedin/refresh',
    linkedinPostsSync: '/api/creator/linkedin/posts/sync',
    linkedinProfile: '/api/creator/linkedin/profile',
    niches: '/api/creator/niches',
    audience: '/api/creator/audience',
    billing: '/api/creator/billing',
    account: '/api/creator/account',
    opportunities: (query: Record<string, string | number | undefined> = {}) =>
        withQuery('/api/creator/opportunities', query),
    opportunity: (id: number) => `/api/creator/opportunities/${id}`,
    opportunityApply: (id: number) => `/api/creator/opportunities/${id}/apply`,
    collaborations: (query: Record<string, string | number | undefined> = {}) =>
        withQuery('/api/creator/collaborations', query),
    collaboration: (id: number) => `/api/creator/collaborations/${id}`,
    collaborationAccept: (id: number) =>
        `/api/creator/collaborations/${id}/accept`,
    collaborationDecline: (id: number) =>
        `/api/creator/collaborations/${id}/decline`,
    collaborationContract: (id: number) =>
        `/api/creator/collaborations/${id}/contract`,
    collaborationPosts: (id: number) =>
        `/api/creator/collaborations/${id}/posts`,
    collaborationMessages: (id: number) =>
        `/api/creator/collaborations/${id}/messages`,
    collaborationMessagesRead: (id: number) =>
        `/api/creator/collaborations/${id}/messages/read`,
    post: (id: number) => `/api/creator/posts/${id}`,
    postSubmit: (id: number) => `/api/creator/posts/${id}/submit`,
    postSchedule: (id: number) => `/api/creator/posts/${id}/schedule`,
    postPublish: (id: number) => `/api/creator/posts/${id}/publish`,
    media: '/api/creator/media',
    mediaDestroy: (id: number) => `/api/creator/media/${id}`,
    analyticsOverview: (
        query: Record<string, string | number | undefined> = {},
    ) => withQuery('/api/creator/analytics/overview', query),
    collaborationMetrics: (id: number) =>
        `/api/creator/collaborations/${id}/metrics`,
    postMetrics: (id: number) => `/api/creator/posts/${id}/metrics`,
    wallet: '/api/creator/wallet',
    walletWithdrawals: '/api/creator/wallet/withdrawals',
    connect: '/api/creator/connect',
    connectOnboarding: '/api/creator/connect/onboarding',
    connectDashboard: '/api/creator/connect/dashboard',
};

export const sharedApi = {
    niches: '/api/niches',
    profiles: '/api/profiles',
    profilesCreator: '/api/profiles/creator',
    profilesCompany: '/api/profiles/company',
    profilesActive: '/api/profiles/active',
    notifications: '/api/notifications',
    notificationsReadAll: '/api/notifications/read',
    notificationRead: (id: string) => `/api/notifications/${id}/read`,
    notificationPreferences: '/api/notification-preferences',
};
