import { LogOut, Settings, UsersRound } from 'lucide-react';
import { AppLink } from '@/components/app-link';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { UserInfo } from '@/components/user-info';
import type { User } from '@/types';

type Props = {
    user: User;
};

export function UserMenuContent({ user }: Props) {
    const csrf = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');
    const profiles = user.profiles ?? [];
    const showProfiles =
        profiles.length > 1 || (user.can_create_profiles?.length ?? 0) > 0;

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

                {showProfiles ? (
                    <DropdownMenuItem asChild className="rounded-xl px-3 py-2.5">
                        <AppLink href="/setting/profiles">
                            <UsersRound />
                            Switch profile
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
