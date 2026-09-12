export type ContractTerms = {
    brand: { name: string | null; country: string | null };
    creator: { display_name: string | null; country: string | null };
    campaign: { id: number; name: string };
    offer: {
        posts_count: number | null;
        price_cents: number | null;
        currency: string;
    };
    clauses: string[];
    version: number;
};

export type ContractDocument = {
    id: number;
    status: string;
    generated_at: string | null;
    pdf_path: string | null;
    terms: ContractTerms | null;
};
