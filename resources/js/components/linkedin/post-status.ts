/** Badge variant for post workflow status (lime accent on positive states). */
export function postStatusBadgeVariant(
    status: string,
): 'accent' | 'soft' | 'outline' | 'destructive' {
    switch (status) {
        case 'published':
        case 'approved':
            return 'accent';
        case 'in_review':
        case 'scheduled':
            return 'soft';
        case 'rejected':
            return 'destructive';
        default:
            return 'outline';
    }
}

/** Soft-canvas surface for a review-history row. */
export function reviewActionSurface(action: string): string {
    switch (action) {
        case 'approved':
        case 'submitted':
            return 'border-transparent bg-lime-soft text-lime-soft-foreground';
        case 'rejected':
            return 'border-border bg-muted text-foreground';
        default:
            return 'border-border bg-muted text-foreground';
    }
}
