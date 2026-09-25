import { useState } from 'react';
import { Building2, LogOut, Settings, UserRound } from 'lucide-react';
import { toast } from 'sonner';
import { AppLink } from '@/components/app-link';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { UserInfo } from '@/components/user-info';
import { ApiError, http, sharedApi } from '@/lib/api';
import type { User } from '@/types';

type ProfilesPayload = {
    profiles: NonNullable<User['profiles']>;
    active_profile: string | null;
    can_create: string[];
};

type Props = {
    user: User;
};

function profileIcon(type: string) {
    return type === 'company' ? Building2 : UserRound;
}

function profileLabel(type: string): string {
    return type === 'company' ? 'company' : 'creator';
}

export function UserMenuContent({ user }: Props) {
    const [busy, setBusy] = useState(false);
    const csrf = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');
    const profiles = user.profiles ?? [];
    const active = user.active_profile ?? user.role ?? null;
    const otherProfiles = profiles.filter((profile) => profile.type !== active);
    const canCreate = user.can_create_profiles ?? [];

    async function switchTo(type: string) {
        if (busy) {
            return;
        }

        setBusy(true);

        try {
            const { data: payload } = await http.patch<ProfilesPayload>(
                sharedApi.profilesActive,
                { type },
            );

            if (window.Naano?.user) {
                window.Naano.user.active_profile = payload.active_profile;
                window.Naano.user.role = payload.active_profile;
                window.Naano.user.profiles = payload.profiles;
                window.Naano.user.can_create_profiles = payload.can_create;
            }

            toast.success(
                type === 'creator'
                    ? 'Switched to creator'
                    : 'Switched to company',
            );

            window.location.href = type === 'creator' ? '/creator' : '/company';
        } catch (caught) {
            setBusy(false);
            toast.error(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not switch profile.',
            );
        }
    }

    return (
        <>
            <DropdownMenuLabel className="p-0 font-normal">
                <div className="flex items-center gap-3 rounded-2xl bg-muted/60 px-3 py-3">
                    <UserInfo user={user} showEmail={true} size="lg" />
                </div>
            </DropdownMenuLabel>

            <DropdownMenuSeparator className="my-2" />

            <DropdownMenuGroup className="flex flex-col gap-0.5">
                <DropdownMenuItem asChild className="rounded-xl px-3 py-2.5">
                    <AppLink href="/setting/profile">
                        <Settings />
                        Settings
                    </AppLink>
                </DropdownMenuItem>

                {otherProfiles.map((profile) => {
                    const Icon = profileIcon(profile.type);

                    return (
                        <DropdownMenuItem
                            key={profile.type}
                            className="rounded-xl px-3 py-2.5"
                            disabled={busy}
                            onSelect={(event) => {
                                event.preventDefault();
                                void switchTo(profile.type);
                            }}
                        >
                            <Icon />
                            Switch to {profileLabel(profile.type)}
                        </DropdownMenuItem>
                    );
                })}

                {canCreate.length > 0 ? (
                    <DropdownMenuItem asChild className="rounded-xl px-3 py-2.5">
                        <AppLink href="/setting/profiles">
                            <UserRound />
                            Add profile
                        </AppLink>
                    </DropdownMenuItem>
                ) : null}
            </DropdownMenuGroup>

            <DropdownMenuSeparator className="my-2" />

            <DropdownMenuItem
                variant="destructive"
                className="rounded-xl px-3 py-2.5"
                asChild
            >
                <form method="POST" action="/logout" className="w-full">
                    <input type="hidden" name="_token" value={csrf ?? ''} />
                    <button
                        type="submit"
                        className="flex w-full cursor-pointer items-center gap-2"
                        data-test="logout-button"
                    >
                        <LogOut />
                        Log out
                    </button>
                </form>
            </DropdownMenuItem>
        </>
    );
}
