---
name: ui-design
description: >-
  Enforces Naano marketing, auth, onboarding, and Blade UI. Use when creating or
  editing Blade views, layouts, landing, login, register, password, verification,
  onboarding, buttons, forms, GSAP, Tailwind classes, or app.css tokens.
---

# Naano UI design

This skill is mandatory. Visual language is the lime system: soft grey canvas, black ink CTAs, white soft cards, lime accent (`#C7F33C`). Content and IA follow naano.com. Do not invent a second look.

## Before any markup

1. Read [tokens.md](tokens.md) and [components.md](components.md).
2. Use only those layouts, components, tokens, and recipes.
3. If a needed color does not exist, add a token in `resources/css/app.css` first, then use the semantic class. Never invent a one-off.

## Hard rules

1. Colors: only semantic classes from `app.css`. Forbidden: palette utilities (`bg-neutral-*`, `text-white`, `text-black`, `text-red-600`), arbitrary colors (`bg-[#171818]`, `text-[oklch(...)]`), new `--color-*` or `@theme` blocks outside `app.css`, `dark:` on dashboards.
2. Every button, input, textarea, select, field error, card, alert, logo, stepper, kicker, emphasis, and nav CTA MUST use the Blade components in `components.md`. Duplicating their class strings in a page is a skill violation.
3. Marketing pages use `x-layouts.marketing`. Auth pages use `x-layouts.auth`. Onboarding pages use `x-layouts.onboarding`. Never put the homepage in the auth split layout.
4. Copy: sentence case, active verbs, one job per control. Primary CTA labels: “Sign in”, “Create account”, “Continue”, “Verify”, “Analyze website”, “Go to workspace”, “Get started”, “Book creators”, “Get booked”. Errors name the field and the fix.
5. Motion: GSAP only on landing section reveals and the first auth/onboarding paint. `prefers-reduced-motion: reduce` → opacity only. Never animate login submit, keyboard, or repeated controls. Duration 180–400ms, ease `cubic-bezier(0.23, 1, 0.32, 1)`. Enter from `opacity: 0; y: 16` (not `scale(0)`).
6. Spacing: `gap-2` inside fields, `gap-5` inside forms, section `py-24` / `px-6`, content `max-w-6xl mx-auto`. Buttons `rounded-pill`. Soft cards `rounded-2xl` with soft shadow. No full-width hairline rules between landing sections.

## Marketing layout

Copy this structure. Do not replace it with a centered SaaS hero.

- Soft grey canvas, space between bands (no full-width hairline rules), soft white cards.
- Masthead: logo left, text links in ink, one black (or lime accent) `Get started` on the right. No backdrop blur.
- Display headline: large Inter sans; optional emphasis via `x-ui.em`.
- Body under the hero: ~70ch, `text-lg` or `text-xl`, ink not muted for the lead sentence; muted for supporting lines.
- Numbered process uses `01` `02` `03` in muted tracking, then a title. Numbers only when the content is a real sequence.
- Two-up split (companies / creators, or two pricing paths): equal columns with gap, not a divider line.
- Stats are a four-up grid: big number, small label. No hairline grid.
- FAQ is a stacked list with spacing, not tiles or rules.
- Footer is four link columns. No social icon soup. No full-width trim line.

Landing section order (naano.com IA):

1. Hero + product card
2. Trusted by
3. Quote
4. Creators (reach / fit)
5. How campaigns run (`#how-it-works`)
6. Companies vs creators (`#companies` `#creators`)
7. Proof stats
8. Pricing (`#pricing`)
9. FAQ
10. Closing CTA

## Layouts

- `/` landing → `x-layouts.marketing`
- login, register, password, verify, 2FA, confirm → `x-layouts.auth`
- creator/company onboarding → `x-layouts.onboarding`
- company/creator SPA → `x-layouts.spa` (Inter; `font-dashboard`)
- local component gallery → `/components` (`APP_ENV=local` only)

## Dashboards (company and creator SPA)

- Layout: `x-layouts.spa` + `font-dashboard` (Inter). Full-width main. Breadcrumbs in the header from the route.
- React UI: `resources/js/components/ui` + `resources/js/components/ds`. Pill buttons, soft cards, lime accents, no `dark:`.
- Colors stay the semantic tokens in `app.css`. Chart series use `chart-1` … `chart-5` or `var(--chart-1)`.
- Preview kit: local `/components` gallery.

## Do not

- Restyle dashboard React pages unless the task is explicitly the SPA.
- Add fonts beyond Inter for product surfaces.
- Add `class="dark"` to marketing, auth, onboarding, or SPA layouts.
- Call `redirect()->intended()` after login or email verify. Use `HomeRedirect::afterAuth()`.
- Use blue as the brand primary. Primary is black; accent is lime.
- Clone newspaper mastheads or pixel fonts. Keep Naano copy.
