# Project state

> Living snapshot. Claude updates this at the end of every session (`/save-memory`).
> Keep it under ~60 lines — history goes in `progress-log.md`, not here.

**Last updated:** 2026-11-01
**Current phase:** Phase 1 — MVP (see `planning/phase-1-mvp.md`)
**Current task:** 1.6 Admin console. 1.4, 1.2 config and 1.5 are merged into `develop` and live on staging; 1.2 production is still to do
**Branch:** `develop` (no open PRs). Start 1.6 from `develop`
**Project root:** `D:\AI Workflow For GoVoyza\Claude Code Workbase` (Windows)

## What exists
- Laravel 13 app: auth, dashboard, admin/security scaffolding, domain-aware routing, base branding
- Money (PR #1): `Money`, `Currency` enum (BDT/USD/EUR), `MoneyCast`, `exchange_rates` + `CurrencyConverter`.
  No admin UI for rates yet (1.6)
- Repo guards (PR #2): env-template keys must be read in config, no new decimal money columns, no real Hostinger username
- Auth (PR #3): `users.type` picks the portal, the role picks permissions (one type per role). Staff roles
  Owner / Operations / Finance (least privilege). Email verification by 6-digit OTP. Policies in `app/Policies`.
  Post-deploy runs `govoyza:sync-permissions`
- Local PHP 8.4 / SQLite: 80 tests pass, Pint passes
- GitHub repo `ahad20641/govoyza` is **public**. Secret scan of the full history on 2026-11-01 was clean
- Inherited schema still has `decimal` money columns, converted per task (checkboxes on 1.12/1.17/1.23/1.24)

## Environments
| Env | URL | Branch | Status |
|---|---|---|---|
| Local | govoyza.test | any | SQLite; migrations and tests pass |
| Staging | staging.govoyza.com | `develop` | **Live.** Deployed by CI on push; `/up` returns 200; APP_KEY set; cron every minute (Hostinger API) |
| Production | govoyza.com (+ partner., admin.) | `main` | Hostinger placeholder page. Not deployed, on purpose |

Server layout matches `deploy/hostinger.md`: `app/` and `staging-app/` sit outside any `public_html`.
Staging's `public_html` is a symlink to `staging-app/public`. Production's `public_html` is **not yet**
a symlink; swap it at production's first deploy. Both `.env` files exist (mode 600) with owner-typed DB passwords.
Real host, port, user and DB names live only in GitHub secrets and server `.env` files.

## Waiting on owner
- [ ] Create the first staff account on staging over SSH: `php artisan govoyza:create-admin` (you type the password)
- [ ] Delete the unused `…_govoyza_prod1` database and the old unused staging DB in hPanel (owner said they'd do it)
- [ ] Before production: back up both `APP_KEY`s offline, then decide the go-live date. Production deploy needs your approval (required reviewer)
- [ ] Payment gateway sandbox credentials (SSLCommerz, bKash, Nagad, Stripe)
- [ ] Transactional email provider credentials. Sign-up now needs a working mailer: without one, OTP codes never reach users
- [ ] Phase 0 business items. See `planning/phase-0-foundations.md`

## Open questions
- Public frontend: plain HTML/Blade (per decisions). Confirm there's no need for Livewire or Next.js
- Default display currency BDT; confirm USD/EUR shown alongside for international buyers
- Off-server DB backups (1.31): Hostinger disables `exec`/`proc_open`, so `mysqldump` via laravel-backup won't run

## Next 3 tasks
1. 1.6 Admin console (admin domain; includes the exchange-rate editor). Decide Filament vs plain Blade first (decisions say "revisit Filament")
2. 1.7 Partner portal
3. 1.8 Reference data
