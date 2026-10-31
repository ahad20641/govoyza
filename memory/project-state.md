# Project state

> Living snapshot. Claude updates this at the end of every session (`/save-memory`).
> Keep it under ~60 lines — history goes in `progress-log.md`, not here.

**Last updated:** 2026-11-01
**Current phase:** Phase 1 — MVP (see `planning/phase-1-mvp.md`)
**Current task:** 1.5 Auth and roles (1.4 done on `feat/1.4-money-value-object`; 1.2 blocked on owner)
**Branch:** `chore/1.2-deploy-config` (stacked on `feat/1.4-money-value-object`, PR #1)
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
| Staging | staging.govoyza.com | `develop` | separate website on the plan, HTTPS live; app not deployed |
| Production | govoyza.com | `main` | domain + SSL live; app not deployed. Do not deploy yet |

## Waiting on owner — task 1.2 (hosting)
Owner reports done in hPanel: PHP 8.4 + extensions, SSH enabled, deploy public key added, DBs created.
- [ ] **SSH host + port**: the owner's last two messages still had placeholders (`HOST_FROM_HPANEL` / `PORT_FROM_HPANEL`)
- [ ] DB names: also placeholders (`PROD_DB_NAME` …), and the Hostinger API lists 0 databases. Owner to confirm in hPanel
- [ ] PHP 8.4 + extensions on the new `staging.govoyza.com` website (hPanel, per website)
- Done: layout decided (see decisions → "Hostinger hosting layout"). Staging recreated as a separate website,
  partner/admin are subdomains with root = `public_html`. GitHub environments, with production requiring
  reviewer `ahad20641` and deploying from `main` only. Deploy key `%USERPROFILE%\.ssh\govoyza_deploy`.
  Secrets SSH_KEY, SSH_USER, PROD_APP_PATH, STAGING_APP_PATH, PROD_URL, STAGING_URL. Env template names fixed.
- Then (Claude): secrets HOST/PORT → server folders + symlinks (hostinger.md step 5) → both `.env`
  files without DB_PASSWORD (owner types it with nano) → push `develop` → `key:generate`, `storage:link`,
  cron → check staging `/up` is 200
- [ ] Payment gateway sandbox credentials (SSLCommerz, bKash, Nagad, Stripe)
- [ ] Transactional email provider credentials
- [ ] Phase 0 business items. See `planning/phase-0-foundations.md`

## Open questions
- Public frontend: plain HTML/Blade (per decisions). Confirm there's no need for Livewire or Next.js
- Default display currency BDT; confirm USD/EUR shown alongside for international buyers

## Next 3 tasks
1. Merge PR #1 (1.4) and the stacked deploy-config PR, then finish 1.2 once the owner sends the SSH host/port
2. 1.5 Auth and roles
3. 1.6 Admin console (admin domain; includes the exchange-rate editor)
