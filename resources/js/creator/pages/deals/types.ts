export type DealCompany = {
    id: number;
    name: string | null;
    logo_url: string | null;
};

export type Deal = {
    id: number;
    source: string;
    status: string;
    booked_price_cents: number | null;
    booked_posts_count: number | null;
    accepted_at: string | null;
    booked_at: string | null;
    campaign: {
        id: number;
        name: string;
        type: string;
        objective: string;
    };
    company: DealCompany;
};
