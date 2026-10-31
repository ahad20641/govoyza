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
| Staging | staging.govoyza.com | `develop` | not set up |
| Production | govoyza.com | `main` | not set up |

## Waiting on owner
- [ ] Hostinger plan type (Business/Cloud shared, or VPS)
- [ ] Hostinger SSH host, username, port (usually 65002) — hPanel → Advanced → SSH Access
- [ ] Domain govoyza.com pointed at Hostinger; subdomains `partner`, `admin`, `staging` created in hPanel
- [ ] MySQL databases created in hPanel (production + staging) and credentials stored in server `.env`
- [ ] GitHub Actions secrets added (see `deploy/hostinger.md`)
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
