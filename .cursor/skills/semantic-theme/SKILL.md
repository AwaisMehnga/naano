---
name: semantic-theme
description: Enforces theme tokens from resources/css/app.css only. Use semantic Tailwind color classes (background, foreground, primary, muted, sidebar, accent, lime-soft). Do not define new color classes in Blade, TSX, or other CSS files. Activate when styling UI, writing className, editing Tailwind, themes, colors, dashboards, or app.css.
---

# Semantic theme

We only need to use the semantic classes. Not need to define new color classes within the files. Only use the app.css so we can update the theme of whole website with a single file.

## Source of truth

`resources/css/app.css` is the only file that may define colors. Change `:root` tokens there to restyle the site.

Lime system mapping in `:root` (do not paste these hex values into Blade or TSX):

- Ink / foreground / primary CTAs: `#000000`
- Canvas / background: `#F5F5F7`
- Card / white surfaces: `#FFFFFF`
- Accent (lime): `#C7F33C`
- Soft lime: `#E1F2AE`
- Muted text: `#6B7280`

Dashboards (company and creator SPAs) are **light mode only**. Do not add `class="dark"` to the SPA layout. Do not add appearance scripts that flip dashboards to dark. Do not add `class="dark"` to marketing, auth, or onboarding layouts.

## Allowed color classes

Use the Tailwind utilities mapped from `@theme` in `app.css`:

| Token | Typical classes |
| --- | --- |
| background / foreground | `bg-background` `text-foreground` |
| card | `bg-card` `text-card-foreground` |
| popover | `bg-popover` `text-popover-foreground` |
| primary | `bg-primary` `text-primary-foreground` |
| secondary | `bg-secondary` `text-secondary-foreground` |
| muted | `bg-muted` `text-muted-foreground` |
| accent | `bg-accent` `text-accent-foreground` |
| lime-soft | `bg-lime-soft` `text-lime-soft-foreground` |
| destructive | `bg-destructive` `text-destructive` `text-destructive-foreground` |
| border / input / ring | `border-border` `border-input` `ring-ring` |
| chart | `bg-chart-1` … `bg-chart-5` |
| sidebar | `bg-sidebar` `text-sidebar-foreground` `bg-sidebar-primary` `text-sidebar-primary-foreground` `bg-sidebar-accent` `text-sidebar-accent-foreground` `border-sidebar-border` `ring-sidebar-ring` |

Opacity modifiers on those tokens are fine: `bg-primary/90`, `text-foreground/20`.

Typography: `text-heading` (34px), `text-title` (28px), `text-body` (16px). Radius: `rounded-pill`, `rounded-3xl` (SoftCard), `rounded-2xl`.

No shadows: do not use `shadow-*`. Surfaces use `border-border` and canvas vs card. `--shadow-soft` is `none`.

Layout, spacing, typography, and radius utilities (`flex`, `gap-4`, `text-sm`, `rounded-md`) are not colors. Use them freely. Prefer roomy spacing on dashboards (`gap-5`+, SoftCard `p-6`, tall pills).

## Forbidden in Blade, TSX, and other CSS

- Palette utilities: `bg-neutral-*`, `text-red-600`, `text-white`, `text-black`, `bg-green-*`, `stroke-neutral-*`
- Arbitrary colors: `bg-[#fff]`, `text-[oklch(...)]`, `border-[rgb(...)]`
- New `--color-*` or `@theme` blocks outside `app.css`
- Hardcoded `oklch`, hex, or rgb in component `className` or `style` (except non-color layout values like width %)
- `dark:` variants on dashboard pages and layouts (dashboards stay light)

If a needed color does not exist, add a token in `app.css` first, then use the semantic class.

## Examples

```tsx
// Bad
<div className="bg-neutral-200 text-black dark:bg-neutral-700">

// Good
<div className="bg-muted text-foreground">
```

```tsx
// Bad
<p className="text-red-600">Required</p>

// Good
<p className="text-destructive">Required</p>
```
