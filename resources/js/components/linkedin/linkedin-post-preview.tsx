import {
    ThumbsUp,
    MessageCircle,
    Repeat2,
    Send,
    ExternalLink,
} from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import type { MediaItem } from '@/components/media/types';
import { cn } from '@/lib/utils';

export type LinkedInPostAuthor = {
    name: string;
    headline?: string | null;
    avatarUrl?: string | null;
};

type LinkedInPostPreviewProps = {
    author: LinkedInPostAuthor;
    body: string;
    media?: MediaItem[];
    publishedUrl?: string | null;
    timestamp?: string;
    variant?: 'full' | 'compact';
    className?: string;
};

/** Soft-canvas LinkedIn-style feed card for draft/live post preview. */
export function LinkedInPostPreview({
    author,
    body,
    media = [],
    publishedUrl = null,
    timestamp = 'Just now',
    variant = 'full',
    className,
}: LinkedInPostPreviewProps) {
    const initial = author.name.trim().slice(0, 1).toUpperCase() || '?';
    const compact = variant === 'compact';
    const firstMedia = media[0] ?? null;
    const liveUrl = publishedUrl?.trim() || null;

    return (
        <article
            className={cn(
                'overflow-hidden rounded-2xl border border-border bg-card text-card-foreground',
                className,
            )}
        >
            <div
                className={cn(
                    'flex items-start gap-3',
                    compact ? 'px-4 pt-4' : 'px-5 pt-5',
                )}
            >
                <Avatar
                    size={compact ? 'default' : 'md'}
                    className="rounded-full"
                >
                    {author.avatarUrl ? (
                        <AvatarImage
                            src={author.avatarUrl}
                            alt=""
                            className="object-cover"
                        />
                    ) : null}
                    <AvatarFallback className="rounded-full bg-lime-soft text-sm font-medium text-lime-soft-foreground">
                        {initial}
                    </AvatarFallback>
                </Avatar>
                <div className="min-w-0 flex-1">
                    <p className="truncate text-sm font-semibold tracking-tight">
                        {author.name}
                    </p>
                    {!compact && author.headline ? (
                        <p className="truncate text-xs text-muted-foreground">
                            {author.headline}
                        </p>
                    ) : null}
                    <p className="text-xs text-muted-foreground">{timestamp}</p>
                </div>
            </div>

            <div className={cn(compact ? 'px-4 py-3' : 'px-5 py-4')}>
                {body.trim() ? (
                    <p
                        className={cn(
                            'text-sm leading-6 whitespace-pre-wrap',
                            compact && 'line-clamp-3',
                        )}
                    >
                        {body}
                    </p>
                ) : (
                    <p className="text-sm text-muted-foreground italic">
                        {compact ? 'Empty draft' : 'Start writing your post…'}
                    </p>
                )}

                {liveUrl ? (
                    <a
                        href={liveUrl}
                        target="_blank"
                        rel="noreferrer"
                        className={cn(
                            'mt-3 inline-flex max-w-full items-center gap-1.5 rounded-pill bg-accent px-3 py-1.5 text-sm font-medium text-accent-foreground',
                            compact && 'mt-2 px-2.5 py-1 text-xs',
                        )}
                        onClick={(event) => event.stopPropagation()}
                    >
                        <ExternalLink className="size-3.5 shrink-0" />
                        <span className="truncate">
                            {compact ? 'View on LinkedIn' : 'Open live post'}
                        </span>
                    </a>
                ) : null}
            </div>

            {firstMedia?.url ? (
                <div
                    className={cn(
                        'border-t border-border bg-muted',
                        compact ? 'aspect-video' : 'aspect-[4/3]',
                    )}
                >
                    {firstMedia.kind === 'video' ? (
                        <video
                            src={firstMedia.url}
                            className="size-full object-cover"
                            muted
                            playsInline
                            controls={!compact}
                        />
                    ) : (
                        <img
                            src={firstMedia.url}
                            alt=""
                            className="size-full object-cover"
                        />
                    )}
                </div>
            ) : null}

            {!compact && media.length > 1 ? (
                <div className="grid grid-cols-2 gap-0.5 border-t border-border">
                    {media.slice(1, 5).map((item) => (
                        <div key={item.id} className="aspect-square bg-muted">
                            {item.kind === 'video' ? (
                                <video
                                    src={item.url ?? undefined}
                                    className="size-full object-cover"
                                    muted
                                    playsInline
                                />
                            ) : (
                                <img
                                    src={item.url ?? undefined}
                                    alt=""
                                    className="size-full object-cover"
                                />
                            )}
                        </div>
                    ))}
                </div>
            ) : null}

            {!compact ? (
                <div className="flex items-center justify-between border-t border-border px-2 py-1.5">
                    {(
                        [
                            { icon: ThumbsUp, label: 'Like' },
                            { icon: MessageCircle, label: 'Comment' },
                            { icon: Repeat2, label: 'Repost' },
                            { icon: Send, label: 'Send' },
                        ] as const
                    ).map(({ icon: Icon, label }) => (
                        <span
                            key={label}
                            className="flex flex-1 items-center justify-center gap-1.5 rounded-pill px-2 py-2 text-xs font-medium text-muted-foreground"
                        >
                            <Icon className="size-3.5" />
                            {label}
                        </span>
                    ))}
                </div>
            ) : null}
        </article>
    );
}
