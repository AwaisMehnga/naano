import { AppLink } from '@/components/app-link';
import { cn } from '@/lib/utils';

export type SettingsNavItem = {
    title: string;
    href: string;
};

export function SettingsNav({
    items,
    pathname,
}: {
    items: SettingsNavItem[];
    pathname: string;
}) {
    return (
        <nav className="flex flex-col gap-1">
            {items.map((item) => {
                const isActive =
                    pathname === item.href ||
                    pathname.startsWith(`${item.href}/`);

                return (
                    <AppLink
                        key={item.href}
                        href={item.href}
                        className={cn(
                            'rounded-md px-3 py-2 text-sm',
                            isActive
                                ? 'bg-muted font-medium text-foreground'
                                : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
                        )}
                    >
                        {item.title}
                    </AppLink>
                );
            })}
        </nav>
    );
}
