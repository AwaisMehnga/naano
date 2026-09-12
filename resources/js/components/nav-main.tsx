import { useLocation } from 'react-router';
import { AppLink } from '@/components/app-link';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';

export function NavMain({ items }: { items: NavItem[] }) {
    const { pathname } = useLocation();

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel>Platform</SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => {
                    const isActive =
                        item.href === '/'
                            ? pathname === '/'
                            : item.href.startsWith('/setting')
                              ? pathname.startsWith('/setting')
                              : pathname === item.href ||
                                pathname.startsWith(`${item.href}/`);

                    return (
                    <SidebarMenuItem key={item.title}>
                        <SidebarMenuButton
                            asChild
                            isActive={isActive}
                            tooltip={{ children: item.title }}
                        >
                            <AppLink href={item.href}>
                                {item.icon && <item.icon />}
                                <span>{item.title}</span>
                            </AppLink>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    );
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}
