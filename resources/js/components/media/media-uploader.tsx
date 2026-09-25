import { ImagePlus, Loader2, X } from 'lucide-react';
import { useRef, useState } from 'react';
import { toast } from 'sonner';
import type { MediaItem } from '@/components/media/types';
import { Button } from '@/components/ui/button';
import { ApiError, creatorApi, http } from '@/lib/api';
import { cn } from '@/lib/utils';

type MediaUploaderProps = {
    items: MediaItem[];
    onChange: (items: MediaItem[]) => void;
    disabled?: boolean;
    className?: string;
};

/** Photo/video picker that uploads via the creator media API. */
export function MediaUploader({
    items,
    onChange,
    disabled = false,
    className,
}: MediaUploaderProps) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [uploading, setUploading] = useState(false);

    async function onPick(files: FileList | null) {
        if (!files || files.length === 0 || disabled) {
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

            onChange([...items, ...uploaded]);
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

    async function remove(item: MediaItem) {
        try {
            await http.delete(creatorApi.mediaDestroy(item.id));
            onChange(items.filter((row) => row.id !== item.id));
        } catch (caught) {
            toast.error(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not remove media.',
            );
        }
    }

    return (
        <div className={cn('space-y-3', className)}>
            {items.length > 0 ? (
                <div className="grid grid-cols-2 gap-2 sm:grid-cols-3">
                    {items.map((item) => (
                        <div
                            key={item.id}
                            className="relative overflow-hidden rounded-lg border border-border bg-muted"
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
                            {!disabled ? (
                                <button
                                    type="button"
                                    className="absolute top-1.5 right-1.5 flex size-8 items-center justify-center rounded-full bg-accent text-accent-foreground"
                                    aria-label="Remove media"
                                    onClick={() => void remove(item)}
                                >
                                    <X className="size-3.5" />
                                </button>
                            ) : null}
                        </div>
                    ))}
                </div>
            ) : null}

            {!disabled ? (
                <>
                    <input
                        ref={inputRef}
                        type="file"
                        accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/quicktime,video/webm"
                        multiple
                        className="hidden"
                        onChange={(event) => void onPick(event.target.files)}
                    />
                    <Button
                        type="button"
                        variant="accent"
                        size="icon"
                        disabled={uploading}
                        aria-label="Add photo or video"
                        onClick={() => inputRef.current?.click()}
                    >
                        {uploading ? (
                            <Loader2 className="size-4 animate-spin" />
                        ) : (
                            <ImagePlus className="size-4" />
                        )}
                    </Button>
                </>
            ) : null}
        </div>
    );
}
