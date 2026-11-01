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
