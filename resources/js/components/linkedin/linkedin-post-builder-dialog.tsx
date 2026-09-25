import type { ReactNode } from 'react';
import { LinkedInPostBuilder } from '@/components/linkedin/linkedin-post-builder';
import type { LinkedInPostAuthor } from '@/components/linkedin/linkedin-post-preview';
import {
    postStatusBadgeVariant,
    reviewActionSurface,
} from '@/components/linkedin/post-status';
import type { MediaItem, PostReviewItem } from '@/components/media/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { cn } from '@/lib/utils';

type LinkedInPostBuilderDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title?: string;
    description?: string;
    statusLabel?: string;
    status?: string;
    author: LinkedInPostAuthor;
    value: string;
    onChange?: (value: string) => void;
    media: MediaItem[];
    onMediaChange?: (items: MediaItem[]) => void;
    reviews?: PostReviewItem[];
    publishedUrl?: string | null;
    readOnly?: boolean;
    footer?: ReactNode;
    onSave?: () => void;
    onSubmit?: () => void;
    saving?: boolean;
    canSubmit?: boolean;
};

function titleCase(value: string): string {
    return value
        .replaceAll('_', ' ')
        .replace(/^\w/, (letter) => letter.toUpperCase());
}

/** Roomier soft-canvas compose modal with review history and lime accents. */
export function LinkedInPostBuilderDialog({
    open,
    onOpenChange,
    title = 'Create a post',
    description,
    statusLabel,
    status,
    author,
    value,
    onChange,
    media,
    onMediaChange,
    reviews = [],
    publishedUrl = null,
    readOnly = false,
    footer,
    onSave,
    onSubmit,
    saving = false,
    canSubmit = true,
}: LinkedInPostBuilderDialogProps) {
    const liveUrl = publishedUrl?.trim() || null;
    const badgeStatus = status ?? statusLabel?.toLowerCase().replaceAll(' ', '_') ?? '';

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="flex max-h-[90vh] w-full flex-col gap-0 overflow-hidden rounded-2xl border-border p-0 sm:max-w-5xl">
                <DialogHeader className="shrink-0 space-y-2 border-b border-border px-6 py-5 pr-12 text-left">
                    <div className="flex flex-wrap items-center gap-2">
                        <DialogTitle className="text-title font-medium tracking-tight">
                            {title}
                        </DialogTitle>
                        {statusLabel ? (
                            <Badge variant={postStatusBadgeVariant(badgeStatus)}>
                                {statusLabel}
                            </Badge>
                        ) : null}
                    </div>
                    {description ? (
                        <DialogDescription>{description}</DialogDescription>
                    ) : (
                        <DialogDescription>
                            Write your post, add media, and check the preview.
                        </DialogDescription>
                    )}
                </DialogHeader>

                <div className="grid min-h-0 flex-1 gap-0 overflow-hidden lg:grid-cols-[minmax(0,1fr)_18rem]">
                    <div className="min-h-0 overflow-y-auto bg-background px-6 py-6">
                        <LinkedInPostBuilder
                            author={author}
                            value={value}
                            onChange={onChange}
                            media={media}
                            onMediaChange={onMediaChange}
                            publishedUrl={liveUrl}
                            readOnly={readOnly}
                        />
                    </div>

                    <aside className="flex min-h-0 flex-col border-t border-border bg-card lg:border-t-0 lg:border-l">
                        {liveUrl ? (
                            <div className="shrink-0 space-y-3 border-b border-border bg-lime-soft px-5 py-4 text-lime-soft-foreground">
                                <p className="text-sm font-medium">
                                    Live on LinkedIn
                                </p>
                                <a
                                    href={liveUrl}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="block break-all text-sm font-medium underline-offset-4 hover:underline"
                                >
                                    {liveUrl}
                                </a>
                            </div>
                        ) : null}
                        <div className="shrink-0 border-b border-border px-5 py-4">
                            <p className="text-sm font-medium text-muted-foreground">
                                Review history
                            </p>
                        </div>
                        <div className="min-h-0 flex-1 overflow-y-auto px-5 py-4">
                            {reviews.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No review activity yet.
                                </p>
                            ) : (
                                <ul className="space-y-3">
                                    {reviews.map((review) => (
                                        <li
                                            key={review.id}
                                            className={cn(
                                                'rounded-2xl border px-4 py-3',
                                                reviewActionSurface(
                                                    review.action,
                                                ),
                                            )}
                                        >
                                            <p className="text-xs font-medium">
                                                {titleCase(review.action)}
                                            </p>
                                            <p className="mt-0.5 text-xs text-muted-foreground">
                                                {review.actor_name}
                                                {review.created_at
                                                    ? ` · ${new Date(review.created_at).toLocaleString()}`
                                                    : ''}
                                            </p>
                                            {review.note ? (
                                                <p className="mt-2 text-sm leading-5">
                                                    {review.note}
                                                </p>
                                            ) : null}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>
                    </aside>
                </div>

                <DialogFooter className="shrink-0 gap-3 border-t border-border bg-card px-6 py-4 sm:justify-end">
                    {footer ?? (
                        <>
                            {!readOnly && onSave ? (
                                <Button
                                    type="button"
                                    variant="outline"
                                    disabled={saving}
                                    onClick={onSave}
                                >
                                    Save draft
                                </Button>
                            ) : (
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => onOpenChange(false)}
                                >
                                    Close
                                </Button>
                            )}
                            {!readOnly && onSubmit ? (
                                <Button
                                    type="button"
                                    variant="accent"
                                    disabled={saving || !canSubmit}
                                    onClick={onSubmit}
                                >
                                    Post
                                </Button>
                            ) : null}
                        </>
                    )}
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
