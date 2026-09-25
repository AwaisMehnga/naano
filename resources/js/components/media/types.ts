export type MediaItem = {
    id: number;
    kind: 'image' | 'video';
    mime_type: string;
    size_bytes: number;
    original_name: string | null;
    url: string | null;
};

export type PostReviewItem = {
    id: number;
    action: string;
    note: string | null;
    actor_name: string;
    created_at: string | null;
};
