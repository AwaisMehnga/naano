export type OpportunityCompany = {
    id: number;
    name: string | null;
    logo_url: string | null;
};

export type Opportunity = {
    id: number;
    name: string;
    type: string;
    objective: string;
    status: string;
    start_at: string | null;
    end_at: string | null;
    deadline: string | null;
    match_score: number;
    audience_relevance: number | null;
    reasons: string[];
    location: {
        country: string | null;
        regions: string[];
    };
    company: OpportunityCompany;
};

export type OpportunityDetail = Opportunity & {
    brief: { context?: string } | null;
    goal: string | null;
    key_messages: string[] | null;
    guidelines: string | null;
};
