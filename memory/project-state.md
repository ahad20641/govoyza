# Project state

> Living snapshot. Claude updates this at the end of every session (`/save-memory`).
> Keep it under ~60 lines — history goes in `progress-log.md`, not here.

**Last updated:** 2026-11-01
**Current phase:** Phase 1 — MVP, built skeleton first (see `planning/phase-1-mvp.md`, "Build order")
**Current task:** Area A — Catalogue + Pricing. 1.11 is in PR #12; next is 1.12 Pricing engine
**Branch:** `feat/1.11-requirements-checklist` → PR #12 to `develop`
**Project root:** `D:\AI Workflow For GoVoyza\Claude Code Workbase` (Windows)

## What exists
- Laravel 13 app: auth, domain-aware routing, base branding, security headers
- Money: `Money`, `Currency` (BDT/USD/EUR), `MoneyCast`, `exchange_rates` + `CurrencyConverter`
- Repo guards: env-template keys must be read in config, no new decimal money columns, no real Hostinger username
- Auth: `users.type` picks the portal, role picks permissions. Staff Owner / Operations / Finance. Email OTP.
  CLI: `create-admin`, `reset-password`, `staging-password`, `send-test-email` (passwords via `bash deploy/password.sh …`)
- Admin console (Filament 5.11, TOTP 2FA, audited). Groups: Today, Catalogue, Orders, Users, Accounting, CMS, Settings.
  Real: exchange rates, audit log, countries, visa types, products, partners, **offer review queue (1.10)**.
  S1 shells: Today board, supplier offers, orders, customers, agencies, staff, ledger, payouts, P&L, payables, CMS, coupons, settings
- Partner portal (Filament, 2FA, invite-only): KYC, bank details, agreement, my services (real); assigned jobs + job
  detail, earnings, performance (S1 shells, scoped to the partner)
- Public site (S1 shells): home + finder form, `/visa`, `/visa/{country}`, `/visa/{country}/{type}`, `/services/{product}`,
  `/track`, about/contact/faq/terms/privacy/refund-policy/add-ons/become-an-agent. Customer `/account`, agent `/agent`
- Schema: every `data-model.md` table exists (money `*_minor` BIGINT). Legacy tables dropped. `app/Domain/*` with READMEs;
  pricing, state machine, routing, ledger, wallet are `NotBuiltYet` stubs
- Requirements checklist (1.11): Operations manage per-product document items with required/optional state, accepted
  formats, maximum size and passport-validity rules; live service pages render the customer checklist.
- `DemoSeeder` (local/staging only, runs each deploy): 5 products, 5 orders, 1 job; demo logins
  `customer@`, `agent@`, `partner@demo.govoyza.test` with random passwords
- Local PHP 8.4 / SQLite: 159 tests pass, Pint passes. GitHub repo `ahad20641/govoyza` is **public**

## Environments
| Env | URL | Branch | Status |
|---|---|---|---|
| Local | govoyza.test | any | SQLite; migrations and tests pass |
| Staging | staging.govoyza.com | `develop` | **Live.** CI deploys on push; `/up` 200; cron every minute |
| Production | govoyza.com (+ partner., admin.) | `main` | Static coming-soon page in `public_html`. App not deployed, on purpose |

Server layout matches `deploy/hostinger.md`. Real server details live only in GitHub secrets and `.env` files.

## Waiting on owner
- [ ] Staging `.env` (File Manager): set `GOVOYZA_ADMIN_DOMAIN=staging.govoyza.com` and
  `GOVOYZA_PARTNER_DOMAIN=staging.govoyza.com`; add `GOVOYZA_ADMIN_PATH=admin`, `GOVOYZA_PARTNER_PATH=partner` and
  `MAIL_PASSWORD`; then `php artisan config:cache`. (Claude may not write server files.) Needed for staging admin/partner URLs
- [ ] To click through as a demo user: `bash deploy/password.sh reset-password customer@demo.govoyza.test` (same for agent@, partner@)
- [ ] Lawyer-approved **supplier agreement** text to replace the placeholder in `resources/agreements/partner-agreement.md`
- [ ] Change the staging Owner password (it was echoed): `bash deploy/password.sh reset-password EMAIL`
- [ ] 1.2b: `bash deploy/password.sh staging-password`, then Claude checks the 401 and ticks 1.2b
- [ ] After MAIL_PASSWORD: Claude sends `govoyza:send-test-email` to the owner's admin address
- [ ] Delete the unused `…_govoyza_prod1` database and the old unused staging DB in hPanel
- [ ] Before production: back up both `APP_KEY`s offline, decide go-live. Merging to `main` / prod deploy needs the owner's OK
- [ ] Payment gateway sandbox credentials (SSLCommerz, bKash, Nagad, Stripe)
- [ ] Phase 0 business items. See `planning/phase-0-foundations.md`

## Open questions
- Default display currency BDT; confirm USD/EUR shown alongside for international buyers
- Off-server DB backups (1.31): Hostinger disables `exec`/`proc_open`, so `mysqldump` via laravel-backup won't run

## Next 3 tasks
1. Area A — Catalogue + Pricing: 1.12 pricing engine, 1.13 finder, 1.14 country pages, 1.15 CMS
2. Area B — Orders + Documents: 1.16–1.22
3. Area C — Payments + Ledger: 1.23–1.27
