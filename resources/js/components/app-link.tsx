import { Link as RouterLink, useInRouterContext } from 'react-router';

export function AppLink({
    href,
    children,
    className,
    onClick,
}: {
    href: string;
    children: React.ReactNode;
    className?: string;
    onClick?: () => void;
}) {
    if (useInRouterContext()) {
        return (
            <RouterLink to={href} className={className} onClick={onClick}>
                {children}
            </RouterLink>
        );
    }

    return (
        <a href={href} className={className} onClick={onClick}>
            {children}
        </a>
    );
}
