# Phase 1 — MVP (2–3 months)

**Gate:** first 50 paid orders processed end to end without spreadsheets.
Work top to bottom. Each task: branch → code + Pest tests → Pint → PR → tick box → `/save-memory`.
"Done when" lines are the acceptance criteria.

## 1A · Project foundation
- [x] **1.1 Create the Laravel project and connect GitHub**
  Latest stable Laravel with Pest, MySQL, Livewire, Tailwind/Vite. Move this planning kit into the project root (keep `CLAUDE.md`, `planning/`, `memory/`, `deploy/`, `.claude/`, `.github/`). `git init`, first commit, push to `main`, create `develop`.
  Done when: `php artisan test` passes locally, repo on GitHub has `main` + `develop`, versions recorded in `memory/decisions.md`.
- [ ] **1.2 Hostinger deploy pipeline (staging first)**
  Follow `deploy/hostinger.md`. GitHub secrets set, `develop` → staging, `main` → production. Cron for `schedule:run`. Health route `/up`.
  Done when: a push to `develop` shows the Laravel welcome page on staging over HTTPS, and `/up` returns 200.
- [x] **1.3 Config, domains and base layout**
  `config/govoyza.php` (domains, currencies, company details). `Route::domain()` groups for public / partner / admin. `noindex` header + robots disallow on partner and admin domains. Base Blade layout, Tailwind theme with brand tokens.
  Done when: all three hostnames resolve to the right route group locally and on staging; partner domain sends `X-Robots-Tag: noindex`.
- [x] **1.4 Money value object and currency service**
  `Money` (minor units + currency), Eloquent cast, formatting for BDT/USD/EUR. `exchange_rates` table with admin-set rate + buffer %.
  Done when: unit tests cover add/subtract/multiply-by-percent/rounding/conversion.

## 1B · Users and access
- [ ] **1.5 Auth and roles** — users table with `type` (customer, agent_user, partner_user, admin). Customer sign-up with email OTP (phone OTP later). Roles/permissions (spatie/laravel-permission): Owner, Operations, Finance. Policies per model.
- [ ] **1.6 Admin console (Filament panel on admin domain)** — 2FA required, optional IP allowlist from config, audit log of every admin write (`audit_logs` table or spatie/laravel-activitylog).
- [ ] **1.7 Partner portal (Filament panel on partner domain)** — invite-link registration, KYC upload, bank details, e-sign agreement (click-to-sign with name, timestamp, IP, agreement version), status Pending → Active by admin. 2FA required.

## 1C · Catalogue
- [ ] **1.8 Reference data** — countries (with region, e.g. Schengen), visa types, nationalities. Seeders for all countries + the 7 visa types.
- [ ] **1.9 Products and supplier offers** — see `data-model.md`. Partner creates/edits offers (Draft → Pending review). Edits to a Live offer create a pending revision; live version unchanged until approved.
- [ ] **1.10 Admin review queue** — side-by-side compare with other offers for same country + visa type + nationality. Approve / Request changes (comment) / Reject. On approve: set B2C + B2B markup, public name/description, primary/backup priority, publish product. Cost increases flagged.
- [ ] **1.11 Requirements checklist** — per-product requirement items (doc type, required, rules: max size, formats, passport validity months).
- [ ] **1.12 Pricing engine** — `PriceCalculator` returns breakdown (service price, embassy fee pass-through, VAT, total) per channel/tier/currency. Never typed by hand. Tested with fixtures.
  - [ ] Convert decimal money columns to `*_minor` BIGINT + `currency` with `MoneyCast`: `service_prices` (government/service/courier/other fee, discount), `agency_services` + `vendor_services` (normal/urgent/super_urgent price), `pickup_locations.courier_fee`. Remove `create_visa_catalog_tables` from `RepositoryGuardsTest::LEGACY_DECIMAL_MIGRATIONS`.

## 1D · Public site
- [ ] **1.13 Home + visa finder** — nationality / destination / purpose search → matching products with price, processing time, requirements.
- [ ] **1.14 Country pages, visa-type pages, service detail** — `/visa/{country}`, `/visa/{country}/{type}`; auto-generated from catalogue; SEO meta, JSON-LD, sitemap.xml (public domain only).
- [ ] **1.15 Static/CMS pages** — About, Contact, FAQ, Terms, Privacy, Refund, Become an agent (simple CMS in admin).
- [ ] **1.16 Track application** — order number + email, no login, rate-limited.

## 1E · Orders
- [ ] **1.17 Order placement** — choose product, add applicants (or saved travellers), price snapshot locked on the order, checklist generated. Guest checkout creates account.
  - [ ] Convert decimal money columns to `*_minor` BIGINT + `currency` with `MoneyCast`: `applications` (government/service/courier/other fee, discount, total_amount), `vendor_assignments.vendor_price`. Remove `create_application_tables` from `RepositoryGuardsTest::LEGACY_DECIMAL_MIGRATIONS`.
- [ ] **1.18 Order state machine** — implement `order-lifecycle.md` exactly: allowed transitions, who may trigger each, events fired. Tested transition by transition.
- [ ] **1.19 Document upload & review** — private `documents` disk, signed temporary URLs, versioning, admin completeness check (approve / flag item back to customer with reason).
- [ ] **1.20 Routing & jobs** — on admin approval of completeness, create Job for primary offer (fallback to backup if primary paused/at capacity). Partner sees only order no., applicant name, assigned documents. Access revoked when job closes.
- [ ] **1.21 Partner job updates** — status updates, appointment date, upload result file (e-visa, letter, decision). Each update fires customer notification via events.
- [ ] **1.22 Customer portal** — dashboard, application detail timeline, uploads, messages (customer ↔ Govoyza only; partner messages relayed by admin), invoices, saved travellers, profile.

## 1F · Money
- [ ] **1.23 Payment gateway layer** — `PaymentGateway` interface; SSLCommerz first (covers cards + bKash/Nagad via SSLCommerz), Stripe second. Webhook/IPN verification, idempotency keys, sandbox mode from config.
  - [ ] Convert decimal money columns to `*_minor` BIGINT + `currency` with `MoneyCast`: `payments` (amount, refunded_amount), `payment_transactions.amount`, `deposit_requests.amount`.
- [ ] **1.24 Ledger** — implement `accounting-rules.md`: accounts, journal entries + lines, balanced-entry guard, posting service listening to order/payment events.
  - [ ] Convert decimal money columns to `*_minor` BIGINT + `currency` with `MoneyCast`: `ledger_accounts` (opening/current balance), `ledger_transactions` (debit, credit, balance_after), `agencies.credit_limit`. Remove `create_finance_tables` and `create_party_tables` from `RepositoryGuardsTest::LEGACY_DECIMAL_MIGRATIONS`; the list should then be empty.
- [ ] **1.25 Invoices & receipts** — PDF (Govoyza branding only), invoice numbering per fiscal year, embassy fee shown separately, credit notes for refunds.
- [ ] **1.26 Refunds & cancellations** — admin action with reason; gateway refund or manual; reversing journal entries.
- [ ] **1.27 Basic reports** — P&L by month, per-order profit, supplier payables due list, export CSV.

## 1G · Notifications & ops
- [ ] **1.28 Email notifications** — Markdown mail templates editable in admin; one per lifecycle event; queued.
- [ ] **1.29 Reminders & SLA basics** — scheduler: document reminders at 24h/72h; job SLA timer flags late jobs on Today board.
- [ ] **1.30 Admin Today board** — orders needing action, late jobs, offers to review, partners to approve.
- [ ] **1.31 Backups & monitoring** — daily DB + documents backup off-server (spatie/laravel-backup to a second location), error tracking (Sentry), uptime monitor on `/up`.

## 1H · Launch
- [ ] **1.32 Security pass** — run through `security.md` checklist; fix all items.
- [ ] **1.33 Supplier-leak test** — automated test that crawls public pages, emails, PDFs and customer API responses for any partner name/email/phone/cost. Must pass in CI.
- [ ] **1.34 Production launch** — seed real catalogue for top 5 destinations, gateway live keys, merge to `main`.
