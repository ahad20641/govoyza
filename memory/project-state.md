# Project state

> Living snapshot. Claude updates this at the end of every session (`/save-memory`).
> Keep it under ~60 lines — history goes in `progress-log.md`, not here.

**Last updated:** 2026-11-01
**Current phase:** Phase 1 — MVP (see `planning/phase-1-mvp.md`)
**Current task:** 1.10 Admin review queue. 1.9 is in PR #10 (merge after CI); 1.2 (production half) and 1.2b wait on the owner
**Branch:** `feat/1.9-products-and-supplier-offers`
**Project root:** `D:\AI Workflow For GoVoyza\Claude Code Workbase` (Windows)

## What exists
- Laravel 13 app: auth, domain-aware routing, base branding, security headers
- Money: `Money`, `Currency` (BDT/USD/EUR), `MoneyCast`, `exchange_rates` + `CurrencyConverter`
- Repo guards: env-template keys must be read in config, no new decimal money columns, no real Hostinger username
- Auth: `users.type` picks the portal, role picks permissions. Staff Owner / Operations / Finance. Email OTP.
  Policies in `app/Policies`. CLI: `create-admin`, `reset-password`, `staging-password`, `send-test-email`
  (passwords via `bash deploy/password.sh …` on Hostinger)
- Admin console (1.6): Filament 5.11, required TOTP 2FA, optional `GOVOYZA_ADMIN_IPS`, every panel write audited.
  Resources: exchange rates (add-only), audit log (read-only). Production `admin.govoyza.com/`, staging `staging.govoyza.com/admin`
- Partner portal (1.7): Filament panel `partner`, 2FA, invite-only join, KYC uploads (private), encrypted bank
  details, click-to-sign agreement; admin Partners resource (invite / approve / suspend / download KYC). Role is
  `partner` now (was `vendor`). Production `partner.govoyza.com/`, staging `staging.govoyza.com/partner`
- Staging gate (Basic Auth, off until a hash is set) and the coming-soon page on govoyza.com
- Reference data (1.8): 250 countries (slug, region schengen/gcc, continent, SEO), 199 nationalities, 7 visa types,
  2 visa formats. `ReferenceDataSeeder` runs on every deploy and keeps admin edits. Admin: Countries, Visa types
- Catalogue (1.9): products and supplier offers with partner ownership; live-offer edits are pending revisions and
  don't change current terms. Product and offer JSON hides supplier/cost details.
- Local PHP 8.4 / SQLite: 137 tests pass, Pint passes. GitHub repo `ahad20641/govoyza` is **public**
- Legacy tables still exist, unused by new code: `vendors`/`vendor_*`, `visa_rules`, `visa_documents`, `services`,
  `service_prices`, `agency_services`, application tables. Retire them in 1.9/1.11/1.17/1.20/1.24
- Inherited schema still has `decimal` money columns, converted per task (checkboxes on 1.12/1.17/1.23/1.24)

## Environments
| Env | URL | Branch | Status |
|---|---|---|---|
| Local | govoyza.test | any | SQLite; migrations and tests pass |
| Staging | staging.govoyza.com | `develop` | **Live.** CI deploys on push; `/up` 200; cron every minute |
| Production | govoyza.com (+ partner., admin.) | `main` | Static coming-soon page in `public_html`. App not deployed, on purpose |

Server layout matches `deploy/hostinger.md`: `app/` and `staging-app/` sit outside any `public_html`.
Staging's `public_html` is a symlink to `staging-app/public`. Production's `public_html` holds the coming-soon
page; it becomes the `app/public` symlink at production's first deploy. Hostinger's placeholder is backed up in
`~/domains/govoyza.com/public_html_placeholder_backup/`. Real server details live only in GitHub secrets and `.env` files.

## Waiting on owner
- [ ] Staging `.env` (File Manager): set `GOVOYZA_ADMIN_DOMAIN=staging.govoyza.com` and
  `GOVOYZA_PARTNER_DOMAIN=staging.govoyza.com`; add `GOVOYZA_ADMIN_PATH=admin`, `GOVOYZA_PARTNER_PATH=partner` and
  `MAIL_PASSWORD`; then `php artisan config:cache`. (Claude may not write server files.)
- [ ] Lawyer-approved **supplier agreement** text to replace the placeholder in `resources/agreements/partner-agreement.md`
  (needed before any real partner signs)
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
1. 1.10 Admin review queue
2. 1.11 Requirements checklist
3. 1.12 Pricing engine
