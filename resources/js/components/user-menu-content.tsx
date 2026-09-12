import { LogOut, Settings } from 'lucide-react';
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

    return (
        <>
            <DropdownMenuLabel className="p-0 font-normal">
                <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                    <UserInfo user={user} showEmail={true} />
                </div>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuGroup>
                <DropdownMenuItem asChild>
                    <AppLink
                        className="block w-full cursor-pointer"
                        href="/setting/profile"
                    >
                        <Settings className="mr-2" />
                        Settings
                    </AppLink>
                </DropdownMenuItem>
            </DropdownMenuGroup>
            <DropdownMenuSeparator />
            <DropdownMenuItem asChild>
                <form method="POST" action="/logout">
                    <input type="hidden" name="_token" value={csrf ?? ''} />
                    <button
                        type="submit"
                        className="flex w-full cursor-pointer items-center"
                        data-test="logout-button"
                    >
                        <LogOut className="mr-2" />
                        Log out
                    </button>
                </form>
            </DropdownMenuItem>
        </>
    );
}
