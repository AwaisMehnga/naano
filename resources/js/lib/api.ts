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
    workspaces: '/api/company/workspaces',
    workspace: (id: number) => `/api/company/workspaces/${id}`,
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
    campaignCollaborations: (
        id: number,
        query: Record<string, string | number | undefined> = {},
    ) => withQuery(`/api/company/campaigns/${id}/collaborations`, query),
    campaignInvites: (id: number) => `/api/company/campaigns/${id}/invites`,
    collaborationSelect: (id: number) =>
        `/api/company/collaborations/${id}/select`,
    collaborationCancel: (id: number) =>
        `/api/company/collaborations/${id}/cancel`,
};

export const creatorApi = {
    profile: '/api/creator/profile',
    niches: '/api/creator/niches',
    audience: '/api/creator/audience',
    billing: '/api/creator/billing',
    account: '/api/creator/account',
};

export const sharedApi = {
    niches: '/api/niches',
};
