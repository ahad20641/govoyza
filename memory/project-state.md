# Project state

> Living snapshot. Claude updates this at the end of every session (`/save-memory`).
> Keep it under ~60 lines — history goes in `progress-log.md`, not here.

**Last updated:** 2026-11-01
**Current phase:** Phase 1 — MVP (see `planning/phase-1-mvp.md`)
**Current task:** 1.7 Partner portal. 1.6 is merged (PR #7). 1.2 (production half) and 1.2b wait on the owner
**Branch:** `develop` (no open PRs)
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
- Staging gate (Basic Auth, off until a hash is set) and the coming-soon page on govoyza.com
- Local PHP 8.4 / SQLite: 109 tests pass, Pint passes. GitHub repo `ahad20641/govoyza` is **public**
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
- [ ] Staging `.env` (File Manager): set `GOVOYZA_ADMIN_DOMAIN=staging.govoyza.com`, add `GOVOYZA_ADMIN_PATH=admin`,
  add `MAIL_PASSWORD`, then `php artisan config:cache`. (Claude may not write server files.)
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
- Partner portal on staging needs the same path trick as admin (`GOVOYZA_PARTNER_PATH`), decide in 1.7

## Next 3 tasks
1. 1.7 Partner portal (Filament panel on the partner domain; invite link, KYC, bank details, e-sign, 2FA)
2. 1.8 Reference data (countries, visa types, nationalities + seeders)
3. 1.9 Products and supplier offers
