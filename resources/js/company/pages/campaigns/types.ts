export type CampaignStatus =
    | 'draft'
    | 'active'
    | 'paused'
    | 'completed'
    | 'cancelled';

export type CampaignType =
    | 'thought_leadership'
    | 'product'
    | 'hiring'
    | 'event'
    | 'other';

export type CampaignObjective =
    | 'awareness'
    | 'pipeline'
    | 'talent'
    | 'community';

export type PipelineTab =
    | 'all'
    | 'active'
    | 'invitations_received'
    | 'invitations_sent'
    | 'todo'
    | 'completed';

export type CampaignBrief = {
    context: string;
    product: string;
    differentiators: string[];
    target: string;
    pains: string[];
    trigger: string;
    key_message: string;
    audience: {
        industries: string;
        geographies: string;
        tone: string;
    };
    editorial: {
        do: string[];
        avoid: string[];
    };
    references: { quote: string; structure: string }[];
    angles: { title: string; hook: string; format: string; example: string }[];
};

export type CampaignListItem = {
    id: number;
    name: string;
    type: CampaignType;
    objective: CampaignObjective;
    status: CampaignStatus;
    budget_cents: number | null;
    start_at: string | null;
    end_at: string | null;
    collab_count: number;
};

export type CampaignList = {
    data: CampaignListItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

export type CollabCounts = {
    all: number;
    active: number;
    invitations_received: number;
    invitations_sent: number;
    todo: number;
    completed: number;
};

export type ShortlistedCreator = {
    id: number;
    collaboration_id: number;
    status: string;
    display_name: string | null;
    headline: string | null;
    photo_url: string | null;
    country: string | null;
    from_price_cents: number | null;
    followers_count: number | null;
};

export type CampaignPost = {
    id: number;
    collaboration_id: number;
    status: string;
    body: string | null;
    published_url: string | null;
    submitted_at: string | null;
    media?: {
        id: number;
        kind: 'image' | 'video';
        mime_type: string;
        size_bytes: number;
        original_name: string | null;
        url: string | null;
    }[];
    creator: {
        id: number;
        display_name: string | null;
        photo_url: string | null;
    };
};

export type CampaignDetail = CampaignListItem & {
    company_icp_id: number | null;
    company_icp: { id: number; title: string } | null;
    goal: string | null;
    key_messages: string[] | null;
    guidelines: string | null;
    brief: CampaignBrief | null;
    collab_counts: CollabCounts;
    shortlisted: ShortlistedCreator[];
    leads_count: number;
    posts: CampaignPost[];
};

export type CollaborationRow = {
    id: number;
    source: string;
    status: string;
    booked_price_cents: number | null;
    booked_posts_count: number | null;
    has_published_post: boolean;
    review_post_id: number | null;
    campaign?: { id: number; name: string };
    creator: {
        id: number;
        display_name: string | null;
        headline: string | null;
        photo_url: string | null;
        country: string | null;
        from_price_cents: number | null;
    };
};

export type CollaborationList = {
    data: CollaborationRow[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    counts: CollabCounts;
};

export type IcpOption = {
    id: number;
    title: string;
};

export const emptyBrief = (): CampaignBrief => ({
    context: '',
    product: '',
    differentiators: [''],
    target: '',
    pains: [''],
    trigger: '',
    key_message: '',
    audience: {
        industries: '',
        geographies: '',
        tone: '',
    },
    editorial: {
        do: [''],
        avoid: [''],
    },
    references: [{ quote: '', structure: '' }],
    angles: [{ title: '', hook: '', format: '', example: '' }],
});

export const typeLabels: Record<CampaignType, string> = {
    thought_leadership: 'Thought leadership',
    product: 'Product',
    hiring: 'Hiring',
    event: 'Event',
    other: 'Other',
};

export const objectiveLabels: Record<CampaignObjective, string> = {
    awareness: 'Awareness',
    pipeline: 'Pipeline',
    talent: 'Talent',
    community: 'Community',
};

export const statusLabels: Record<CampaignStatus, string> = {
    draft: 'Draft',
    active: 'Active',
    paused: 'Paused',
    completed: 'Completed',
    cancelled: 'Cancelled',
};

export const pipelineLabels: Record<PipelineTab, string> = {
    all: 'All',
    active: 'Active',
    invitations_received: 'Invitations received',
    invitations_sent: 'Invitations sent',
    todo: 'To do',
    completed: 'Completed',
};

export type StatusAction =
    | 'launch'
    | 'resume'
    | 'reopen'
    | 'pause'
    | 'complete'
    | 'cancel';

export function reachableStatuses(from: CampaignStatus): CampaignStatus[] {
    switch (from) {
        case 'draft':
            return ['draft', 'active', 'cancelled'];
        case 'active':
            return ['active', 'paused', 'completed', 'cancelled'];
        case 'paused':
            return ['paused', 'active', 'completed', 'cancelled'];
        case 'completed':
            return ['completed'];
        case 'cancelled':
            return ['cancelled', 'active'];
    }
}

export function actionForStatusChange(
    from: CampaignStatus,
    to: CampaignStatus,
): StatusAction | null {
    if (from === to) {
        return null;
    }

    if (to === 'active') {
        if (from === 'draft') {
            return 'launch';
        }

        if (from === 'paused') {
            return 'resume';
        }

        if (from === 'cancelled') {
            return 'reopen';
        }
    }

    if (to === 'paused' && from === 'active') {
        return 'pause';
    }

    if (to === 'completed' && (from === 'active' || from === 'paused')) {
        return 'complete';
    }

    if (
        to === 'cancelled' &&
        (from === 'draft' || from === 'active' || from === 'paused')
    ) {
        return 'cancel';
    }

    return null;
}
