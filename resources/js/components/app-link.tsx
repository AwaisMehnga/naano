import { forwardRef, type ReactNode } from 'react';
import { Link as RouterLink, useInRouterContext } from 'react-router';

export const AppLink = forwardRef<
    HTMLAnchorElement,
    {
        href: string;
        children: ReactNode;
        className?: string;
        onClick?: () => void;
    }
>(function AppLink({ href, children, className, onClick }, ref) {
    if (useInRouterContext()) {
        return (
            <RouterLink
                ref={ref}
                to={href}
                className={className}
                onClick={onClick}
            >
                {children}
            </RouterLink>
        );
    }

    return (
        <a ref={ref} href={href} className={className} onClick={onClick}>
            {children}
        </a>
    );
});
