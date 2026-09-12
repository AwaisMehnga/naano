# Build understanding

Clone of [naano.com](https://naano.com): a **B2B LinkedIn creator marketplace**. Companies book vetted creators for sponsored posts at a **fixed price per post**. The platform handles match, brief, publish, pay, and track. Not an ads auction. Not employee advocacy. Not a creator directory you email offline.

Source snapshot: naano.com public site, 2026-09-12. EN primary, FR secondary. Founded Paris 2025.

## Job

B2B buyers trust people more than brands. Personal LinkedIn posts reach more than company pages. Vertical fit beats follower count. Most B2B creators are salaried experts who will not invoice a brand. Naano is the ops layer that makes the 20th collaboration as cheap as the first.

## Actors

- **Company** — B2B team (SaaS, GTM, demand-gen). Finds creators, briefs, books, tracks pipeline.
- **Creator** — B2B specialist on LinkedIn (~1k–500k followers). Sets a public per-post rate, writes in their own voice, gets paid without invoicing.
- **Brand agency** — one workspace per client, budgets, campaigns, reporting.
- **Creator agency** — roster import, rates, collabs. Creators do not need Naano accounts.
- **Naano ops** — Managed plan only. Sources, briefs, runs, reports.

Auth surfaces: `/login`, `/register`, `/dashboard`. Marketing: `/` companies, `/creators`, `/agencies`.

## Core objects

| Object | Meaning |
| --- | --- |
| **Offer** | Creator-set deliverable + flat € price, visible before book. No CPC/CPM/CPL billing. |
| **Campaign** | Brand goal, ICP, messages, guidelines, tracking links. AI-assisted brief. |
| **Collaboration** | Booked offer inside a campaign. States: draft → scheduled → live. Brand reviews content before publish. |
| **Post** | Sponsored post from the creator's personal account (LinkedIn first; also X, YouTube). |
| **Tracking** | Per-post UTMs: impressions, clicks, leads, attributed pipeline. |
| **Payout** | After brand approval, Stripe Connect pays the creator. Platform owns contract/invoice/payout. |

Median transacted prices (Naano Index, n=239, Jun–Aug 2026): ~€84 (<5k), ~€180 (5–10k), ~€312 (10–25k). Floor marketed from €20/post. Creators quote ~€500 avg deal, paid within ~24h. No exclusivity.

## Company loop

1. **Match** — browse vetted creators; audience-fit score vs ICP/vertical, not vanity reach.
2. **Brief** — objectives, key messages, creator guidelines, tracking links. Minutes, not a deck.
3. **Collaborate** — book, review draft, schedule, go live. Median booking → publish ~8 days.
4. **Track** — views, clicks, leads, pipeline per creator and per post.
5. **Pay** — approve content → auto payout. Brand never chases invoices.

Self-serve or book a campaign call (`/book`). Launch in days.

## Creator loop

Join free. Pick brand deals or **bring your own** (platform still handles contract + payout; extra bonus possible). Post in own voice. Brand approves. Paid to account (SEPA). Dashboard: opportunities, delivery, performance. No admin, no exclusivity.

## Plans (platform fee ≠ creator spend)

| Plan | Fee | Who does the work |
| --- | --- | --- |
| **Self-Serve** | €0/mo | Company books published offers. Help center + email. |
| **Managed** | €700/mo | Naano team: strategy, sourcing, briefs, launch, reporting. |

Month-to-month, cancel anytime. Campaign spend is always the booked post prices, separate from the plan fee.

## Product UI to clone

**Marketing:** hero + proof, creator grid, 5-step how-it-works, case studies, pricing, FAQ, EN/FR, CTAs to register or book.

**App (companies):** creator search/fit, campaign brief, collab pipeline, attribution dashboard, payments.

**App (creators):** media kit/rate, deal inbox, delivery, payouts, performance.

**App (agencies):** brand-agency client workspaces **or** creator-agency roster without creator logins.

## Scope for this repo

Build that marketplace end to end. LinkedIn is the primary network. Success = book a post, approve it, pay the creator, attribute clicks/leads/pipeline. Keep pricing transparent and per-post.

**Not this product:** LinkedIn Ads, Thought Leader Ads media buy, consumer IG/TikTok influencer DBs, employee advocacy, discovery-only directories (Favikon-style), creator storefronts (Passionfroot-style).

## Vocabulary

Use **creator** not influencer. **Offer / per-post price** not CPC. **Audience fit** not follower count. **Creator-led growth** = practitioners your buyers already follow, seeding a long B2B funnel.
