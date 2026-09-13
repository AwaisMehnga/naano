# Components

Use these Blade components. Do not paste their class strings into pages.

## `x-ui.button`

```php
@props([
    'variant' => 'primary', // primary|secondary|inverted|ghost|link
    'size' => 'md',         // md|lg
    'href' => null,
    'type' => 'submit',
])
```

If `$href` is set, render `<a>`. Else `<button type="{{ $type }}">`. Merge `$attributes`. Pass `data-test` through the attribute bag.

- Primary: `inline-flex items-center justify-center rounded-sm bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground hover:opacity-90 disabled:opacity-50`
- Secondary: `bg-card text-foreground border border-border` (white paper fill, ink text)
- Inverted: `bg-primary-foreground text-primary` (paper fill, blue text — on `bg-primary` bands)
- Ghost: `bg-transparent text-foreground hover:bg-muted`
- Link: `bg-transparent text-primary underline-offset-4 hover:underline px-0`
- Size `lg`: `px-5 py-3`

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

Italic Instrument Serif inside a display headline: `font-serif italic font-normal`. One emphasized word per headline.

## `x-ui.input` / `x-ui.textarea` / `x-ui.select`

`w-full rounded-md border border-input bg-card px-3 py-2 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30`

## `x-ui.field`

```php
@props(['label', 'name' => null, 'hint' => null])
```

Slot is the control. Label: `text-sm font-medium`. Hint: `text-sm text-muted-foreground`. Error: `text-sm text-destructive`. If `$name` is set, render `@error($name)` and a `data-error-for="{{ $name }}"` element for AJAX field errors.

## `x-ui.card`

Default: `block rounded-lg border border-border bg-card p-6 text-card-foreground`

`flush` (bool): drop padding for split lists. `href` (string|null): render as a block link. No drop shadow.

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

- `x-layouts.marketing` — paper masthead, hairline footer columns, landing Vite entry. No backdrop blur.
- `x-layouts.auth` — paper masthead, two columns, no divider line. Form `max-w-sm` (`max-w-xl` when `wide`). `kicker` + `heading` slot for italic display. Panel slot is editorial copy, not a muted slab.
- `x-layouts.onboarding` — auth chrome, `wide`, stepper above heading, onboarding.js.
