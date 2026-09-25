import {
    Calendar,
    ChevronDown,
    ImagePlus,
    Loader2,
    Medal,
    Plus,
    Smile,
    X,
} from 'lucide-react';
import { useRef, useState } from 'react';
import { toast } from 'sonner';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import type { MediaItem } from '@/components/media/types';
import {
    LinkedInPostPreview,
    type LinkedInPostAuthor,
} from '@/components/linkedin/linkedin-post-preview';
import { ApiError, creatorApi, http } from '@/lib/api';
import { cn } from '@/lib/utils';

type LinkedInPostBuilderProps = {
    author: LinkedInPostAuthor;
    value: string;
    onChange?: (value: string) => void;
    media: MediaItem[];
    onMediaChange?: (items: MediaItem[]) => void;
    publishedUrl?: string | null;
    readOnly?: boolean;
    className?: string;
    placeholder?: string;
};

/**
 * LinkedIn-style compose + live preview on soft-canvas tokens.
 * Compose pane mirrors create-post: author, open textarea, media toolbar.
 */
export function LinkedInPostBuilder({
    author,
    value,
    onChange,
    media,
    onMediaChange,
    publishedUrl = null,
    readOnly = false,
    className,
    placeholder = 'What do you want to talk about?',
}: LinkedInPostBuilderProps) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [uploading, setUploading] = useState(false);
    const initial = author.name.trim().slice(0, 1).toUpperCase() || '?';

    async function onPick(files: FileList | null) {
        if (!files || files.length === 0 || readOnly) {
            return;
        }

        setUploading(true);

        try {
            const uploaded: MediaItem[] = [];

            for (const file of Array.from(files)) {
                const form = new FormData();
                form.append('file', file);
                const { data } = await http.post<MediaItem>(
                    creatorApi.media,
                    form,
                );
                uploaded.push(data);
            }

            onMediaChange?.([...media, ...uploaded]);
        } catch (caught) {
            toast.error(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not upload media.',
            );
        } finally {
            setUploading(false);
            if (inputRef.current) {
                inputRef.current.value = '';
            }
        }
    }

    async function removeMedia(item: MediaItem) {
        if (readOnly) {
            return;
        }

        try {
            await http.delete(creatorApi.mediaDestroy(item.id));
            onMediaChange?.(media.filter((row) => row.id !== item.id));
        } catch (caught) {
            toast.error(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not remove media.',
            );
        }
    }

    return (
        <div className={cn('grid gap-6 lg:grid-cols-2', className)}>
            <div className="flex min-h-112 flex-col overflow-hidden rounded-2xl border border-border bg-card">
                <div className="flex items-center gap-3 px-6 pt-6">
                    <Avatar size="md" className="rounded-full">
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
                    <div className="min-w-0">
                        <button
                            type="button"
                            className="flex max-w-full items-center gap-1 rounded-pill text-left"
                            disabled
                            aria-label="Audience"
                        >
                            <span className="truncate text-sm font-semibold">
                                {author.name}
                            </span>
                            <ChevronDown className="size-4 shrink-0 text-muted-foreground" />
                        </button>
                        <p className="text-xs text-muted-foreground">
                            Post to Anyone
                        </p>
                    </div>
                </div>

                <div className="flex min-h-0 flex-1 flex-col px-6 pt-5">
                    {readOnly ? (
                        <p className="min-h-40 flex-1 whitespace-pre-wrap text-body leading-7">
                            {value.trim() || 'No draft yet.'}
                        </p>
                    ) : (
                        <textarea
                            value={value}
                            onChange={(event) => onChange?.(event.target.value)}
                            placeholder={placeholder}
                            rows={8}
                            className="placeholder:text-muted-foreground min-h-40 w-full flex-1 resize-none border-0 bg-transparent text-body leading-7 outline-none"
                        />
                    )}

                    {media.length > 0 ? (
                        <div className="mt-4 grid grid-cols-2 gap-3 pb-2 sm:grid-cols-3">
                            {media.map((item) => (
                                <div
                                    key={item.id}
                                    className="relative overflow-hidden rounded-2xl border border-border bg-muted"
                                >
                                    {item.kind === 'video' ? (
                                        <video
                                            src={item.url ?? undefined}
                                            className="aspect-square size-full object-cover"
                                            muted
                                            playsInline
                                        />
                                    ) : (
                                        <img
                                            src={item.url ?? undefined}
                                            alt=""
                                            className="aspect-square size-full object-cover"
                                        />
                                    )}
                                    {!readOnly ? (
                                        <button
                                            type="button"
                                            className="absolute top-2 right-2 flex size-8 items-center justify-center rounded-full bg-accent text-accent-foreground"
                                            aria-label="Remove media"
                                            onClick={() =>
                                                void removeMedia(item)
                                            }
                                        >
                                            <X className="size-3.5" />
                                        </button>
                                    ) : null}
                                </div>
                            ))}
                        </div>
                    ) : null}

                    <div className="flex items-center pb-3">
                        <span className="flex size-10 items-center justify-center rounded-pill text-muted-foreground">
                            <Smile className="size-5" />
                        </span>
                    </div>
                </div>

                <div className="flex items-center gap-1 border-t border-border bg-muted/60 px-4 py-3">
                    <input
                        ref={inputRef}
                        type="file"
                        accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/quicktime,video/webm"
                        multiple
                        className="hidden"
                        disabled={readOnly}
                        onChange={(event) => void onPick(event.target.files)}
                    />
                    {(
                        [
                            {
                                icon: ImagePlus,
                                label: 'Add a photo or video',
                                action: () => inputRef.current?.click(),
                                enabled: !readOnly,
                                accent: true,
                            },
                            {
                                icon: Calendar,
                                label: 'Create an event',
                                action: () => undefined,
                                enabled: false,
                                accent: false,
                            },
                            {
                                icon: Medal,
                                label: 'Celebrate an occasion',
                                action: () => undefined,
                                enabled: false,
                                accent: false,
                            },
                            {
                                icon: Plus,
                                label: 'More',
                                action: () => undefined,
                                enabled: false,
                                accent: false,
                            },
                        ] as const
                    ).map(({ icon: Icon, label, action, enabled, accent }) => (
                        <button
                            key={label}
                            type="button"
                            aria-label={label}
                            disabled={!enabled || uploading}
                            onClick={action}
                            className={cn(
                                'flex size-11 items-center justify-center rounded-pill transition-colors',
                                accent && enabled
                                    ? 'bg-accent text-accent-foreground hover:bg-accent/90'
                                    : 'text-muted-foreground',
                                enabled &&
                                    !accent &&
                                    !uploading &&
                                    'hover:bg-card hover:text-foreground',
                                !enabled && 'opacity-40',
                            )}
                        >
                            {label === 'Add a photo or video' && uploading ? (
                                <Loader2 className="size-5 animate-spin" />
                            ) : (
                                <Icon className="size-5" />
                            )}
                        </button>
                    ))}
                </div>
            </div>

            <div className="space-y-3">
                <p className="text-sm font-medium text-muted-foreground">
                    Preview
                </p>
                <LinkedInPostPreview
                    author={author}
                    body={value}
                    media={media}
                    publishedUrl={publishedUrl}
                    variant="full"
                />
            </div>
        </div>
    );
}
