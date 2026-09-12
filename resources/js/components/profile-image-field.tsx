import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export function ProfileImageField({
    id,
    name,
    label,
    url,
    onFileChange,
    onRemove,
    removing = false,
}: {
    id: string;
    name?: string;
    label: string;
    url: string | null;
    onFileChange: (file: File | null) => void;
    onRemove?: () => void;
    removing?: boolean;
}) {
    return (
        <div className="space-y-2">
            <Label htmlFor={id}>{label}</Label>
            {url && (
                <div className="flex items-center gap-3">
                    <img
                        src={url}
                        alt=""
                        className="size-16 rounded-md border border-border object-cover"
                    />
                    {onRemove && (
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={onRemove}
                            disabled={removing}
                        >
                            Remove
                        </Button>
                    )}
                </div>
            )}
            <Input
                id={id}
                name={name}
                type="file"
                accept="image/*"
                onChange={(event) =>
                    onFileChange(event.target.files?.[0] ?? null)
                }
            />
        </div>
    );
}
