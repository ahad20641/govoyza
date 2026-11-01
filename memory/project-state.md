# Project state

> Living snapshot. Claude updates this at the end of every session (`/save-memory`).
> Keep it under ~60 lines — history goes in `progress-log.md`, not here.

**Last updated:** 2026-11-01
**Current phase:** Phase 1 — MVP (see `planning/phase-1-mvp.md`)
**Current task:** 1.5 Auth and roles (1.4 done on `feat/1.4-money-value-object`; 1.2 blocked on owner)
**Branch:** `feat/1.4-money-value-object` (from `develop`)
**Project root:** `D:\AI Workflow For GoVoyza\Claude Code Workbase` (Windows)

## What exists
- Laravel 13 app: auth, dashboard, admin/security scaffolding, domain-aware routing, base branding
- Money: `App\Support\Money\Money`, `App\Enums\Currency` (BDT/USD/EUR), `MoneyCast`,
  `exchange_rates` table + `CurrencyConverter` (buffer %, inverse rate). No admin UI for rates yet (1.6)
- Local PHP 8.4 / SQLite: 55 tests pass, Pint passes
- GitHub repo `ahad20641/govoyza`, **now public**. Secret scan of the full history on 2026-11-01 was clean
- Inherited schema still has `decimal` money columns. See known-issues → Money

## Environments
| Env | URL | Branch | Status |
|---|---|---|---|
| Local | govoyza.test | any | SQLite; migrations and tests pass |
| Staging | staging.govoyza.com | `develop` | subdomain exists; app not deployed; web root needs fixing |
| Production | govoyza.com | `main` | domain + SSL live; app not deployed. Do not deploy yet |

## Waiting on owner — task 1.2 (hosting)
Owner reports done in hPanel: PHP 8.4 + extensions, SSH enabled, deploy public key added, DBs created.
- [ ] **SSH host + port**: the last message still had the `HOST_HERE` / `PORT_HERE` placeholders
- [ ] DBs: the Hostinger API lists 0 databases. Owner to confirm the account and send the full DB/user names (no passwords)
- [ ] Approve the staging layout: app in `~/domains/govoyza.com/staging-app`, recreate the `staging`
      subdomain with its root at `staging-app/public` (it's currently `public_html/staging`, inside prod's web root)
- Done: `partner`/`admin` subdomains (root = `public_html`), `staging` + `production` GitHub environments
  (production: required reviewer `ahad20641`, `main` only), deploy key `%USERPROFILE%\.ssh\govoyza_deploy`,
  secrets SSH_KEY, SSH_USER, PROD_APP_PATH, PROD_URL, STAGING_URL
- Then (Claude): secrets HOST/PORT/STAGING_APP_PATH → fix env names in `deploy/env.production.example`
  → server folders, symlinks, `.env` (DB_PASSWORD typed by owner) → push `develop` → `key:generate`,
  `storage:link`, cron → check staging `/up` is 200
- [ ] Payment gateway sandbox credentials (SSLCommerz, bKash, Nagad, Stripe)
- [ ] Transactional email provider credentials
- [ ] Phase 0 business items. See `planning/phase-0-foundations.md`

## Open questions
- Owner: should the Hostinger username in commit `1559971` (memory file) stay public, or be replaced with `uXXXXXXX`?
- Public frontend: plain HTML/Blade (per decisions). Confirm there's no need for Livewire or Next.js
- Default display currency BDT; confirm USD/EUR shown alongside for international buyers

## Next 3 tasks
1. Merge the 1.4 PR, then finish 1.2 once the owner sends the SSH host/port
2. 1.5 Auth and roles
3. 1.6 Admin console (admin domain; includes the exchange-rate editor)
