import { ArrowUpRight, Mail, Video } from 'lucide-react';
import type { ReactNode } from 'react';
import { AvatarGroup, type AvatarGroupItem } from '@/components/ds/avatar-group';
import { IconButton } from '@/components/ds/icon-button';
import { StatusPill } from '@/components/ds/status-pill';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { cn } from '@/lib/utils';

type ScheduleCardProps = {
    variant?: 'default' | 'accent';
    name: string;
    role: string;
    avatarSrc?: string | null;
    avatarFallback?: string;
    eventTitle: string;
    eventMeta: string;
    participants?: AvatarGroupItem[];
    statusLabel?: string;
    eventIcon?: ReactNode;
    className?: string;
};

export function ScheduleCard({
    variant = 'default',
    name,
    role,
    avatarSrc,
    avatarFallback = '?',
    eventTitle,
    eventMeta,
    participants = [],
    statusLabel = 'Call Scheduled',
    eventIcon,
    className,
}: ScheduleCardProps) {
    const accent = variant === 'accent';

    return (
        <div className={cn('relative pt-2 pr-2', className)}>
            <div
                className={cn(
                    'flex flex-col gap-5 rounded-2xl p-5 shadow-[var(--shadow-soft)]',
                    accent
                        ? 'bg-accent text-accent-foreground'
                        : 'bg-card text-card-foreground',
                )}
            >
                <div className="flex items-center gap-3 pr-10">
                    <Avatar size="md">
                        {avatarSrc ? (
                            <AvatarImage src={avatarSrc} alt={name} />
                        ) : null}
                        <AvatarFallback>
                            {avatarFallback.slice(0, 2)}
                        </AvatarFallback>
                    </Avatar>
                    <div className="min-w-0">
                        <p className="truncate font-medium">{name}</p>
                        <p
                            className={cn(
                                'truncate text-sm',
                                accent
                                    ? 'text-accent-foreground/70'
                                    : 'text-muted-foreground',
                            )}
                        >
                            {role}
                        </p>
                    </div>
                </div>

                <div className="flex items-start gap-3">
                    <div
                        className={cn(
                            'flex size-10 shrink-0 items-center justify-center rounded-full',
                            accent ? 'bg-primary/10' : 'bg-muted',
                        )}
                    >
                        {eventIcon ?? <Video className="size-4" />}
                    </div>
                    <div className="min-w-0">
                        <p className="font-medium">{eventTitle}</p>
                        <div className="mt-1 flex flex-wrap items-center gap-2 text-sm">
                            {participants.length > 0 ? (
                                <AvatarGroup
                                    items={participants}
                                    max={2}
                                    size="sm"
                                />
                            ) : null}
                            <span
                                className={cn(
                                    'tabular-nums',
                                    accent
                                        ? 'text-accent-foreground/70'
                                        : 'text-muted-foreground',
                                )}
                            >
                                {eventMeta}
                            </span>
                        </div>
                    </div>
                </div>

                <div className="flex items-center gap-2">
                    <StatusPill
                        label={statusLabel}
                        avatarSrc={avatarSrc}
                        avatarFallback={avatarFallback}
                        className={cn(
                            'min-w-0 flex-1',
                            accent && 'border-primary/20 bg-card/50',
                        )}
                    />
                    <IconButton
                        variant={accent ? 'default' : 'outline'}
                        size="sm"
                        aria-label="Message"
                    >
                        <Mail />
                    </IconButton>
                    <IconButton
                        variant="default"
                        size="sm"
                        aria-label="Join call"
                    >
                        <Video />
                    </IconButton>
                </div>
            </div>

            <div className="absolute top-0 right-0">
                <IconButton
                    variant={accent ? 'default' : 'outline'}
                    size="sm"
                    aria-label="Open"
                    className="shadow-[var(--shadow-soft)]"
                >
                    <ArrowUpRight />
                </IconButton>
            </div>
        </div>
    );
}
