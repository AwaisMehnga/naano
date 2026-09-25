import type { ReactNode } from 'react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { cn } from '@/lib/utils';

type NotchedCardProps = {
    name: string;
    role: string;
    avatarSrc?: string | null;
    avatarFallback?: string;
    title: string;
    icon?: ReactNode;
    chips?: ReactNode;
    meta?: ReactNode;
    footer?: ReactNode;
    className?: string;
};

/** Soft-canvas opportunity/deal card. Neutral surface only — never accent-filled. */
export function NotchedCard({
    name,
    role,
    avatarSrc,
    avatarFallback = '?',
    title,
    icon,
    chips,
    meta,
    footer,
    className,
}: NotchedCardProps) {
    return (
        <div
            className={cn(
                'flex flex-col gap-5 rounded-3xl bg-muted p-5 text-foreground',
                className,
            )}
        >
            <div className="flex items-center gap-3">
                <Avatar size="md" className="ring-2 ring-background">
                    {avatarSrc ? (
                        <AvatarImage
                            src={avatarSrc}
                            alt={name}
                            className="object-cover"
                        />
                    ) : null}
                    <AvatarFallback className="bg-primary text-primary-foreground">
                        {avatarFallback.slice(0, 2)}
                    </AvatarFallback>
                </Avatar>
                <div className="min-w-0">
                    <p className="truncate text-sm font-semibold tracking-tight">
                        {name}
                    </p>
                    <p className="truncate text-xs text-muted-foreground">
                        {role}
                    </p>
                </div>
            </div>

            <div className="flex items-start gap-3">
                {icon ? (
                    <div className="flex size-10 shrink-0 items-center justify-center rounded-full bg-card">
                        {icon}
                    </div>
                ) : null}
                <div className="min-w-0 space-y-2.5">
                    <p className="text-base font-semibold tracking-tight text-balance">
                        {title}
                    </p>
                    {chips ? (
                        <div className="flex flex-wrap gap-1.5">{chips}</div>
                    ) : null}
                    {meta}
                </div>
            </div>

            {footer ? (
                <div className="mt-auto flex w-full flex-wrap items-center gap-2">
                    {footer}
                </div>
            ) : null}
        </div>
    );
}
