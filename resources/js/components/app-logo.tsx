import AppLogoIcon from '@/components/app-logo-icon';
import { cn } from '@/lib/utils';

type AppLogoProps = {
    className?: string;
    showWordmark?: boolean;
};

export default function AppLogo({
    className,
    showWordmark = true,
}: AppLogoProps) {
    const name = window.Naano?.name ?? 'Naano';
    const label = name.charAt(0).toUpperCase() + name.slice(1);

    return (
        <span className={cn('inline-flex items-center gap-2', className)}>
            <AppLogoIcon className="size-8 shrink-0" />
            {showWordmark ? (
                <span className="truncate text-lg font-semibold tracking-tight text-foreground">
                    {label}
                </span>
            ) : null}
        </span>
    );
}
