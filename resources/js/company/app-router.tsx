import { Suspense } from 'react';
import { useRoutes } from 'react-router';
import { Spinner } from '@/components/ui/spinner';
import { routes } from './_routes';

export default function AppRouter() {
    const element = useRoutes(routes);

    return (
        <Suspense
            fallback={
                <div className="flex min-h-svh items-center justify-center">
                    <Spinner className="size-6" />
                </div>
            }
        >
            {element}
        </Suspense>
    );
}
