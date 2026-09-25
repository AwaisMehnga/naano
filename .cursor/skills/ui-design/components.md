# Components

Prefer shared components. Do not paste their class strings into pages.

## React SPA — Design system (`@/components/ds`)

| Component | Role |
| --- | --- |
| `SoftCard` | Primary surface: `rounded-3xl border border-border bg-card p-6`. Optional `title` / `action`. No expand control. |
| `MetricStat` | Large value + muted label/hint |
| `SegmentedNav` | Pill top nav; active = primary fill |
| `IconButton` | Round icon control; default outline, `size` sm/default/lg |
| `StatusPill` | Avatar + label + chevron menu trigger |
| `AvatarGroup` | Stacked avatars |
| `DateRangePills` | From → to date chips |
| `ProgressRow` | Label + high-contrast primary fill bar + `%` (value and label stay readable in one row) |
| `ActivityBarChart` | SoftCard bar chart |
| `SpendLineChart` | SoftCard line chart; `sideStats` are **horizontal** value+label pills |
| `RevenueAreaChart` | SoftCard dual-series area |
| `GlassPanel` | Frosted panel on lime-soft / media (border, no shadow) |
| `LinkedInPostPreview` | LinkedIn-style feed card for draft/live post preview |
| `LinkedInPostBuilder` | Compose + live preview; reusable creator/company |
| `LinkedInPostBuilderDialog` | Dialog wrapper with Save / Submit for post drafting |
| `NotchedCard` | Soft-canvas opportunity/deal card (`rounded-3xl bg-muted`). Neutral only — never accent-filled. Use chips for metadata. |
| `InfoChip` | Outline pill for country / match / status on cards |
| `SearchPill` | Pill search + circular primary search button |
| `FilterToolbar` | Flex row for **separate** filter pills — never one bundled filter dropdown |
| `ScheduleCard` | Thin wrapper around `NotchedCard` (prefer `NotchedCard`) |

Import DS pieces from `@/components/ds`. Import `NotchedCard` / `InfoChip` from `@/components/notched-card` and `@/components/info-chip`.

Primitives: `@/components/ui` (`Button`, `Badge`, `Input`, `Avatar`, …). Button sizes are roomy (`default` ≈ `h-11 px-6`). Badge default accent is lime.

## React SPA — Shells

- Creator: `resources/js/layouts/creator-shell.tsx` + `CreatorTopBar` (pill `SegmentedNav`, notifications, user StatusPill). **No left icon rail.**
- Company: Blade `x-layouts.spa` + sidebar; breadcrumbs from route.

Local gallery: `/components` (`APP_ENV=local`).

## Blade — `x-ui.*` (marketing / auth / onboarding)

Use these instead of duplicating classes on marketing/auth pages.

### `x-ui.button`

```php
@props([
    'variant' => 'primary', // primary|secondary|accent|inverted|ghost|link
    'size' => 'md',         // sm|md|lg
    'href' => null,
    'type' => 'submit',
])
```

Primary = black pill. Accent = lime pill. Sizes are roomy (`sm` still `h-10`, not tiny).

### `x-ui.card`

`rounded-2xl border border-border bg-card p-6` — **no shadow**.

### `x-ui.input` / `x-ui.textarea` / `x-ui.select`

Pill fields: `rounded-pill border border-input bg-card`.

### `x-ui.field`

Label + control + hint + `@error`. Errors: `text-destructive`.

### `x-ui.alert`

`info` | `success` | `danger` — semantic text colors only.

### `x-ui.kicker` / `x-ui.em` / `x-ui.favicon` / `x-ui.logo` / `x-ui.stepper`

Keep existing marketing patterns; colors stay semantic.

## Layouts

| Surface | Layout |
| --- | --- |
| Landing `/` | `x-layouts.marketing` |
| Login, register, password, verify | `x-layouts.auth` |
| Creator/company onboarding | `x-layouts.onboarding` |
| Company SPA | `x-layouts.spa` |
| Creator SPA | `CreatorShellLayout` |
