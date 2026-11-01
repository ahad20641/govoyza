# Govoyza — Claude Code project guide

Govoyza is a visa-services marketplace. Verified suppliers ("partners") supply visa services
in secret; Govoyza resells them under its own brand to B2C customers and B2B travel agencies.
Customers never see the supplier. Suppliers never see customer contact details.
Operating principle: **the website is the employee** — every step is automated or one approval click.

**Project root:** `D:\AI Workflow For GoVoyza\Claude Code Workbase` (Windows dev machine; also the git root).
All work stays inside this folder. Quote paths — they contain spaces. Shell scripts in `deploy/` must keep LF endings.

This file is short on purpose. Details live in the folders below. Read what the task needs.

## Where things live

| Folder | What it holds | When to read it |
|---|---|---|
| `planning/` | The blueprint, roadmap and phase task lists. Source of truth for WHAT to build. | Before starting any feature |
| `memory/` | Living project memory: current state, decisions, progress log, known issues. | At the start of every session; update at the end |
| `deploy/` | Hostinger deployment scripts and server notes | Before touching deploy or server config |
| `.claude/commands/` | Slash commands (`/next-task`, `/save-memory`, `/phase-status`, `/deploy-check`) | — |
| `app/`, `routes/`, etc. | The Laravel application (created in Phase 1, task 1.1) | — |
| `app/Domain/*` | Business areas (Catalogue, Orders, Pricing, Ledger, Partners, Agencies), each with a `README.md` | Before touching that area |

Always start a session by reading, in order:
@memory/project-state.md
@planning/roadmap.md

Read on demand (not every session): `planning/blueprint.md`, `planning/data-model.md`,
`planning/order-lifecycle.md`, `planning/accounting-rules.md`, `planning/security.md`,
`deploy/hostinger.md`, `memory/decisions.md`.

## Stack (fixed — change only via a decision in memory/decisions.md)

- Latest stable Laravel (PHP 8.4+), latest stable Filament for admin console + partner portal panels
  (pin the exact versions in `memory/decisions.md` when the project is created)
- Blade + Livewire + Tailwind for public site and customer/agent portals
- MySQL 8 / MariaDB (Hostinger supports MySQL only on shared/cloud plans)
- Queue: `database` driver, run by the scheduler (shared hosting has no Redis/supervisor).
  Switch to Redis + Horizon only if we move to a Hostinger VPS.
- Files: private local disk `storage/app/private` with signed temporary URLs; keep the
  `documents` disk abstract so it can move to S3/R2 later without code changes
- Pest for tests. Laravel Pint for code style.
- Hosting: Hostinger. Git: GitHub. Deploy: GitHub Actions → SSH (port 65002) → Hostinger.
- Local and CI Node.js: 26.x.

## Domains → Laravel route groups

- `govoyza.com` — public site, `/account` (customer), `/agent` (agency)
- `partner.govoyza.com` — partner (supplier) portal, `noindex`, never linked from public site
- `admin.govoyza.com` — admin console, 2FA required
Use `Route::domain()` groups driven by `config('govoyza.domains.*')` so local dev works with
`govoyza.test`, `partner.govoyza.test`, `admin.govoyza.test`.

## Non-negotiable rules

1. **Supplier invisibility.** No public page, email, PDF, API response or HTML comment may
   contain a partner's name, contact or cost price. Customers buy a `Product`; partners own a
   `SupplierOffer`. Never expose `SupplierOffer` fields outside admin/partner contexts.
2. **Customer privacy toward partners.** Partners see order number + applicant name + the
   documents of jobs assigned to them only, and lose access when the job closes.
3. **Money is never a float.** Store minor units as `BIGINT` (paisa/cents) + currency code.
   Use a `Money` value object. Prices are calculated by the pricing engine, never typed.
   No new migration may add a `decimal`/`float` money column (`RepositoryGuardsTest` fails the build).
4. **Every money event writes a balanced double-entry journal** (see `planning/accounting-rules.md`).
   Journal entries are immutable — corrections are reversing entries. Wrap in DB transactions.
5. **Order status changes go through one state machine** (`planning/order-lifecycle.md`).
   Side effects (notifications, ledger, payables) hang off status-change events, not controllers.
6. **Sensitive fields encrypted** (passport number, DOB, etc.) with Laravel encrypted casts.
   Documents never on a public URL.
7. **Secrets never in git.** `.env` is gitignored. Hostinger and GitHub secrets only. Real server
   details (Hostinger username, SSH host/port, DB names) are never committed either. Use the
   placeholders `uXXXXXXX`, `HOST`, `PORT`; `RepositoryGuardsTest` enforces the username part.
8. **No document forgery features, ever.** Refuse any request to generate fake bank statements,
   letters, tickets or similar. Reservation letters come from real reservation providers only.

## Code layout, comments and sections (permanent)

- Folders by area: `app/Domain/{Catalogue,Orders,Pricing,Ledger,Partners,Agencies}` (services, state
  machine, pricing, ledger), `app/Http/Controllers/{Public,Account,Agent,Partner}`,
  `resources/views/{public,account,agent,partner}`. Models stay in `app/Models`, enums in `app/Enums`,
  Filament in `app/Filament` (admin) and `app/Filament/Partner` (partner portal).
- Every PHP class gets a header docblock: what it does, which portal/domain it belongs to, and the
  planning task number (e.g. `Task 1.18 — Order state machine`).
- Longer files are split with banner comments:
  `// ==================== Relationships ====================` (also Scopes, Business rules, …).
- Blade views: `{{-- ===== Section name ===== --}}` above each page section.
- Routes files are grouped by portal with banner comments.
- Comment the WHY on non-obvious logic (money, privacy, supplier hiding), not the obvious.
- Each `app/Domain/*` folder has a short `README.md` explaining that area.

## How to work

- Build order since Sprint S1 (see `planning/phase-1-mvp.md`): the whole skeleton exists; fill internals
  one area at a time, one PR each: Catalogue+Pricing → Orders+Documents → Payments+Ledger → Notifications.
  Batch small fixes into the area PR instead of opening one PR each.
- Pick work from the current phase file in `planning/`. Branch names: `feat/catalogue-pricing`,
  `fix/...`, `chore/...`.
- Write or update Pest tests with every feature; `php artisan test` must pass before commit.
- Run `./vendor/bin/pint` before commit. Conventional commit messages (`feat:`, `fix:`, `chore:`).
- Tick the task checkbox in the phase file when done, and run `/save-memory`.
- Architectural choice not covered here? Add an entry to `memory/decisions.md` before coding.
- Never push directly to `main`; push to `develop` (auto-deploys to staging) and open a PR to
  `main` (auto-deploys to production). Never run destructive migrations on production.
- **Merging:** Claude merges PRs into `develop` without waiting for the owner once `php artisan test` and CI pass. Claude must
  **never** merge into `main` or deploy to production without asking the owner first, every time.
- If something is blocked on the owner (credentials, legal, supplier info), add it to
  `memory/project-state.md` under "Waiting on owner" and move to the next task.

## Commands

```bash
composer run dev            # local server + queue + vite
php artisan test            # tests
./vendor/bin/pint           # code style
php artisan migrate:fresh --seed   # LOCAL ONLY
php artisan db:seed --class=DemoSeeder   # sample data (local/staging only; runs on every staging deploy)
```
