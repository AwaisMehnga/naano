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

export async function api<T>(url: string, init: RequestInit = {}): Promise<T> {
    const headers = new Headers(init.headers);
    headers.set('Accept', 'application/json');
    headers.set('X-CSRF-TOKEN', csrfToken());
    headers.set('X-Requested-With', 'XMLHttpRequest');

    if (init.body && !(init.body instanceof FormData) && !headers.has('Content-Type')) {
        headers.set('Content-Type', 'application/json');
    }

    const response = await fetch(url, {
        ...init,
        headers,
        credentials: 'same-origin',
    });

    const payload = (await response.json()) as AjaxPayload<T>;

    if (!response.ok || payload.status !== 'success') {
        throw new ApiError(response.status, payload);
    }

    return payload.data;
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
