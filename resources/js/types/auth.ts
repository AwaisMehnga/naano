export type ProfileSummary = {
    type: string;
    onboarded: boolean;
    label: string;
};

export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string | null;
    email_verified_at: string | null;
    role?: string | null;
    active_profile?: string | null;
    profiles?: ProfileSummary[];
    can_create_profiles?: string[];
    onboarded?: boolean;
    unread_notifications_count?: number;
};
