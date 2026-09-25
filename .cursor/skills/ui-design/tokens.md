# Tokens

`resources/css/app.css` is the only file that may define colors. Lime system: black CTAs, white cards, lime accent.

## Hex source

- Ink / foreground / primary: `#000000`
- Canvas / background: `#F5F5F7`
- Card / surfaces: `#FFFFFF`
- Accent (lime): `#C7F33C`
- Soft lime: `#E1F2AE`
- Muted text: `#6B7280`

Primary is black for filled pills and active nav. Accent is lime for highlights, badges, and chart callouts. Soft lime is for soft fills (`bg-lime-soft`).

## `:root` (must match `app.css`)

```css
:root {
    --background: #f5f5f7;
    --foreground: #000000;
    --card: #ffffff;
    --card-foreground: #000000;
    --primary: #000000;
    --primary-foreground: #ffffff;
    --muted-foreground: #6b7280;
    --accent: #c7f33c;
    --accent-foreground: #000000;
    --lime-soft: #e1f2ae;
    --border: #e5e5e8;
    --radius: 1.5rem;
}
```

Fonts: Inter (`font-sans` / `font-dashboard`) for the whole product. Type scale: `text-heading` 34px, `text-title` 28px, `text-body` 16px.

`.dark` inverts canvas/ink and keeps lime accent. Do not add `class="dark"` to marketing, auth, onboarding, or SPA layouts.

## Allowed classes

`bg-background` `text-foreground` `bg-card` `text-card-foreground` `bg-primary` `text-primary-foreground` `bg-secondary` `text-secondary-foreground` `bg-muted` `text-muted-foreground` `bg-accent` `text-accent-foreground` `bg-lime-soft` `text-lime-soft-foreground` `text-destructive` `border-border` `border-input` `ring-ring` plus sidebar and chart tokens. Opacity modifiers are allowed (`bg-primary/80`).
