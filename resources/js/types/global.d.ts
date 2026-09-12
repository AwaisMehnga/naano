import type { User } from '@/types/auth';

declare global {
    interface Window {
        Naano?: {
            name: string;
            user: User;
        };
    }
}

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}
