# Decisions

## 2026-11-01 — Stack versions and package choices

- **PHP 8.4.27** (dev and CI also test 8.5), **Laravel 13.39.0**, Composer 2.11, Node 26 LTS.
- Started from the existing Phase 1 app (`govoyza-phase1.zip`) instead of a fresh
  `composer create-project`, because it already has the schema, auth and roles with 26 passing tests.
- Added: `spatie/laravel-backup` 10.3, `laravel/pint` 1.32, `pestphp/pest` 4.7 (+ `pest-plugin-laravel` 4.1).
- **Not added:** Filament, `spatie/laravel-permission`, `spatie/laravel-activitylog`. The app has its own
  `roles`/`permissions` tables (`config/permissions.php` + `govoyza:sync-permissions`) and an immutable
  `audit_logs` table; those packages would duplicate or conflict with them. Revisit Filament when the
  admin portal (Phase 11) is built.
- Front end is plain HTML/CSS/JS (no Livewire); Vite/Tailwind are installed but not required to run.
- Tests run on SQLite in-memory (`phpunit.xml`); production uses MySQL 8.

## 2026-11-01 — Local development database and runtime

- Local Windows development uses PHP 8.4 installed via WinGet and SQLite (`DB_CONNECTION=sqlite` in the ignored local `.env`).
- CI and production continue to use MySQL; `.env.example` and the CI MySQL service remain unchanged.
- Node.js 26 is the selected local and CI major version; Composer is 2.11.

## 2026-11-01 — Money value object and currency conversion (task 1.4)

- Own `App\Support\Money\Money` (int minor units + `App\Enums\Currency`) built on `brick/math`,
  not `brick/money` or `moneyphp/money`. `brick/math` is already a Laravel dependency and works
  without bcmath/gmp/intl (intl isn't loaded on the local Windows PHP). The API stays small
  and matches our rules.
- Amounts are created from strings or ints only. Floats are rejected at the type level and in `MoneyCast`.
- Rounding: **HalfUp** (ties go away from zero), applied once per operation at the currency's
  minor-unit scale. Intermediate maths (rate × buffer × amount) stays exact and is rounded once at the end.
- Formatting is done by hand (no intl): BDT `৳` with South Asian grouping (1,23,456.78),
  USD `$` / EUR `€` with Western grouping.
- `exchange_rates` stores `rate` as DECIMAL(18,8). A rate is not money, so this doesn't break the
  BIGINT rule. The rate in use is the latest row with `effective_at <= now`. If there is no direct
  pair, the inverse pair (1/rate) is used.
- The buffer % always *increases* the converted amount (a safety margin on selling prices).
  `CurrencyConverter::convert(..., withBuffer: false)` gives the raw rate for reports.

## 2026-11-01 — Hostinger hosting layout (task 1.2)

- **Production** app in `~/domains/govoyza.com/app`; `~/domains/govoyza.com/public_html` is a
  symlink to `app/public`.
- **partner. / admin.** are *subdomains* of govoyza.com whose root is `public_html` itself (API flag
  "use the website public directory"). They serve the same production app, and `Route::domain()`
  picks the portal. Nothing is nested inside `public_html`, and they use no website slots.
- **Staging** is a separate *website* (addon) `staging.govoyza.com`. Its app is in
  `~/domains/govoyza.com/staging-app` (outside any `public_html`), and
  `~/domains/staging.govoyza.com/public_html` is a symlink to `staging-app/public` only. Staging has its
  own `.env`, `APP_KEY` and database.
- Rejected: staging as a subdomain. Hostinger forces a subdomain's root under
  `domains/govoyza.com/public_html/…`, i.e. inside production. Rejected: parked domains
  (unverified for same-domain subdomains) and separate websites for partner/admin (+2 slots, two more
  symlinks for no gain).
- Slot usage on the Business plan: **2 websites** (govoyza.com, staging.govoyza.com) plus 2 subdomains.
- Real server details live only in GitHub secrets and server `.env` files; docs use `uXXXXXXX`/`HOST`/`PORT`.

## 2026-11-01 — Auth, user types and staff roles (task 1.5)

- **Still no `spatie/laravel-permission`** (the task text names it): the app's own `roles`/`permissions`
  tables already do the job (see the first entry). One role per user.
- `users.type` (`App\Enums\UserType`: customer / agent_user / partner_user / admin) picks the portal
  (`type:` middleware, `dashboardRoute()`); the role picks permissions. Each `RoleSlug` belongs to exactly
  one type, and `User` throws on a mismatch. Set both with `User::assignRole()`.
- `super_admin` renamed to **`owner`** by migration; new staff roles **`operations`** and **`finance`**.
  Owner bypasses all checks (`Gate::before`), and only when `type = admin`. Finance has no
  `applications.update_status` / `verify_documents` (cannot edit orders); Operations has no money permissions.
  Pricing, staff, settings and audit log are Owner-only.
- Existing role slugs `client` / `agency` / `vendor` and the `/client`, `/agency`, `/vendor` routes stay
  for now; renaming them belongs to the portal tasks (1.7, 1.22).
- **Email OTP replaces signed verification links** for every account type (one flow). 6 digits, HMAC-SHA256
  with `APP_KEY` bound to user + purpose (`Hashing::otp`), 10-minute TTL, single use, 5 wrong guesses kill a
  code, a new code deletes the old one. Route limits: verify 5/min + 30/h, resend 2/min + 10/h per user.
  The code email is sent synchronously, not queued, because the database queue only runs once a minute.
- Policies live in `app/Policies` (auto-discovered); Country/Nationality/District share `ReferenceDataPolicy`.
- `deploy/post-deploy.sh` now runs `govoyza:sync-permissions` after `migrate`, so new roles and
  permissions reach staging/production without a manual step.
