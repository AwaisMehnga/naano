import { useLocation } from 'react-router';
import type { BreadcrumbItem } from '@/types';

const titles: Record<string, string> = {
    campaigns: 'Campaigns',
    wallet: 'Wallet',
    creators: 'Creators',
    notifications: 'Notifications',
    setting: 'Settings',
    profile: 'Profile',
    audience: 'Audience',
    'team-access': 'Team access',
    opportunities: 'Opportunities',
    deals: 'Deals',
    metrics: 'Metrics',
    billing: 'Billing',
    account: 'Account',
    posts: 'Post',
    collaborations: 'Contract',
    collaboration: 'Collaborations',
    brief: 'Brief',
    analytics: 'Analytics',
};

function crumbTitle(part: string, previous?: string): string {
    if (/^\d+$/.test(part)) {
        return (
            {
                campaigns: 'Campaign',
                creators: 'Creator',
                opportunities: 'Opportunity',
                deals: 'Deal',
                posts: 'Post',
                collaborations: 'Contract',
            }[previous ?? ''] ?? 'Details'
        );
    }

    return titles[part] ?? part.replaceAll('-', ' ');
}

export function useDashboardBreadcrumbs(): BreadcrumbItem[] {
    const { pathname } = useLocation();
    const parts = pathname.split('/').filter(Boolean);
    const crumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/' }];

    let href = '';

    parts.forEach((part, index) => {
        href += `/${part}`;
        crumbs.push({
            title: crumbTitle(part, parts[index - 1]),
            href,
        });
    });

    return crumbs;
}
