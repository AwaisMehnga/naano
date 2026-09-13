# Tokens

`resources/css/app.css` is the only file that may define colors. Agentcard paper + ink + blue.

## Hex source

- Ink / foreground: `#171818`
- Muted text: `#7c7c7c`
- Primary / accent / links / CTAs: `#1520b8`
- Paper / background: `#f4f3ee`
- Hairline / border: `#c9c9c9`

Primary is the Agentcard tertiary blue. Ink stays on `--foreground`. Do not fill buttons with ink.

## `:root` (must match `app.css`)

```css
:root {
    --background: oklch(0.961 0.007 95);
    --foreground: oklch(0.21 0.005 145);
    --card: oklch(0.985 0.004 95);
    --card-foreground: oklch(0.21 0.005 145);
    --primary: oklch(0.372 0.22 264);
    --primary-foreground: oklch(0.985 0.004 95);
    --muted-foreground: oklch(0.55 0.01 145);
    --accent: oklch(0.38 0.18 264);
    --border: oklch(0.82 0.01 95);
    --radius: 0.5rem;
}
```

Fonts: Instrument Sans (`font-sans`) for marketing, auth, and onboarding. Instrument Serif italic (`font-serif italic`) only for the emphasized word in a display headline. Dashboards use IBM Plex Sans (`font-dashboard`) on `x-layouts.spa` only. Do not load Instrument on the SPA, and do not use IBM Plex on public pages.

`.dark` inverts paper/ink and keeps a lighter blue primary. Do not add `class="dark"` to marketing, auth, onboarding, or SPA layouts.

## Allowed classes

`bg-background` `text-foreground` `bg-card` `text-card-foreground` `bg-primary` `text-primary-foreground` `bg-secondary` `text-secondary-foreground` `bg-muted` `text-muted-foreground` `bg-accent` `text-accent-foreground` `text-destructive` `border-border` `border-input` `ring-ring` plus sidebar and chart tokens. Opacity modifiers are allowed (`bg-primary/80`).
