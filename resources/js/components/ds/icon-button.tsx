import { cva, type VariantProps } from 'class-variance-authority';
import type { ComponentProps } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

const iconButtonVariants = cva('rounded-full shrink-0', {
    variants: {
        variant: {
            default: '',
            outline: '',
            accent: '',
            ghost: '',
            muted: '',
        },
        size: {
            sm: '',
            default: '',
            lg: '',
        },
    },
});

const sizeMap = {
    sm: 'icon-sm',
    default: 'icon',
    lg: 'icon-lg',
} as const;

const variantMap = {
    default: 'default',
    outline: 'outline',
    accent: 'accent',
    ghost: 'ghost',
    muted: 'secondary',
} as const;

type IconButtonProps = Omit<ComponentProps<typeof Button>, 'variant' | 'size'> &
    VariantProps<typeof iconButtonVariants>;

export function IconButton({
    className,
    variant = 'outline',
    size = 'default',
    type = 'button',
    ...props
}: IconButtonProps) {
    return (
        <Button
            type={type}
            variant={variantMap[variant ?? 'outline']}
            size={sizeMap[size ?? 'default']}
            className={cn(iconButtonVariants({ variant, size }), className)}
            {...props}
        />
    );
}
