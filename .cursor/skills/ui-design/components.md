# Components

Use these Blade components. Do not paste their class strings into pages.

## `x-ui.button`

```php
@props([
    'variant' => 'primary', // primary|secondary|accent|inverted|ghost|link
    'size' => 'md',         // sm|md|lg
    'href' => null,
    'type' => 'submit',
])
```

If `$href` is set, render `<a>`. Else `<button type="{{ $type }}">`. Merge `$attributes`. Pass `data-test` through the attribute bag.

- Primary: black pill CTA (`rounded-pill bg-primary text-primary-foreground`)
- Secondary: bordered white pill
- Accent: lime pill (`bg-accent`)
- Ghost / link: text actions
- Size `sm`: compact; `lg`: larger padding

`:active` scale lives in `app.css` (`scale(0.97)`).

## `x-ui.kicker`

Uppercase tracking label: `text-xs font-medium uppercase tracking-[0.18em] text-muted-foreground`

`tone="on-primary"`: `text-primary-foreground/65` for kickers on a `bg-primary` band.

`tone="on-inverse"`: `text-background/65` for kickers on a `bg-foreground` band.

## `x-ui.accordion-item`

```php
@props([
    'question',
    'name' => null,
    'open' => false,
    'tone' => null, // on-primary|on-inverse
])
```

Native `<details>` row. Shared `name` keeps one item open. Slot is the answer.

## `x-ui.em`

Italic emphasis inside a display headline: `font-serif italic font-normal`. One emphasized word per headline.

## `x-ui.input` / `x-ui.textarea` / `x-ui.select`

Pill inputs: `w-full rounded-pill border border-input bg-card px-4 py-2.5 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30`

## `x-ui.field`

```php
@props(['label', 'name' => null, 'hint' => null])
```

Slot is the control. Label: `text-sm font-medium`. Hint: `text-sm text-muted-foreground`. Error: `text-sm text-destructive`. If `$name` is set, render `@error($name)` and a `data-error-for="{{ $name }}"` element for AJAX field errors.

## `x-ui.card`

Default: `rounded-2xl border border-border bg-card p-6 shadow-[var(--shadow-soft)]`

`flush` (bool): drop padding. `href` (string|null): block link. `selectable` (bool): lime selected state via `has-[:checked]:border-accent has-[:checked]:bg-lime-soft`.

## `x-ui.alert`

Variants: `info` | `success` | `danger`.

- info: `text-sm text-foreground`
- success: `text-sm font-medium text-accent`
- danger: `text-sm text-destructive`

Onboarding top errors use `id="form-errors"`.

## `x-ui.favicon`

Head icons via `asset()` plus a filemtime query so Cloudflare/browser caches miss after a change.

## `x-ui.logo`

`text-lg font-semibold tracking-tight text-foreground` linking to `route('home')`. Text: `config('app.name')`.

## `x-ui.stepper`

Props: `step` (int, 1-based), `steps` (list of labels).

- current: `bg-primary text-primary-foreground`
- done: `bg-primary/80 text-primary-foreground`
- upcoming: `bg-muted text-muted-foreground`

## Layouts

- `x-layouts.marketing` — paper masthead, landing Vite entry. No backdrop blur.
- `x-layouts.auth` — full-height split screen: form column + `bg-primary` info panel. Top bar with logo + CTA. Short copy; lime accent on panel steps/dots.
- `x-layouts.onboarding` — same split screen; stepper above the form; primary preview panel.
- company/creator SPA → `x-layouts.spa` (Inter; `font-dashboard`)
