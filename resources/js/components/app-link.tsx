import { forwardRef, type AnchorHTMLAttributes, type ReactNode } from 'react';
import { Link as RouterLink, useInRouterContext } from 'react-router';

type AppLinkProps = {
    href: string;
    children: ReactNode;
    className?: string;
    onClick?: () => void;
} & Omit<AnchorHTMLAttributes<HTMLAnchorElement>, 'href' | 'children' | 'className' | 'onClick'>;

export const AppLink = forwardRef<HTMLAnchorElement, AppLinkProps>(
    function AppLink({ href, children, className, onClick, ...props }, ref) {
        if (useInRouterContext()) {
            return (
                <RouterLink
                    ref={ref}
                    to={href}
                    className={className}
                    onClick={onClick}
                    {...props}
                >
                    {children}
                </RouterLink>
            );
        }

        return (
            <a
                ref={ref}
                href={href}
                className={className}
                onClick={onClick}
                {...props}
            >
                {children}
            </a>
        );
    },
);
