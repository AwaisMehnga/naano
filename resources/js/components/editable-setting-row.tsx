import { type ReactNode, useState } from 'react';
import { Pencil } from 'lucide-react';
import { IconButton } from '@/components/ds/icon-button';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type EditableSettingRowProps = {
    label: string;
    displayValue: ReactNode;
    emptyLabel?: string;
    editing: boolean;
    onEdit: () => void;
    onCancel: () => void;
    onSave: () => void | Promise<void>;
    saving?: boolean;
    disabled?: boolean;
    children: ReactNode;
    className?: string;
};

/** Read-only setting row with pencil-to-edit and Save / Cancel. */
export function EditableSettingRow({
    label,
    displayValue,
    emptyLabel = 'Not set',
    editing,
    onEdit,
    onCancel,
    onSave,
    saving = false,
    disabled = false,
    children,
    className,
}: EditableSettingRowProps) {
    const [busy, setBusy] = useState(false);
    const isBusy = busy || saving;
    const hasValue =
        displayValue !== null &&
        displayValue !== undefined &&
        displayValue !== '';

    async function save() {
        setBusy(true);
        try {
            await onSave();
        } finally {
            setBusy(false);
        }
    }

    return (
        <div
            className={cn(
                'rounded-2xl border border-border bg-card px-5 py-4',
                className,
            )}
        >
            <div className="flex items-start justify-between gap-3">
                <p className="text-sm font-medium text-muted-foreground">
                    {label}
                </p>
                {!editing && !disabled ? (
                    <IconButton
                        variant="ghost"
                        size="sm"
                        aria-label={`Edit ${label}`}
                        onClick={onEdit}
                    >
                        <Pencil className="size-4" />
                    </IconButton>
                ) : null}
            </div>

            {editing ? (
                <div className="mt-3 space-y-4">
                    {children}
                    <div className="flex flex-wrap gap-2">
                        <Button
                            type="button"
                            variant="accent"
                            size="sm"
                            disabled={isBusy}
                            onClick={() => void save()}
                        >
                            {isBusy ? 'Saving…' : 'Save'}
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            disabled={isBusy}
                            onClick={onCancel}
                        >
                            Cancel
                        </Button>
                    </div>
                </div>
            ) : (
                <div className="mt-2 text-sm leading-relaxed whitespace-pre-wrap">
                    {hasValue ? (
                        displayValue
                    ) : (
                        <span className="text-muted-foreground">
                            {emptyLabel}
                        </span>
                    )}
                </div>
            )}
        </div>
    );
}
