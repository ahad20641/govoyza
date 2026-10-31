# GOVOYZA — Visa Processing Platform

Laravel 13 · PHP 8.4+ · MySQL 8 · plain HTML/CSS/JS front end (no build step required).

Three portals — **B2C Client**, **B2B Travel Agency**, **Super Admin** (plus a Vendor role) — built in the
15 phases defined in the product spec.

## Build status

| Phase | Scope | Status |
|---|---|---|
| 1 | Database, authentication, roles & permissions | ✅ Done — 26 feature tests passing |
| 2 | Public website + client registration polish | Next |
| 3–15 | Visa rules → documents → pricing → pickup → payment → tracking → B2B → vendors → admin → notifications/chat → reports → security → deploy | Planned |

## Requirements

- PHP **8.4+** with `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `zip`
- Composer 2, MySQL 8 (or MariaDB 10.6+)
- A mail service (SMTP / SES / Mailgun …) for verification & reset emails

## Setup

```bash
composer install
cp .env.example .env          # then fill in DB_*, MAIL_*, APP_URL
php artisan key:generate
php artisan migrate --seed    # creates tables + roles, permissions, default settings (no users)
php artisan govoyza:create-admin   # first Super Admin — prompted for a strong password
php artisan serve
```

There is **no default admin account or password** anywhere in the code. The seeder creates only system data.

### Useful commands

| Command | Purpose |
|---|---|
| `php artisan govoyza:create-admin` | Create a Super Admin (min 12 chars, mixed case, number, symbol) |
| `php artisan govoyza:sync-permissions` | Re-sync roles + `config/permissions.php` after adding permissions |
| `php artisan test` | Run the test suite (SQLite in-memory) |

## What Phase 1 contains

**Database** (`database/migrations/`) — the full normalised schema for every module, so later phases
add behaviour, not tables:

- *Identity*: `roles`, `permissions`, `permission_role`, `users` (status, 2FA-ready columns), sessions, password resets
- *Reference data*: `countries`, `nationalities` (references countries — no duplication), `districts`, `visa_categories`, `visa_types`, `settings`
- *Parties*: `clients` (passport no. encrypted + HMAC lookup hash), `agencies` (status, credit limit, markup cap), `agency_documents`, `authorized_agencies` (drop-off branches), `vendors`
- *Catalogue*: `visa_rules` (destination + nationality|any + category → visa type), `visa_documents`, `services`, `service_prices` (per level: normal/urgent/super urgent), `pickup_locations`, `agency_services`, `vendor_services`, `agency_vendor`
- *Applications*: `application_drafts` (pre-payment wizard state — an application is only created after payment), `applications` (price & rule snapshot), `application_documents`, `application_status_history`, `vendor_assignments`, `embassy_submissions`, `courier_tracking`
- *Finance*: `payments`, `payment_transactions` (idempotency key), `receipts`, `ledger_accounts`, `ledger_transactions` (append-only), `deposit_requests`
- *Comms & audit*: `notifications`, `notification_templates`, `chat_conversations`, `chat_participants`, `chat_messages`, `reports`, `audit_logs`

Deliberate deviations from the suggested table list:
- `tracking_records` was not created — the client timeline is `application_status_history` (with `is_client_visible`) plus `embassy_submissions` and `courier_tracking`; a fourth table would duplicate them.
- Added `districts`, `application_drafts`, `chat_participants`, `agency_documents`, `agency_vendor` — required by the spec's rules (district drop-off, pay-before-create, chat access control, agency verification, vendor assignment).

**Access control**
- Roles: `super_admin`, `client`, `agency`, `vendor`. Permissions are defined in `config/permissions.php` and synced to the DB.
- `Gate::before` grants Super Admin everything; other roles get only their role's permissions. Record-level ownership is handled by Policies (from Phase 3).
- `role:` route middleware confines each portal; `EnsureAccountActive` logs out suspended users immediately and confines pending agencies to a status page.
- `role_id` and `status` are not mass-assignable — registration can't escalate privileges (tested).

**Authentication**
- Client registration → email verification → client dashboard
- Agency registration with business details + documents (trade license, owner ID, optional TIN) → email verification → *pending approval* until a Super Admin approves (approval UI lands in Phase 11)
- Login: 5 failed attempts per email+IP → 15-minute lockout; session regenerated on login; last login time/IP recorded
- Forgot/reset password with no user enumeration; signed, throttled verification links
- Strong password policy in production (10+ chars, mixed case, numbers, symbols, breach check)

**Security baseline**
- CSRF on all forms, Blade auto-escaping, Eloquent parameter binding
- Security headers incl. a strict Content-Security-Policy (no inline scripts), HSTS over HTTPS, `X-Frame-Options: DENY`
- Encrypted sessions, HTTPS forced in production
- Uploads stored on a **private** disk with random 40-char names, MIME-derived extensions, SHA-256 hashes; never web-served directly
- Immutable audit log (login, failed login, blocked login, logout, registration, password reset, admin creation) with secret masking

## Where credentials go

All secrets live in `.env` only — never in code or the front end. See `.env.example`.
Payment, SMS, courier and push providers get their own config sections as their phases are built.

## Production checklist (expanded in Phase 15)

- `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`
- Serve only `public/` as the web root; `storage/` must not be web-accessible
- Run `php artisan config:cache route:cache view:cache`
- Set `TRUSTED_PROXIES` if behind a load balancer
- Queue worker for emails: `php artisan queue:work`

## Note on the development sandbox

This code was built in a sandbox that only had PHP 8.3, while Laravel 13 requires PHP 8.4.
`tools/patch-vendor-php83.py` rewrites a handful of PHP 8.4-only syntax lines inside `vendor/`
so the tests could run there. **You do not need it** — on a server with PHP 8.4+, just run
`composer install`. (It never touches application code.)
