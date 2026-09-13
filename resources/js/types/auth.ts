export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string | null;
    email_verified_at: string | null;
    role?: string | null;
    membership_role?: string | null;
    unread_notifications_count?: number;
};
