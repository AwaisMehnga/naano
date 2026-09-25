export type LinkedInCompany = {
    name: string | null;
    url: string | null;
};

export type LinkedInEngagementPoint = {
    label: string;
    reactions: number;
    comments: number;
    posted_at: string | null;
};

export type LinkedInTopPost = {
    id: string | null;
    linkedin_url: string | null;
    content: string | null;
    posted_at: string | null;
    likes: number;
    comments: number;
    shares: number;
};

export type LinkedInBucket = {
    label: string;
    count: number;
};

export type LinkedInEngager = {
    name: string | null;
    headline: string | null;
    profile_url: string | null;
};

export type LinkedInPosition = {
    title: string | null;
    company: string | null;
    company_url?: string | null;
    location?: string | null;
    start: string | null;
    end: string | null;
    description: string | null;
};

export type LinkedInEducation = {
    school: string | null;
    degree: string | null;
    field: string | null;
    start: string | null;
    end: string | null;
};

export type LinkedInInsights = {
    verified: boolean;
    verified_at: string | null;
    synced_at: string | null;
    verify_code: string | null;
    linkedin_url: string | null;
    header: {
        name: string | null;
        headline: string | null;
        job_title?: string | null;
        location: string | null;
        company: LinkedInCompany | null;
        picture_url: string | null;
        captured_at: string | null;
    };
    stats: {
        followers: number | null;
        connections: number | null;
        posts_count: number;
        last_posted_at: string | null;
        avg_reactions: number | null;
        avg_comments: number | null;
        asking_rate_cents: number | null;
    };
    engagement_series: LinkedInEngagementPoint[];
    top_posts: LinkedInTopPost[];
    recent_posts?: LinkedInTopPost[];
    engagers: {
        people_count: number;
        reply_rate: number | null;
        seniority: LinkedInBucket[];
        locations: LinkedInBucket[];
        top: LinkedInEngager[];
    } | null;
    background: {
        positions: LinkedInPosition[];
        educations: LinkedInEducation[];
        skills: string[];
        summary: string | null;
    };
};
