# Tokens

`resources/css/app.css` is the **only** file that may define colors. Edit `:root` there to restyle the whole product. Never paste these hex values into Blade or TSX — use semantic classes.

## Hex source (documentation only)

| Role | Hex | Semantic use |
| --- | --- | --- |
| Ink / primary CTAs | `#000000` | `bg-primary` `text-foreground` |
| Canvas | `#F5F5F7` | `bg-background` |
| Card / white | `#FFFFFF` | `bg-card` |
| Accent lime | `#C7F33C` | `bg-accent` |
| Soft lime | `#E1F2AE` | `bg-lime-soft` |
| Muted text | `#6B7280` | `text-muted-foreground` |
| Border | `#E5E5E8` | `border-border` |

Primary is black for filled pills and active nav. Accent (`bg-accent`) is the bright lime for brand banners, badges, and highlights — prefer it over soft-lime. Soft lime (`bg-lime-soft`) is only for quiet secondary fills, never a substitute for accent.

## Allowed semantic color classes

`bg-background` `text-foreground`  
`bg-card` `text-card-foreground`  
`bg-popover` `text-popover-foreground`  
`bg-primary` `text-primary-foreground`  
`bg-secondary` `text-secondary-foreground`  
`bg-muted` `text-muted-foreground`  
`bg-accent` `text-accent-foreground`  
`bg-lime-soft` `text-lime-soft-foreground`  
`bg-destructive` `text-destructive` `text-destructive-foreground`  
`border-border` `border-input` `ring-ring`  
`bg-chart-1` … `bg-chart-5` (or `var(--chart-1)` in chart SVG/Recharts)  
Sidebar tokens: `bg-sidebar` `text-sidebar-foreground` `bg-sidebar-primary` `bg-sidebar-accent` `border-sidebar-border` `ring-sidebar-ring`

Opacity modifiers on tokens are fine: `bg-primary/90`, `text-foreground/20`.

## Type & radius (from `@theme`)

| Token | Value / class |
| --- | --- |
| Heading | `text-heading` (34px) |
| Title | `text-title` (28px) |
| Body | `text-body` (16px) |
| Pill | `rounded-pill` |
| Soft card | `rounded-3xl` |
| Card | `rounded-2xl` |

Fonts: Inter (`font-sans` / `font-dashboard`) only.

## Elevation

`--shadow-soft: none`. Do not use `shadow-*`. Separate layers with `border-border` and canvas vs card.

## Dark

`.dark` exists in `app.css` but product surfaces stay light. Do not add `class="dark"` to marketing, auth, onboarding, or SPA layouts.

## Forbidden

- Palette utilities: `bg-neutral-*`, `text-white`, `text-black`, `text-red-600`, `bg-green-*`
- Arbitrary colors: `bg-[#…]`, `text-[oklch(…)]`, `border-[rgb(…)]`
- New `--color-*` / `@theme` blocks outside `app.css`
- Hardcoded hex/rgb/oklch in `className` or `style` (except non-color layout like width %)
