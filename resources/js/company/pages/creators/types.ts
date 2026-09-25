import type { LinkedInInsights } from '@/components/linkedin/types';

export type Niche = {
    id: number;
    name: string;
    slug: string;
};

export type CreatorListItem = {
    id: number;
    display_name: string | null;
    headline: string | null;
    photo_url: string | null;
    country: string | null;
    niches: Niche[];
    followers_count: number | null;
    connections_count?: number | null;
    from_price_cents: number | null;
    linkedin_url?: string | null;
};

export type CreatorList = {
    items: CreatorListItem[];
    current_page: number;
    last_page: number;
    total: number;
};

export type Offer = {
    id: number;
    label: string;
    posts_count: number;
    price_cents: number;
};

export type CreatorProfileCard = CreatorListItem & {
    bio: string | null;
    linkedin_url: string | null;
    audience_mix: Record<string, unknown> | unknown[];
    captured_at: string | null;
    offers: Offer[];
    recent_metrics: unknown[];
    linkedin_insights?: LinkedInInsights | null;
};
