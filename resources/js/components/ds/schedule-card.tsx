import type { ReactNode } from 'react';
import { NotchedCard } from '@/components/notched-card';
import { InfoChip } from '@/components/info-chip';
import type { AvatarGroupItem } from '@/components/ds/avatar-group';
import { AvatarGroup } from '@/components/ds/avatar-group';

type ScheduleCardProps = {
    name: string;
    role: string;
    avatarSrc?: string | null;
    avatarFallback?: string;
    eventTitle: string;
    eventMeta?: string;
    participants?: AvatarGroupItem[];
    statusLabel?: string;
    eventIcon?: ReactNode;
    className?: string;
    footer?: ReactNode;
    chips?: ReactNode;
};

/** @deprecated Prefer `NotchedCard` directly. */
export function ScheduleCard({
    name,
    role,
    avatarSrc,
    avatarFallback,
    eventTitle,
    eventMeta,
    participants = [],
    statusLabel,
    eventIcon,
    className,
    footer,
    chips,
}: ScheduleCardProps) {
    return (
        <NotchedCard
            name={name}
            role={role}
            avatarSrc={avatarSrc}
            avatarFallback={avatarFallback}
            title={eventTitle}
            icon={eventIcon}
            className={className}
            chips={
                chips ?? (
                    <>
                        {statusLabel ? (
                            <InfoChip>{statusLabel}</InfoChip>
                        ) : null}
                        {eventMeta ? <InfoChip>{eventMeta}</InfoChip> : null}
                    </>
                )
            }
            meta={
                participants.length > 0 ? (
                    <AvatarGroup items={participants} max={3} size="sm" />
                ) : null
            }
            footer={footer}
        />
    );
}
