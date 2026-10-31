# Project state

> Living snapshot. Claude updates this at the end of every session (`/save-memory`).
> Keep it under ~60 lines — history goes in `progress-log.md`, not here.

**Last updated:** 2026-11-01
**Current phase:** Phase 1 — MVP (see `planning/phase-1-mvp.md`)
**Current task:** 1.4 Money value object and currency service
**Branch:** `develop`
**Project root:** `D:\AI Workflow For GoVoyza\Claude Code Workbase` (Windows)

## What exists
- Laravel app with auth, dashboard, admin/security scaffolding, domain-aware routing and base branding
- Local PHP 8.4 / SQLite setup; migrations pass, 27 Pest tests pass, Pint passes
- GitHub repo `ahad20641/govoyza` connected; `develop` is the working branch

## Environments
| Env | URL | Branch | Status |
|---|---|---|---|
| Local | govoyza.test | any | SQLite configured; migrations and tests pass |
| Staging | staging.govoyza.com | `develop` | not set up (layout decision pending) |
| Production | govoyza.com | `main` | domain + SSL live, app not deployed |

## Waiting on owner
Task 1.2 (hosting) progress, 2026-11-01. Hostinger account `uXXXXXXX` (shared CloudLinux), govoyza.com already on Hostinger with HTTPS.
- [x] Subdomains `partner` + `admin` created, root = `domains/govoyza.com/public_html` (same app)
- [ ] Decide staging layout: subdomain `staging` was created at `public_html/staging`, which clashes with
      the `public_html → app/public` symlink. Proposal: replace it with a separate website `staging.govoyza.com`
- [ ] PHP 8.4 + extensions (intl, bcmath, gd/imagick, zip, pdo_mysql, fileinfo, exif) — hPanel only
- [ ] Enable SSH; add deploy public key (`%USERPROFILE%\.ssh\govoyza_deploy.pub`) in hPanel → SSH Access
- [ ] MySQL `govoyza_prod` + `govoyza_staging` (hPanel; passwords go only in server `.env`)
- [ ] Server folders, `public_html` symlink, server `.env` (see `deploy/hostinger.md` step 5)
- [ ] GitHub secrets still missing: `HOSTINGER_SSH_HOST`, `HOSTINGER_SSH_PORT`, `STAGING_APP_PATH`
      (set: SSH_KEY, SSH_USER, PROD_APP_PATH, PROD_URL, STAGING_URL)
- [ ] Production approval gate: repo is private on GitHub Free, so required reviewers are unavailable.
      `staging` + `production` environments exist without rules. Upgrade to Pro, or rely on PR-to-main as the gate
- [ ] Cron `schedule:run` per environment, then `key:generate` + `storage:link` after first deploy
- [ ] Payment gateway sandbox credentials (SSLCommerz, bKash, Nagad, Stripe)
- [ ] Transactional email provider (SMTP/Postmark/SES) credentials
- [ ] Phase 0 business items (trade licence, suppliers, top 5 destinations) — see `planning/phase-0-foundations.md`

## Open questions
- Public frontend: Blade + Livewire (default) — confirm no need for Next.js
- Default display currency BDT; confirm USD/EUR shown alongside for international buyers

## Next 3 tasks
1. 1.4 Money value object and currency service
2. 1.5 Auth and roles
3. 1.6 Admin console (Filament panel on admin domain)
