# Feature APIs v1

JSON APIs for the company and creator SPAs. Mapped from [naano.com](https://naano.com/) (home, `/creators`, `/agencies`, pricing, creator-led growth playbook, 2026-09-12) plus `[main-features.md](main-features.md)` and `[database-design-v1.md](database-design-v1.md)`.

This file is the endpoint list. It does not implement routes.

Auth, onboarding, and `GET /api/user` already exist on web / Fortify. Dashboard work after onboarding lives under `/api/company` and `/api/creator`.

---

## Conventions


| Rule            | Detail                                                                                                                          |
| --------------- | ------------------------------------------------------------------------------------------------------------------------------- |
| Prefix          | `/api` (web middleware, session cookie). Named `api.*`.                                                                         |
| Company         | `/api/company/…` — `auth`, `verified`, `role:company`, `onboarded`                                                              |
| Creator         | `/api/creator/…` — `auth`, `verified`, `role:creator`, `onboarded`                                                              |
| Shared          | `/api/…` — `auth` (and `verified` where noted)                                                                                  |
| Response        | `AjaxResponse`: `{ status, message, data }`                                                                                     |
| Money           | Integer cents, EUR                                                                                                              |
| IDs             | Numeric route params. No DB foreign keys; services load related rows                                                            |
| Current company | For users in several workspaces: `X-Company-Id` header, or the sole membership. Every company route is scoped to that workspace |
| Controllers     | `[Controller::class, 'method']` only in `routes/api.php`. Work in services                                                      |
| Stripe webhook  | `POST /api/stripe/webhook` — Stripe signature only, CSRF-exempt, not role-scoped                                                |


**Already live (do not reinvent)**


| Method | Path                | Name               |
| ------ | ------------------- | ------------------ |
| GET    | `/api/user`         | `api.user`         |
| GET    | `/api/company/ping` | `api.company.ping` |
| GET    | `/api/creator/ping` | `api.creator.ping` |


Web: register, login, email code, company/creator onboarding, Fortify settings. Those stay web until a later SPA auth pass.

**Out of this list:** brand-agency client portfolios and creator-agency rosters without logins (`/agencies`). `company_members` is the hook later.

---



## Product flows (from naano.com)



### Company (home + pricing)

1. **Match** — browse vetted creators; fit % vs buyers/ICP, not follower count.
2. **Brief** — AI-assisted campaign: objectives, key messages, creator guidelines, tracking links.
3. **Collaborate** — collab pipeline: draft → scheduled → live. Brand reviews before publish.
4. **Track** — views, clicks, qualified clicks (UTM + ≥30s on-site), leads, attributed pipeline per post and creator.
5. **Pay** — book at the creator’s fixed price (company wallet hold) → approve draft (publish permission only) → creator submits the live LinkedIn URL → platform credits the creator wallet → creator withdraws via Stripe Connect (min €100). Brand never invoices the creator. Stripe is the processor; Naano is the counterparty.

Self-serve (€0/mo) books published offers. Managed (€700/mo) is a separate Stripe subscription. Campaign spend is always booked post prices, not CPC.

### Creator (`/creators`)

1. Join free, set a public per-post rate / media kit.
2. **Opportunities** — accept brand deals, or bring your own (platform still does contract + payout; bonus possible).
3. **Delivery** — write in own voice, submit draft, publish on LinkedIn.
4. **Pay** — earnings hit the creator wallet after the live post URL is submitted (not on draft approval). Withdrawals from €100 via Stripe Connect (SEPA to bank). No creator-to-brand invoice. No exclusivity.
5. **Performance** — views, clicks, engagement on one dashboard.

Success for this repo: book a post (wallet hold), approve the draft, submit the live URL, credit the creator, withdraw via Stripe, attribute clicks/leads/pipeline.

---



## 1. Users and profiles



### Shared


| Method | Path          | What it does                                                                                   |
| ------ | ------------- | ---------------------------------------------------------------------------------------------- |
| GET    | `/api/user`   | Current user, role/side, onboarded, current company id, avatar (creator photo or company logo) |
| GET    | `/api/niches` | Active niche lookup (filters + creator profile)                                                |




### Company

Owner-gated money: members can update profile, ICPs, and targeting. Only **owners** invite/change/remove members, change `billing_email`, and (later) top up, book, and manage billing. Last owner cannot be demoted or removed.

Routes use `apiResource` / `apiSingleton`. Workspace switch is `PATCH /api/company/workspaces/{workspace}`. Audience refresh is `POST /api/creator/audience`.


| Method | Path                                  | What it does                                                                                                                                                     |
| ------ | ------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| GET    | `/api/company/workspaces`             | Companies this login belongs to                                                                                                                                  |
| PATCH  | `/api/company/workspaces/{workspace}` | Set current workspace                                                                                                                                            |
| GET    | `/api/company/profile`                | Current company profile (`can_manage_money`)                                                                                                                     |
| PATCH  | `/api/company/profile`                | Name, website, logo, country, value proposition. `billing_email` owner-only. `remove_logo` clears the logo                                                       |
| GET    | `/api/company/audience`               | Targeting jsonb + lookups (industries, regions, seniority, sizes, titles)                                                                                        |
| PATCH  | `/api/company/audience`               | Save targeting                                                                                                                                                   |
| GET    | `/api/company/icps`                   | Structured ICPs (seeds from `companies.icps` if empty)                                                                                                           |
| POST   | `/api/company/icps`                   | Add ICP                                                                                                                                                          |
| PATCH  | `/api/company/icps/{icp}`             | Update ICP                                                                                                                                                       |
| DELETE | `/api/company/icps/{icp}`             | Soft-delete ICP                                                                                                                                                  |
| GET    | `/api/company/members`                | Workspace members plus pending email invites                                                                                                                     |
| POST   | `/api/company/members`                | Invite by email. Existing company-role users auto-join; unknown emails get an invite mail and a pending `company_invites` row. Creators are rejected. Owner-only |
| PATCH  | `/api/company/members/{member}`       | Change role (`owner` / `member`). Owner-only                                                                                                                     |
| DELETE | `/api/company/members/{member}`       | Remove member. Owner-only                                                                                                                                        |




### Creator

No creator team APIs. Creators are 1:1 with a user.


| Method | Path                          | What it does                                                                      |
| ------ | ----------------------------- | --------------------------------------------------------------------------------- |
| GET    | `/api/creator/profile`        | Media kit: display name, LinkedIn, headline, photo, bio, country, vetting, niches |
| PATCH  | `/api/creator/profile`        | Update card (not rate — that is offers). `remove_photo` clears the photo          |
| PUT    | `/api/creator/niches`         | Replace claimed niches                                                            |
| GET    | `/api/creator/audience`       | Latest audience snapshot                                                          |
| POST   | `/api/creator/audience`       | Persist/refresh current mix (no LinkedIn job in v1)                               |
| GET    | `/api/creator/billing`        | `{ stripe_connect_id, payouts_enabled: false, bank_summary: null }`               |
| DELETE | `/api/creator/account`        | Password confirm, logout, delete user                                             |
| GET    | `/api/creator/offers`         | Public rates (single post + bundles)                                              |
| POST   | `/api/creator/offers`         | Create offer (`single_post` / `bundle`, `posts_count`, `price_cents`)             |
| PATCH  | `/api/creator/offers/{offer}` | Update price / active                                                             |
| DELETE | `/api/creator/offers/{offer}` | Soft-delete offer                                                                 |


Marketplace listing uses **vetted + onboarded** creators. Listed price is the cheapest **active** offer, else `creator_profiles.price_cents`. Offers CRUD is later — onboarding still stores the rate on the profile.

---



## 2. Creator discovery and matching

Company-only. Core **Company → Creator**.

Browse without a campaign uses niches, country, rate, followers. Fit scores are stored per **campaign** (`creator_match_scores`).

The two GETs below are **live**. Campaign recommendation and fit endpoints wait until campaigns exist.


| Method | Path                                                              | What it does                                                                                                                                                                                                          |
| ------ | ----------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| GET    | `/api/company/creators`                                           | **Live.** Search/filter vetted onboarded creators. Query: `q`, `niche_id`, `country`, `min_price_cents`, `max_price_cents`, `min_followers`, `max_followers`, `page`. Paginated 24. `campaign_id` (sort by fit) later |
| GET    | `/api/company/creators/{creatorProfile}`                          | **Live.** Public card: profile, niches, audience, active offers. `recent_metrics` is always `[]` until posts exist. Pending/rejected 404                                                                              |
| GET    | `/api/company/campaigns/{campaign}/recommendations`               | Later. Ranked creators for this campaign (cached scores)                                                                                                                                                              |
| POST   | `/api/company/campaigns/{campaign}/recommendations`               | Later. Recompute fit scores (rule-based now; AI later)                                                                                                                                                                |
| GET    | `/api/company/campaigns/{campaign}/creators/{creatorProfile}/fit` | Later. Score + reasons (“why this creator”, Fit 92% on the marketing site)                                                                                                                                            |


Selecting a creator for a campaign is **3. / 4.** (invite or book), not a separate table.

---



## 3. Campaigns

Company owns the brief. The campaign **is** the brief (no `campaign_briefs` table).


| Method | Path                                         | What it does                                                               |
| ------ | -------------------------------------------- | -------------------------------------------------------------------------- |
| GET    | `/api/company/campaigns`                     | List. Query: `status`                                                      |
| POST   | `/api/company/campaigns`                     | Create draft: name, type, objective, budget_cents, company_icp_id          |
| GET    | `/api/company/campaigns/{campaign}`          | Full brief + collab counts                                                 |
| PATCH  | `/api/company/campaigns/{campaign}`          | Update goal, key_messages, guidelines, dates, budget, type, objective, ICP |
| POST   | `/api/company/campaigns/{campaign}/launch`   | `draft` → `active`                                                         |
| POST   | `/api/company/campaigns/{campaign}/pause`    | `active` → `paused`                                                        |
| POST   | `/api/company/campaigns/{campaign}/complete` | `active`/`paused` → `completed`                                            |
| POST   | `/api/company/campaigns/{campaign}/cancel`   | → `cancelled`                                                              |


Creator sees a campaign only through an opportunity or a collaboration (section 4).

---



## 4. Creator–campaign workflow

Most important loop after profiles. One `collaborations` row per creator per campaign. Invites, applications, sourcing, booking, and acceptance are **status + source**, not extra tables.

```
invite / apply / sourced
        ↓
selected → booked (company wallet hold + contract) → posts
        ↓
declined / cancelled / completed
```



### Company


| Method | Path                                                     | What it does                                                                                                                                           |
| ------ | -------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------ |
| GET    | `/api/company/campaigns/{campaign}/collaborations`       | Pipeline. Query: `status`                                                                                                                              |
| GET    | `/api/company/collaborations`                            | All collabs for current company                                                                                                                        |
| GET    | `/api/company/collaborations/{collaboration}`            | Detail + events + posts                                                                                                                                |
| POST   | `/api/company/campaigns/{campaign}/invites`              | Invite creator (`source=invite`, `status=invited`)                                                                                                     |
| POST   | `/api/company/campaigns/{campaign}/sourcing`             | Add sourced creator (`source=sourced`, `status=outreach`) — Managed-style                                                                              |
| POST   | `/api/company/collaborations/{collaboration}/select`     | `invited`/`applied`/`outreach` → `selected`                                                                                                            |
| POST   | `/api/company/collaborations/{collaboration}/book`       | → `booked` only if company wallet `available_cents` ≥ offer price. Snapshot price, ledger `hold`, generate contract. 422 + Checkout URL if underfunded |
| POST   | `/api/company/collaborations/{collaboration}/cancel`     | → `cancelled`, ledger `release` of unused hold                                                                                                         |
| POST   | `/api/company/collaborations/{collaboration}/follow-ups` | Timeline event (`follow_up` / `note`)                                                                                                                  |
| GET    | `/api/company/collaborations/{collaboration}/events`     | Status + ops timeline                                                                                                                                  |




### Creator


| Method | Path                                                  | What it does                                                                                                               |
| ------ | ----------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------- |
| GET    | `/api/creator/opportunities`                          | Related active campaigns. Query: `q`, `limit`. Hard-filters by ICP niches/tags, AI `fit_score` ≥ 30, exclude existing collab. Payload includes company, location, match_score, audience_relevance, deadline |
| GET    | `/api/creator/opportunities/{campaign}`               | Campaign brief the creator is allowed to see (same score fields)                                                                                                                          |
| POST   | `/api/creator/opportunities/{campaign}/apply`         | `source=apply`, `status=applied` — company sees it under Collaborations `invitations_received`                                                                                            |
| GET    | `/api/creator/collaborations`                         | Deal inbox. Query: `status`, `q`, `source`                                                                                 |
| GET    | `/api/creator/collaborations/{collaboration}`         | Deal + brief + posts                                                                                                       |
| POST   | `/api/creator/collaborations/{collaboration}/accept`  | Invite → `selected` or `booked` (if company already selected; booking still company-side if wallet required)               |
| POST   | `/api/creator/collaborations/{collaboration}/decline` | → `declined`                                                                                                               |
| POST   | `/api/creator/deals/external`                         | Bring-your-own: creator supplies brand/campaign payload; ops/company completes booking. Extra bonus is a payout line later |


**Accept semantics:** creator accept on an `invited` row → `selected`. Company `book` still places the wallet hold and contract so the brand’s funds are reserved first. Money does not leave the company wallet until the live post URL is submitted (§5 / §7).

---



## 5. Content and publishing

**Creator → Campaign → Company.** Draft in creator voice → brand review → schedule/live. Tracking links on the brief and on the post.

### Creator


| Method | Path                                                | What it does                                                                                                                                                                           |
| ------ | --------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| GET    | `/api/creator/collaborations/{collaboration}/posts` | Posts for this deal (bundles = several)                                                                                                                                                |
| POST   | `/api/creator/collaborations/{collaboration}/posts` | Create draft                                                                                                                                                                           |
| PATCH  | `/api/creator/posts/{post}`                         | Edit draft / changes_requested body                                                                                                                                                    |
| POST   | `/api/creator/posts/{post}/submit`                  | → `in_review`                                                                                                                                                                          |
| POST   | `/api/creator/posts/{post}/schedule`                | After `approved`, set `scheduled_at` → `scheduled`                                                                                                                                     |
| POST   | `/api/creator/posts/{post}/publish`                 | Attach canonical `published_url` + LinkedIn post id → `published`. This is payment proof: capture the company hold and credit the creator wallet (§7). Draft approval does not do this |




### Company


| Method | Path                                                | What it does                                                                                           |
| ------ | --------------------------------------------------- | ------------------------------------------------------------------------------------------------------ |
| GET    | `/api/company/collaborations/{collaboration}/posts` | Review queue for a collab                                                                              |
| GET    | `/api/company/posts/{post}`                         | Draft + guidelines + tracking links                                                                    |
| POST   | `/api/company/posts/{post}/approve`                 | → `approved`. Lets the creator publish. Does **not** capture the hold, credit earnings, or call Stripe |
| POST   | `/api/company/posts/{post}/changes`                 | → `changes_requested` + `review_note`                                                                  |
| POST   | `/api/company/posts/{post}/reject`                  | → `rejected`                                                                                           |
| GET    | `/api/company/campaigns/{campaign}/tracking-links`  | Campaign/collab UTMs                                                                                   |
| POST   | `/api/company/campaigns/{campaign}/tracking-links`  | Create destination + UTM (+ optional `collaboration_id`)                                               |
| PATCH  | `/api/company/tracking-links/{trackingLink}`        | Update URL / UTMs                                                                                      |
| DELETE | `/api/company/tracking-links/{trackingLink}`        | Soft-delete                                                                                            |


Public click (not SPA, still needed for tracking):


| Method | Path        | What it does                                                            |
| ------ | ----------- | ----------------------------------------------------------------------- |
| GET    | `/t/{slug}` | Redirect to destination, record click (later: qualified if dwell ≥ 30s) |


---



## 6. Campaign analytics

Same facts on both sides; company gets rollups (campaign, creator, post, pipeline). Source of truth is `post_metrics` + `leads`, not duplicated campaign totals.

### Company


| Method | Path                                                   | What it does                                                             |
| ------ | ------------------------------------------------------ | ------------------------------------------------------------------------ |
| GET    | `/api/company/analytics/overview`                      | Workspace: impressions, clicks, qualified clicks, leads, spend, pipeline |
| GET    | `/api/company/campaigns/{campaign}/analytics`          | Campaign performance                                                     |
| GET    | `/api/company/campaigns/{campaign}/analytics/creators` | Per-creator breakdown                                                    |
| GET    | `/api/company/posts/{post}/metrics`                    | Latest snapshot                                                          |
| GET    | `/api/company/campaigns/{campaign}/leads`              | Attributed leads                                                         |
| POST   | `/api/company/campaigns/{campaign}/leads`              | Manual lead (`source=manual`)                                            |
| GET    | `/api/company/reports/campaigns/{campaign}`            | Export payload for reporting (JSON; CSV later)                           |




### Creator


| Method | Path                                                  | What it does                                  |
| ------ | ----------------------------------------------------- | --------------------------------------------- |
| GET    | `/api/creator/analytics/overview`                     | Own impressions (reach), engagement, public posts, followers, clicks, earnings |
| GET    | `/api/creator/collaborations/{collaboration}/metrics` | Deal-level rollup of posts                    |
| GET    | `/api/creator/posts/{post}/metrics`                   | Same snapshot the company sees                |


Ingest (system/job, not SPA): sync LinkedIn impressions/likes/comments onto `post_metrics`. Not a public creator/company route in v1.

---



## 7. Payments (Naano flow + Stripe)

**Company pays → creator earns.** The unit is a **fixed fee per post** set by the creator (visible before book). Tracking is measurement, not pricing. The platform is the counterparty: the brand never pays the creator directly, and the creator never invoices the brand.

Processor is **Stripe**. Campaign spend and the Managed plan are separate Stripe products.

Sources: [How to pay B2B creators](https://naano.com/blog/how-to-pay-b2b-creators) (updated 2026-08-20), pricing (Self-Serve €0 / Managed €700), `/creators` (SEPA, no invoice).

### Money movement

```
Company Stripe Checkout (wallet top-up)
        ↓
Company wallet available_cents
        ↓
Book collaboration → hold booked_price_cents (no Stripe call)
        ↓
Approve draft → publish permission only (no money, no Stripe)
        ↓
Creator submits live LinkedIn URL → capture company hold
        → credit creator available wallet (net = booked price; creator keeps 100%)
        ↓
Creator requests withdrawal when available ≥ €100
        AND Stripe Connect payouts_enabled
        ↓
Stripe Transfer to the creator Connect account (SEPA)
```

Do **not** pay on draft approval. Do **not** auto-transfer on publish. Credit the creator ledger on live URL; cash out only on a withdrawal request.

v1 credit: one collaboration → one earnings credit when **all** posts on that collab are `published`. Split per post later if bundles need it.

Cancel a booked collab before publish → `release` the hold back to company available. After capture, refunds are a separate `refund` ledger + Stripe Refund (ops, not a creator button).

### Stripe objects


| Role                                   | Stripe                                                       | Stored on                                      |
| -------------------------------------- | ------------------------------------------------------------ | ---------------------------------------------- |
| Company buyer                          | Customer                                                     | `companies.stripe_customer_id`                 |
| Company campaign funds                 | Checkout Session (mode `payment`) or PaymentIntent, EUR      | `wallet_transactions.stripe_id`                |
| Company tax invoice for top-ups / plan | Invoice (or Checkout invoice creation)                       | `invoices.stripe_invoice_id`                   |
| Managed €700/mo                        | Subscription + Checkout mode `subscription` + Billing Portal | `company_subscriptions.stripe_subscription_id` |
| Creator payee                          | Connect Express account                                      | `creator_profiles.stripe_connect_id`           |
| Creator cash-out                       | Transfer (platform balance → Connect)                        | `payouts.stripe_transfer_id`                   |


Model is **separate charges and transfers**, not destination charges. Company money lands on the **platform** Stripe balance (via wallet top-up). Internal holds/captures are ledger only. Stripe Transfer runs only on creator withdrawal.

Creator marketing copy (“paid within 24h”, “instant SEPA”) is the withdrawal processing target after a valid request, not a transfer on approve.

### Shared (Stripe webhooks)

Unauthenticated except `Stripe-Signature`. CSRF-exempt. Not under company/creator role middleware.


| Method | Path                  | What it does                                                                                           |
| ------ | --------------------- | ------------------------------------------------------------------------------------------------------ |
| POST   | `/api/stripe/webhook` | Verify signature. Idempotent by event id. Post ledger from Stripe, never from the SPA guessing success |


Handle at least:


| Event                                                                      | Effect                                                     |
| -------------------------------------------------------------------------- | ---------------------------------------------------------- |
| `checkout.session.completed`                                               | Wallet `topup` → `posted`; or Managed subscription row     |
| `payment_intent.payment_failed`                                            | Top-up txn → `failed`                                      |
| `invoice.paid` / `invoice.payment_failed`                                  | Company `invoices` + subscription                          |
| `customer.subscription.updated` / `deleted`                                | `company_subscriptions`                                    |
| `account.updated`                                                          | Creator Connect: `payouts_enabled`, requirements           |
| `transfer.created` / `transfer.reversed` / `payout.paid` / `payout.failed` | Creator withdrawal status `in_transit` → `paid` / `failed` |


SPA success/cancel URLs may poll; **posted balances only come from the webhook**.

### Company


| Method | Path                                                   | What it does                                                                                                                                                                                      |
| ------ | ------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| GET    | `/api/company/wallet`                                  | `available_cents`, `held_cents`, per-campaign holds, `currency=EUR`                                                                                                                               |
| GET    | `/api/company/wallet/transactions`                     | Ledger. Query: `campaign_id`, `type`, `status`                                                                                                                                                    |
| POST   | `/api/company/wallet/topups`                           | Ensure Stripe Customer. Create Checkout Session (`mode=payment`, EUR). Body: `amount_cents` (min to be set, e.g. 5000). Returns `checkout_url`, `stripe_session_id`. Insert `topup` txn `pending` |
| GET    | `/api/company/wallet/topups/{walletTransaction}`       | Poll pending top-up until webhook posts it                                                                                                                                                        |
| GET    | `/api/company/billing`                                 | Customer id, default payment method last4 (from Stripe), billing email                                                                                                                            |
| GET    | `/api/company/invoices`                                | Platform invoices (top-ups + Managed). Query: `status`                                                                                                                                            |
| GET    | `/api/company/invoices/{invoice}`                      | Detail + hosted invoice / PDF URL from Stripe                                                                                                                                                     |
| GET    | `/api/company/collaborations/{collaboration}/contract` | Platform contract (generated on book)                                                                                                                                                             |
| GET    | `/api/company/subscription`                            | `self_serve` (default, no Stripe sub) or `managed`                                                                                                                                                |
| POST   | `/api/company/subscription/checkout`                   | Checkout Session `mode=subscription` for Managed €700/mo. Campaign wallet is unchanged                                                                                                            |
| POST   | `/api/company/subscription/portal`                     | Stripe Billing Portal URL (upgrade/cancel/payment method)                                                                                                                                         |
| DELETE | `/api/company/subscription`                            | Cancel Managed at period end (or via portal)                                                                                                                                                      |


Book (`POST …/collaborations/{collaboration}/book`) is the spend reservation: refuse unless `available_cents` ≥ snapshot price. It does not charge the card again.

### Creator

Creators have an **earnings wallet** (available vs pending vs withdrawn). v1 schema today only stores a **company** `wallets` row; derive creator balances from posted credits minus withdrawals, or add a creator wallet table when implementing. The API still exposes a wallet.

Pending = booked / in delivery, not yet live. Available = live URL submitted, not yet withdrawn. In transit / paid = withdrawal rows.


| Method | Path                                                   | What it does                                                                                                                                               |
| ------ | ------------------------------------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------- |
| GET    | `/api/creator/wallet`                                  | `pending_cents`, `available_cents`, `in_transit_cents`, `paid_cents`, `currency=EUR`, `withdrawable` (available ≥ 10000 **and** Connect `payouts_enabled`) |
| GET    | `/api/creator/wallet/transactions`                     | Earnings credits (per collaboration, after live URL) and withdrawals                                                                                       |
| GET    | `/api/creator/connect`                                 | Stripe Connect status: onboarded, `payouts_enabled`, outstanding requirements                                                                              |
| POST   | `/api/creator/connect/onboarding`                      | Create Express account if missing. Return Account Link URL (`return_url` / `refresh_url`)                                                                  |
| POST   | `/api/creator/connect/dashboard`                       | Express login link (manage bank / SEPA)                                                                                                                    |
| POST   | `/api/creator/wallet/withdrawals`                      | Body: `amount_cents` (≤ available, ≥ 10000). Create `payouts` row `pending`, Stripe Transfer. 422 if Connect incomplete or under minimum                   |
| GET    | `/api/creator/payouts`                                 | Withdrawal history. Query: `status`                                                                                                                        |
| GET    | `/api/creator/payouts/{payout}`                        | One withdrawal + Stripe transfer id                                                                                                                        |
| GET    | `/api/creator/collaborations/{collaboration}/contract` | Same contract the brand sees                                                                                                                               |
| GET    | `/api/creator/earnings`                                | Alias of wallet totals plus per-campaign earned list (payment statement: campaign, live URL, amount — the document instead of a creator invoice)           |


There is no creator “invoice the brand” endpoint. There is no auto-payout job on approve. Optional later: auto-Transfer when available ≥ €100 and Connect is ready; v1 is an explicit withdrawal request.

BYO deals: same ledger. Bonus is an extra earnings credit (`type` on the creator txn or a second collaboration line), still withdrawn through Connect.

### Implementation notes (when coding, not now)

- Do not add Composer packages until approved. Prefer Laravel Cashier (Billing + Connect) if we take a package; otherwise Stripe PHP + webhooks.
- Top-up Checkout `success_url` / `cancel_url` back to the company SPA wallet page; still wait for the webhook to post the ledger.
- Idempotency keys on Checkout, Transfer, and webhook handlers.
- EUR only. Amounts integer cents.
- Platform fee: none on the creator (they keep 100% of `booked_price_cents`). Any fee is company-side later (`platform_fee` txn), not deducted from the withdrawal.

---



## 8. Communication

Company ↔ creator chat is **not** `agent_conversations`. One `conversations` row per collaboration.


| Method | Path                                                        | What it does                                                     |
| ------ | ----------------------------------------------------------- | ---------------------------------------------------------------- |
| GET    | `/api/company/collaborations/{collaboration}/messages`      | Thread                                                           |
| POST   | `/api/company/collaborations/{collaboration}/messages`      | Send                                                             |
| POST   | `/api/company/collaborations/{collaboration}/messages/read` | Mark read                                                        |
| GET    | `/api/creator/collaborations/{collaboration}/messages`      | Thread                                                           |
| POST   | `/api/creator/collaborations/{collaboration}/messages`      | Send                                                             |
| POST   | `/api/creator/collaborations/{collaboration}/messages/read` | Mark read                                                        |
| GET    | `/api/notifications`                                        | Database notifications (invites, applications, campaign updates) |
| POST   | `/api/notifications/{notification}/read`                    | Mark one read                                                    |
| POST   | `/api/notifications/read`                                   | Mark all read                                                    |
| GET    | `/api/notification-preferences`                             | Email toggles                                                    |
| PUT    | `/api/notification-preferences`                             | Update toggles                                                   |


Follow-ups that are ops notes stay on `collaboration_events` (section 4), not chat.

---



## 9. AI (after marketplace works)

Reuse `campaigns` columns, `creator_match_scores`, and `agent_conversations`. Do not add `ai_recommendations`.


| Method | Path                                                              | What it does                                                                        |
| ------ | ----------------------------------------------------------------- | ----------------------------------------------------------------------------------- |
| POST   | `/api/company/profile/analyze-url`                                | Company URL → value proposition + ICP drafts (onboarding already has a web variant) |
| POST   | `/api/company/campaigns/{campaign}/brief/generate`                | Fill goal, key messages, guidelines from ICP + URL                                  |
| POST   | `/api/company/campaigns/{campaign}/recommendations`               | Same as §2; AI writes score + reasons                                               |
| GET    | `/api/company/campaigns/{campaign}/creators/{creatorProfile}/fit` | AI explanation copy                                                                 |
| POST   | `/api/company/campaigns/{campaign}/budget-suggest`                | Suggested `budget_cents` from format + creator set                                  |
| POST   | `/api/company/campaigns/{campaign}/format-suggest`                | Suggested `type` / post count                                                       |


Ship matching/booking/pay/track first. These stay listed so the SPA can hide them behind a flag.

---



## Status cheat sheet

Used by the workflow endpoints above.


| Resource                    | Values                                                                                       |
| --------------------------- | -------------------------------------------------------------------------------------------- |
| Campaign                    | `draft`, `active`, `paused`, `completed`, `cancelled`                                        |
| Collaboration               | `invited`, `applied`, `outreach`, `declined`, `selected`, `booked`, `cancelled`, `completed` |
| Collaboration source        | `invite`, `apply`, `sourced`                                                                 |
| Post                        | `draft`, `in_review`, `changes_requested`, `approved`, `scheduled`, `published`, `rejected`  |
| Wallet txn                  | `topup`, `hold`, `capture`, `release`, `refund`, `payout`, `platform_fee`                    |
| Wallet txn status           | `pending`, `posted`, `failed`                                                                |
| Invoice                     | `draft`, `open`, `paid`, `void`                                                              |
| Payout (creator withdrawal) | `pending`, `in_transit`, `paid`, `failed`                                                    |
| Plan                        | `self_serve`, `managed`                                                                      |


---



## Build order

1. Profiles + niches + offers + workspaces (§1)
2. Creator search (§2 without AI)
3. Campaigns CRUD + launch (§3)
4. Invites / apply / book + company wallet hold + contract (§4, §7)
5. Posts submit/review/publish + tracking links (§5)
6. Stripe: company Checkout top-up + webhook, Connect onboarding, credit on live URL, withdrawal Transfer (§7)
7. Metrics + leads dashboards (§6)
8. Messages + notifications (§8)
9. AI brief / fit / budget (§9)

Ping routes can stay until each area replaces them.