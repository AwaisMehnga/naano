import { Outlet } from 'react-router';
import { CreatorTopBar } from '@/creator/components/creator-top-bar';

export default function CreatorShellLayout() {
    return (
        <div className="flex min-h-svh flex-col bg-background font-dashboard text-foreground">
            <CreatorTopBar />

            <main className="flex w-full min-w-0 flex-1 flex-col px-6 pb-10 lg:px-8">
                <div className="flex w-full min-w-0 flex-1 flex-col">
                    <Outlet />
                </div>
            </main>
        </div>
    );
}
