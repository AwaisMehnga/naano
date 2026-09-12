# Database design v1

Table inventory for the Naano marketplace. No migration SQL here — names, contents, and how tables relate.

Postgres. Integer cents for money (EUR). Lookup/filter fields are real columns or lookup tables. `jsonb` only for nested blobs that are not queried as first-class filters (audience mix, key-message lists, provider payloads).

Auth, roles, and onboarding already exist. This design **keeps** those tables and **splits** the 1:1 `users.user_id` company link so a person can belong to more than one company.

AI matching and agency rosters are listed at the end as later tables. Do not build them in v1.

---

## How the objects connect

```
User ──< company_members >── Company ──< Campaigns
                │                              │
                │                              └──< Collaborations >── Creator profile <── User
                │                                          │
                │                                          ├── Posts ── Tracking links ── Metrics / leads
                │                                          └── Conversation / contract / payout
                └── Wallet ── Transactions
```

Product names from `docs/build-understanding.md`:

| Object | Table | Meaning |
| --- | --- | --- |
| Company | `companies` | Brand workspace |
| Creator | `creator_profiles` | Public marketplace card |
| Offer | `creator_offers` | Bookable per-post price |
| Campaign | `campaigns` | Brief, budget, status |
| Collaboration | `collaborations` | One creator booked (or invited) on one campaign |
| Post | `posts` | Draft → review → live LinkedIn post |
| Tracking | `tracking_links` + `post_metrics` + `leads` | UTMs, engagement, pipeline |
| Payout | `payouts` + `invoices` + `wallet_transactions` | Company pays, creator earns |

---

## Already in the database

Keep as-is. Do not reinvent.

| Table | Holds |
| --- | --- |
| `users` | Login identity: name, email, password, email verification, 2FA, `hear_about`, timestamps |
| `password_reset_tokens` | Fortify reset tokens |
| `sessions` | Web sessions |
| `passkeys` | Passkey credentials |
| Spatie permission tables | `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` — `creator` / `company` on the user |
| `jobs`, `job_batches`, `failed_jobs` | Queues |
| `cache`, `cache_locks` | Cache |
| `agent_conversations`, `agent_conversation_messages` | Laravel AI SDK (brief generation, URL analysis). Not company↔creator chat |

**Change on existing product tables** (today they are 1:1 with `users`):

- `companies.user_id` (unique) → memberships. Company becomes a workspace.
- `creator_profiles.industries` (`jsonb` of strings) → `niches` + `creator_niche`.
- `companies.icps` (`jsonb`) → `company_icps` rows so a campaign can point at one ICP.

`creator_profiles` stays 1:1 with `users`. A login is either a creator or a company member, not both, matching current roles.

---

## 1. Users, companies, creators

### `companies`

Brand workspace. One row per brand, not per login.

- name, website, logo
- value proposition (onboarding brief)
- billing email, country
- Stripe customer id (when payments go live)
- onboarded_at
- timestamps

Created-by user is the first `company_members` row (`owner`), not a unique FK on this table.

### `company_members`

Who can open a company workspace. This is “multiple company accounts”.

- company
- user
- member role: `owner` \| `member`
- invited_at, joined_at
- unique (company, user)

Spatie `company` role still means “this login is on the company side”. This table says *which* companies.

### `company_invites`

Pending workspace invites for emails that do not have a company login yet.

- company
- email
- member role: `owner` \| `member`
- invited-by user
- accepted_at
- unique (company, email) while pending

When that email registers as a company user, a `company_members` row is created and `accepted_at` is set.

### `company_icps`

Structured ICPs for matching. Replaces `companies.icps` jsonb.

- company
- title, description
- sort order
- timestamps

A company keeps a small set (onboarding uses three). A campaign picks one.

### `creator_profiles`

Public marketplace card. 1:1 with `users`.

- user
- display name (can differ from login name)
- LinkedIn URL, headline, photo
- country
- bio / about
- vetting: `pending` \| `vetted` \| `rejected` (marketplace only shows vetted)
- Stripe Connect account id
- onboarded_at
- timestamps

Rate does **not** live here. Rate lives on `creator_offers` so a creator can have a single post and a bundle without jsonb `bundles`.

### `niches`

Lookup for creator categories (today’s onboarding industry list).

- name (unique)
- slug
- sort order
- active flag

### `creator_niche`

Which niches a creator claims. Filter/search join.

- creator_profile
- niche
- unique (creator, niche)

### `creator_audience_profiles`

Audience snapshot used for fit matching. One current row per creator (replace on refresh), or keep history via `captured_at`.

- creator_profile
- network (`linkedin` for v1)
- follower count
- audience mix as `jsonb`: geo, seniority, job titles, industries (distributions, not filter keys)
- captured_at
- timestamps

### `creator_offers`

The bookable product. Transparent per-post price.

- creator_profile
- network (`linkedin`)
- label (`single_post`, `bundle`)
- posts_count (1 for a single post, 3/5/… for a bundle)
- price_cents (flat EUR, not CPC/CPM)
- active flag
- timestamps

When a company books, the paid amount is **copied** onto the collaboration so later rate changes do not rewrite history.

---

## 2. Discovery and matching

Marketplace listing is a query over vetted `creator_profiles` + niches + offers + audience. No extra “marketplace” table.

### `creator_match_scores`

Cached fit of a creator to a **campaign**. Rule-based now; AI can overwrite later. Browse without a campaign uses niches, country, rate, and audience filters only — no stored score.

- campaign
- creator_profile
- fit_score (0–100)
- reasons (`jsonb` list of short explanations)
- computed_at
- unique (campaign, creator)

Used by in-campaign ranking, recommendations, and “why this creator”.

---

## 3. Campaigns

### `campaigns`

The brief and budget. Company → campaign.

- company
- company_icp (nullable until chosen)
- name
- type: `thought_leadership` \| `product` \| `hiring` \| `event` \| `other`
- objective: `awareness` \| `pipeline` \| `talent` \| `community`
- status: `draft` \| `active` \| `paused` \| `completed` \| `cancelled`
- budget_cents (creator spend cap, not the SaaS plan fee)
- value proposition / campaign goal text
- key messages (`jsonb` ordered strings — short list, not a child table)
- creator guidelines (text the creator must follow)
- start_at, end_at (nullable)
- created_by user
- timestamps

No separate `campaign_briefs` table. The campaign **is** the brief.

---

## 4. Creator–campaign workflow

This is the main join: **company → campaign → creator**.

Invites, applications, sourcing, selection, booking, and acceptance are **statuses on one row**, not five tables.

### `collaborations`

One creator on one campaign.

- campaign
- creator_profile
- creator_offer (what they were booked against; nullable until booked)
- source: `invite` \| `apply` \| `sourced`
- status: `invited` \| `applied` \| `outreach` \| `declined` \| `selected` \| `booked` \| `cancelled` \| `completed`
- booked_price_cents (snapshot)
- booked_posts_count (snapshot from the offer)
- invited_by user (nullable)
- accepted_at, booked_at, cancelled_at
- unique (campaign, creator)
- timestamps

`booked` means the company selected them and the creator accepted. Content then lives on `posts`.

### `collaboration_events`

Timeline: follow-ups, status changes, ops notes. Not chat.

- collaboration
- actor user (nullable for system)
- type: `status_change` \| `follow_up` \| `note`
- body (nullable)
- meta (`jsonb` for from/to status)
- occurred_at

---

## 5. Content and publishing

### `posts`

The deliverable. Creator-authored sponsored post for a collaboration.

- collaboration
- status: `draft` \| `in_review` \| `changes_requested` \| `approved` \| `scheduled` \| `published` \| `rejected`
- body (draft copy)
- review_note (company feedback)
- scheduled_at, published_at
- published_url
- LinkedIn post id (when tracking is attached)
- submitted_at, reviewed_at, reviewed_by user
- timestamps

One collaboration can have several posts when the offer is a bundle.

### `tracking_links`

Tracked URLs on a post or collaboration.

- post (nullable if the link is campaign-level before a draft exists)
- collaboration
- destination_url
- utm_source, utm_medium, utm_campaign, utm_content
- slug / short code if we host redirects
- timestamps

---

## 6. Analytics

Company gets the richer view; both sides read the same facts. Store **post-level** numbers and roll up in queries. Do not duplicate campaign totals as source of truth.

### `post_metrics`

Latest snapshot per post (replace on each pull).

- post (unique)
- impressions
- likes
- comments
- clicks
- qualified_clicks
- leads_count
- cpm_cents (derived, stored for display)
- captured_at

### `post_metric_snapshots`

History of the same counters (daily or per pull). Same metric columns + `captured_at`. Needed for reporting charts. Skip until the first dashboard needs a trend.

### `leads`

Attributed pipeline events (qualified click → lead).

- company
- campaign
- post (nullable)
- tracking_link (nullable)
- occurred_at
- source (`click` \| `form` \| `manual`)
- payload (`jsonb` — CRM id, email hash, etc.)

Impressions/likes/comments stay on `post_metrics`. Leads are rows because they are funnel objects, not just a counter.

---

## 7. Payments

Company spend and creator earnings are separate from the Managed plan fee (€700/mo vs €0 self-serve).

Money never uses floats. Amounts are integer cents.

### `wallets`

One wallet per company. “Campaign wallet” is not a second table: holds are `wallet_transactions` tagged with `campaign_id`. Available campaign funds = sum of holds for that campaign.

- company (unique)
- available_cents
- currency (`EUR`)
- timestamps

### `wallet_transactions`

Ledger. Append-only.

- wallet
- campaign (nullable)
- collaboration (nullable)
- type: `topup` \| `hold` \| `capture` \| `release` \| `refund` \| `payout` \| `platform_fee`
- direction: `credit` \| `debit`
- amount_cents (always positive)
- status: `pending` \| `posted` \| `failed`
- Stripe payment/transfer id
- timestamps

Booking a creator: `hold` for `booked_price_cents`. Company approves the post: `capture` + `payout`.

### `invoices`

What the company is billed (wallet top-ups and/or campaign charges).

- company
- campaign (nullable)
- number
- amount_cents
- status: `draft` \| `open` \| `paid` \| `void`
- Stripe invoice id
- issued_at, paid_at
- pdf path
- timestamps

### `payouts`

What the creator receives after post approval.

- creator_profile
- collaboration
- amount_cents
- status: `pending` \| `in_transit` \| `paid` \| `failed`
- Stripe transfer id
- paid_at
- timestamps

One booked collaboration → one payout (or one per post if we split later; v1 is one per collaboration).

### `contracts`

Platform-owned agreement for a booked collaboration (replaces the creator invoicing the brand).

- collaboration (unique)
- pdf path
- status: `generated` \| `active` \| `void`
- generated_at
- timestamps

### `company_subscriptions`

SaaS plan, not campaign spend.

- company
- plan: `self_serve` \| `managed`
- status: `active` \| `cancelled`
- Stripe subscription id
- current_period_end
- timestamps

---

## 8. Communication

Company ↔ creator chat is **not** `agent_conversations`.

### `conversations`

One thread per collaboration (campaign context included via that FK).

- collaboration (unique)
- timestamps

### `messages`

- conversation
- author user
- body
- read_at (nullable)
- timestamps

Invites, applications, campaign updates, and follow-up **alerts** use Laravel’s `notifications` table (database channel) plus email. No parallel notifications schema.

### `notification_preferences`

Optional per-user mute flags.

- user
- email_invites, email_applications, email_campaign_updates, email_messages (booleans)
- unique user

---

## 9. AI (after marketplace works)

No v1 tables required. When this ships, reuse:

| Later table | Purpose |
| --- | --- |
| `creator_match_scores` | Already above; AI writes score + reasons |
| `agent_conversations` | Already above; URL analysis and brief generation |
| `campaigns` columns | Brief/guidelines already on the campaign |

Do not add `ai_recommendations` until matching is more than a cache of scores.

---

## Out of v1

Keep these out of this design so the schema stays small:

- Brand-agency client workspaces and creator-agency rosters without creator logins (`docs/build-understanding.md`). Memberships can grow into that later (`company_members` is the hook).
- Multi-network posts beyond LinkedIn (column `network` on offers/audience is enough to extend).
- Employee advocacy, ads auctions, CPC billing.
- Storing raw LinkedIn OAuth tokens until publishing/tracking actually needs them (`creator_network_accounts` then).

---

## Status vocabularies (for later check constraints)

Use these strings consistently. Schema can enforce them with checks.

| Field | Values |
| --- | --- |
| Company member role | `owner`, `member` |
| Creator vetting | `pending`, `vetted`, `rejected` |
| Offer label | `single_post`, `bundle` |
| Campaign type | `thought_leadership`, `product`, `hiring`, `event`, `other` |
| Campaign objective | `awareness`, `pipeline`, `talent`, `community` |
| Campaign status | `draft`, `active`, `paused`, `completed`, `cancelled` |
| Collaboration source | `invite`, `apply`, `sourced` |
| Collaboration status | `invited`, `applied`, `outreach`, `declined`, `selected`, `booked`, `cancelled`, `completed` |
| Post status | `draft`, `in_review`, `changes_requested`, `approved`, `scheduled`, `published`, `rejected` |
| Wallet txn type | `topup`, `hold`, `capture`, `release`, `refund`, `payout`, `platform_fee` |
| Wallet txn direction | `credit`, `debit` |
| Invoice status | `draft`, `open`, `paid`, `void` |
| Payout status | `pending`, `in_transit`, `paid`, `failed` |
| Plan | `self_serve`, `managed` |

---

## Build order (schema later)

1. Identity: `company_members`, `company_icps`, `niches`, `creator_niche`, `creator_audience_profiles`, `creator_offers` — and stop using unique `companies.user_id` / industries jsonb.
2. Campaigns + collaborations + events.
3. Posts + tracking links.
4. Metrics + leads.
5. Wallets, invoices, payouts, contracts, subscriptions.
6. Conversations + messages.
7. Match scores (can land with discovery UI).

Auth/roles stay on `users`. Workspace access is `company_members`. Marketplace access is vetted `creator_profiles` plus active `creator_offers`.
