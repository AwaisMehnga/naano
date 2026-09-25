import type { SVGAttributes } from 'react';

/** Naano mark — matches Blade `x-ui.logo` (primary tile + N). */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            {...props}
            viewBox="0 0 32 32"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            aria-hidden="true"
        >
            <rect width="32" height="32" rx="7" className="fill-primary" />
            <path
                d="M8 24V8h4.4L20 18.2V8h4v16h-4.4L12 13.8V24H8z"
                className="fill-primary-foreground"
            />
        </svg>
    );
}
