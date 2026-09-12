import { countries } from '@/lib/lookups';

export function euros(cents: number | null | undefined): string {
    if (cents === null || cents === undefined) {
        return '—';
    }

    return `€${Math.round(cents / 100)}`;
}

export function compact(value: number | null | undefined): string {
    if (value === null || value === undefined) {
        return '—';
    }

    return new Intl.NumberFormat('en', {
        notation: 'compact',
        maximumFractionDigits: 1,
    }).format(value);
}

export function countryLabel(code: string | null): string {
    if (code === null) {
        return '—';
    }

    return countries.find((item) => item.value === code)?.label ?? code;
}

export function estimatedCpm(
    priceCents: number | null,
    followers: number | null,
): number | null {
    if (priceCents === null || !followers || followers < 1) {
        return null;
    }

    return Math.round(priceCents / (followers / 1000));
}

export function initials(name: string | null): string {
    return (name ?? '?').slice(0, 1).toUpperCase();
}

export function centsFromEuros(value: string): number | undefined {
    if (value === '') {
        return undefined;
    }

    const amount = Number(value);

    if (!Number.isFinite(amount)) {
        return undefined;
    }

    return Math.round(amount * 100);
}

export function offerLabel(label: string): string {
    return label === 'single_post' ? 'Single post' : 'Bundle';
}
