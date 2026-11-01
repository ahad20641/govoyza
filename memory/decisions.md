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

## 2026-11-01 — Admin console on Filament (supersedes "Not added: Filament" in the first entry)

- **Filament 5.11.0** (`filament/filament:^5.11`, released 2026-10-26) for the admin console (1.6) and partner
  portal (1.7). Install it in task 1.6 and pin the exact resolved version in `composer.lock`.
- Why: the inherited admin is only an empty dashboard shell (`DashboardController::admin`); there is no Blade admin
  to keep. Filament 5 supports Laravel 13, ships 2FA (google2fa), and gives tables, forms and filters for free.
- It needs `ext-intl` (present locally and on Hostinger PHP 8.4, checked 2026-11-01) and Livewire 4. Livewire is
  used **only inside the Filament panels**; the public site and customer/agent portals stay plain Blade.
- Authorisation stays ours: Filament resources call the existing Policies and `config/permissions.php`.
  No `spatie/laravel-permission`, no `filament-shield`.

## 2026-11-01 — Admin console details (task 1.6)

- Filament **v5.11.0** installed (Livewire 4.4.8), constraint `^5.11`. Panel `admin` on `config('govoyza.domains.admin')`
  at path `/`, so `admin.govoyza.com/` is the dashboard. The old `/admin` shell on the main domain is removed.
- Staff sign in on the admin domain only. Signing in on the public site as staff signs them straight out and
  redirects to the admin login, because the admin console has its own session cookie and requires 2FA.
- **2FA:** Filament app (TOTP) authentication, `isRequired: true`, with hashed recovery codes. Stored in the existing
  encrypted `two_factor_*` user columns (`two_factor_recovery_codes` cast changed to `encrypted:array`).
- **Access:** `User::canAccessPanel` = admin type + active + `admin.access` (Owner implicit). Resources use the
  existing Policies, so the role matrix in `config/permissions.php` applies unchanged.
- **IP allowlist:** `GOVOYZA_ADMIN_IPS` (IPs/CIDR, comma-separated; empty = off). Other IPs get a 404. Persistent
  middleware, so Livewire updates are covered too. Behind a CDN this only works once its IPs are in `TRUSTED_PROXIES`.
- **Audit of every admin write:** wildcard Eloquent `created/updated/deleted` listener → `AdminWriteRecorder`, active
  only while Filament's current panel is `admin` (set by persistent middleware, so it also covers Livewire updates).
  Hidden attributes and known secret keys are masked. Admin logins and failed logins are audited too.
- **CSP:** the panel gets `script-src 'self' 'unsafe-inline' 'unsafe-eval'` (Livewire/Alpine need them); the public
  site keeps `script-src 'self'`.
- Filament assets are gitignored and published on the server by `post-deploy.sh` (`filament:assets`).
- First resources: **Exchange rates** (list + add only; history is append-only; Finance/Owner) and a read-only
  **Audit log** (Owner).
- **Staging admin URL:** staging has no admin subdomain (subdomain roots are forced inside production's web root),
  so the panel path is configurable: `GOVOYZA_ADMIN_PATH=admin` + `GOVOYZA_ADMIN_DOMAIN=staging.govoyza.com` →
  `staging.govoyza.com/admin`. Production leaves the path empty. The partner panel (1.7) will use the same pattern.

## 2026-11-01 — Partner portal (task 1.7)

- New `partners` table per `planning/data-model.md`, plus `users.partner_id`, `partner_documents` (KYC on the private
  disk, random names, sha256) and `partner_invitations` (sha256 of the link token only; 7-day expiry; single use).
  The inherited `vendors` / `vendor_*` tables are **not** migrated. They stay until the catalogue (1.9),
  application (1.17/1.20) and finance (1.24) tasks rebuild those tables on `partners`, then they get dropped.
- Role `vendor` → **`partner`** (`RoleSlug::Partner`, by migration); permission `vendors.manage` →
  **`partners.manage`** (Operations, Owner). The `/vendor` shell is gone; partner users signing in on the public site
  are sent to the partner login.
- Partner portal = second Filament panel `partner` on `GOVOYZA_PARTNER_DOMAIN` + `GOVOYZA_PARTNER_PATH`
  (production `partner.govoyza.com/`; staging `staging.govoyza.com/partner`). 2FA required. Access: active partner
  user whose partner isn't suspended; pending partners get in to finish onboarding.
- Join by **invite link only** (admin "Invite partner" action). The link page is a guest route inside the panel.
  The invited email counts as verified.
- Onboarding pages: KYC documents (trade licence + owner ID required), bank details (encrypted array cast), agreement.
  **Click-to-sign** stores typed name, time, IP, agreement version, signer and the sha256 of the exact text.
  Raising `govoyza.partner.agreement_version` makes every partner sign again.
- **The agreement text is a marked placeholder** (`resources/agreements/partner-agreement.md`). Claude doesn't write
  legal terms; the lawyer-approved supplier agreement (Phase 0) replaces it before any real partner signs.
- Admin: Partners resource (list + view; no create/edit/delete). Approve only when no onboarding gaps
  (`Partner::onboardingGaps()`); Suspend (with reason) / Reactivate. KYC files download through a Livewire action,
  policy-checked and audited, never via a public URL. Bank account shown masked to staff.
- `AdminWriteRecorder` → `PanelWriteRecorder`: writes in the partner panel are audited as `partner.created/updated/deleted`
  (security.md: "every admin and partner write"); panel logins as `{panel}.login` / `{panel}.login_failed`.

## 2026-11-01 — Reference data (task 1.8)

- **Renamed to match `data-model.md`:** inherited `visa_categories` (tourist, business…) → **`visa_types`**, and the
  inherited `visa_types` (e-visa / sticker) → **`visa_formats`**. Legacy `visa_rules` and `applications` columns
  follow (`visa_type_id` = purpose, `visa_format_id` = format). Products (1.9) use `visa_types`.
- Countries gain `slug` (URL key, never changed by the seeder once set), `region` (`schengen` | `gcc` | null,
  `App\Enums\CountryRegion`), `continent`, `seo_title`, `seo_description`.
- **Data:** `database/data/countries.php`, ISO 3166-1 (249) + Kosovo (XK) with ISO3, continent and demonym.
  Nationality rows only where the place issues its own passport: 199 (193 UN members + VA, PS, TW, HK, MO, XK).
  Schengen = 29 states (BG and RO in since 2025; CY and IE out); GCC = 6. Membership lives in `CountryRegion::members()`.
- **7 visa types:** tourist, medical, business, student, work, transit, family. **2 formats:** e-visa, sticker.
- `ReferenceDataSeeder` is idempotent and runs on every deploy (`post-deploy.sh`). It refreshes names, codes,
  regions and flags and adds missing rows, but never touches admin-edited fields (destination/popular/active,
  description, SEO, order, existing slugs, visa type names). The home country's nationality sorts first.
- Admin: Countries and Visa types resources (edit only, `catalog.manage`). Reference data stays public to read
  (`ReferenceDataPolicy`), but the admin screens check `canAccess()`.
- Not seeded: Bangladesh districts (not in 1.8's scope; needed by courier/drop-off later).
