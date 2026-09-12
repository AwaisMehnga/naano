import type { CreatorListItem } from '@/company/pages/creators/types';

function storageKey(): string {
    return `naano:creator-shortlist:${window.Naano?.user?.id ?? 'guest'}`;
}

export function readShortlist(): CreatorListItem[] {
    try {
        const raw = localStorage.getItem(storageKey());

        if (!raw) {
            return [];
        }

        const parsed = JSON.parse(raw) as CreatorListItem[];

        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

export function writeShortlist(items: CreatorListItem[]): void {
    localStorage.setItem(storageKey(), JSON.stringify(items));
}

export function isShortlisted(items: CreatorListItem[], id: number): boolean {
    return items.some((item) => item.id === id);
}

export function toggleShortlist(
    items: CreatorListItem[],
    creator: CreatorListItem,
): CreatorListItem[] {
    const exists = items.some((item) => item.id === creator.id);
    const next = exists
        ? items.filter((item) => item.id !== creator.id)
        : [creator, ...items];

    writeShortlist(next);

    return next;
}
