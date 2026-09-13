import { countryLabel } from '@/company/pages/creators/format';

export function locationLabel(location: {
    country: string | null;
    regions: string[];
}): string {
    const parts = [
        countryLabel(location.country),
        ...location.regions.filter((region) => region.trim() !== ''),
    ].filter((part, index, all) => part !== '—' && all.indexOf(part) === index);

    return parts.length > 0 ? parts.join(' · ') : '—';
}

export function deadlineLabel(value: string | null): string {
    if (!value) {
        return 'Open';
    }

    return new Date(value).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

export function scoreLabel(value: number | null | undefined): string {
    if (value === null || value === undefined) {
        return '—';
    }

    return `${value}%`;
}
