---
name: ui-design
description: >-
  Enforces Naano's only product UI: roomy bento, clean minimal modern soft-canvas
  design with semantic theme tokens from app.css. Use when creating or editing
  React/Blade UI, dashboards, SPA pages, layouts, SoftCard, charts, buttons,
  forms, className, Tailwind, marketing, auth, onboarding, or app.css.
---

# Naano UI design

This skill is mandatory. There is **one** visual language: roomy bento on a soft canvas, black ink, white cards, lime accent. Do not invent a second look.

Theme control is centralized: change colors only in `resources/css/app.css`. Every surface must use semantic classes so the whole product restyles from that file.

Also follow [semantic-theme](../semantic-theme/SKILL.md). Read [tokens.md](tokens.md) and [components.md](components.md) before writing markup.

## Design principles (non-negotiable)

1. **Soft canvas** — Page background is `bg-background`. Content floats as white `bg-card` surfaces. Never a flat white full-bleed app chrome.
2. **Bento** — Dashboards use a spacious grid of SoftCards / chart cards with generous gaps (`gap-5`+). Unequal spans are fine; cramped equal tiles are not.
3. **Roomy** — Prefer larger padding (`p-6`), taller pills (`h-11` default buttons), wider nav tabs (`px-6 py-2.5`), and breathing room between blocks (`gap-6`–`gap-8`). Compact SaaS density is wrong.
4. **Clean / minimal** — One job per section. No decorative shadows. No expand/affordance clutter. No fake demo chrome (Shared avatars, Add widget, Pro upsell) unless the product feature is real.
5. **Modern** — Pill controls (`rounded-pill`), large card radii (`rounded-3xl` SoftCard), Inter only, lime callouts sparingly.
6. **Semantic colors only** — Never hardcode hex/rgb/oklch or palette utilities in TSX/Blade. See Forbidden below.

## Before any markup

1. Prefer existing components in `resources/js/components/ds` and `resources/js/components/ui` (SPA) or Blade `x-ui.*` (marketing/auth).
2. If a color is missing, add a token in `resources/css/app.css` first, then use the semantic class.
3. Copy is sentence case, active verbs, Naano product language — not generic “Product Sales Performance” mock copy.

## Hard rules

1. **Colors** — Only semantic Tailwind classes mapped from `app.css` `@theme` / `:root`. Forbidden in Blade/TSX/other CSS: `bg-neutral-*`, `text-white`, `text-black`, `text-red-600`, `bg-green-*`, arbitrary colors (`bg-[#…]`, `text-[oklch(…)]`), new `--color-*` or `@theme` outside `app.css`, `dark:` on dashboards/marketing/auth/onboarding.
2. **Theme file** — `resources/css/app.css` is the only place colors live. Restyle the product by editing `:root` there.
3. **No shadows** — Do not add `shadow-*`, `shadow-[…]`, or soft elevation. Separate surfaces with `border-border` and canvas vs card contrast. `--shadow-soft` stays `none`.
4. **Reuse DS** — SPA metrics, charts, nav, pills, progress: use `SoftCard`, `MetricStat`, `SegmentedNav`, `IconButton`, `ProgressRow`, `DateRangePills`, `StatusPill`, `AvatarGroup`, chart components from `@/components/ds`. Do not re-implement their class strings.
5. **Roomy buttons** — Default Button is tall and padded (`h-11 px-6`). Icon buttons default `size-11`. Do not shrink back to compact sizes unless the control is truly dense (tables).
6. **Stats in a row** — Inline stats are one line: `value` + `label` in a horizontal pill row — never stacked “1 / Live posts” columns.
7. **Progress contrast** — `ProgressRow` fill is `bg-primary` on a light track (`bg-card` / `bg-muted`). Do not use low-contrast fills.
8. **Creator shell** — `CreatorShellLayout`: top pill nav only, no left icon rail. Soft canvas + roomy main padding (`px-6` / `lg:px-8`, `pb-10`).
9. **Company shell** — `CompanyShellLayout`: top pill nav only (same soft canvas as creator), no left icon rail.
10. **Light mode** — Dashboards stay light. Never `class="dark"` on SPA, marketing, auth, or onboarding layouts.

## Layout recipes

### Creator dashboard (canonical bento)

```
Header: title + short support line | range filters (7/30/90) + DateRangePills
Metric row: SoftCard + MetricStat (4-up)
Bento grid: SoftCard / ActivityBarChart / RevenueAreaChart / SpendLineChart / ProgressRow cards
```

- Page title: `text-heading font-medium tracking-tight`
- Support: `text-sm text-muted-foreground`
- Grid: `gap-5`, wide breakpoints with `xl:col-span-*`
- Real API data and Naano copy only

### Soft surface

```tsx
<SoftCard title="Audience">…</SoftCard>
// → rounded-3xl border border-border bg-card p-6
```

### Pill nav

```tsx
<SegmentedNav items={…} value={…} onChange={…} />
// → bordered white pill track; active = bg-primary text-primary-foreground
```

## Typography & radius

| Use | Class |
| --- | --- |
| Page title | `text-heading` |
| Section / metric value | `text-title` or MetricStat |
| Body | `text-body` / default |
| Buttons / pills | `rounded-pill` |
| Soft cards | `rounded-3xl` |
| Smaller cards | `rounded-2xl` |

Font: Inter via `font-sans` / `font-dashboard` only.

## Charts

- Series colors: `var(--chart-1)` … `var(--chart-5)` or `chart-*` utilities — never raw lime hex in TSX.
- Callouts: `Badge variant="accent"`.
- Keep charts inside SoftCard; roomy chart height (`h-44`–`h-48`).

## Marketing / auth / onboarding (Blade)

Still use `x-layouts.marketing`, `x-layouts.auth`, `x-layouts.onboarding` and Blade `x-ui.*` from [components.md](components.md). Same semantic tokens, same lime system, no shadows. Auth split: form + `bg-primary` panel. Do not put the homepage in the auth layout.

## Do not

- Add a second visual system (purple gradients, cream+serif, newspaper, glassmorphism soup).
- Use blue as brand primary (primary is black; accent is lime).
- Paste mock dashboard chrome or placeholder sales metrics when real APIs exist.
- Reintroduce left creator icon rail or SoftCard expand buttons.
- Add fonts beyond Inter for product surfaces.
- Duplicate DS class strings instead of importing components.

## Checklist before finishing UI work

- [ ] Only semantic color classes; theme editable from `app.css`
- [ ] No shadows
- [ ] Roomy spacing and pill/button sizes
- [ ] SoftCards / DS components reused
- [ ] Bento / soft-canvas layout (not compact table-first chrome)
- [ ] Real copy and real data where available
