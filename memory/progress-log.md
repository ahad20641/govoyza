# Progress log

> Newest first. One short entry per working session: date, what was done, what's next.
> Claude appends here during `/save-memory`.

## 2026-11-01 (task 1.9 products and supplier offers)
- Added products, supplier offers, offer revisions, partner ownership policies, and Filament product/partner-offer screens.
- Draft offers can be submitted for review. Live-offer edits create pending revisions without mutating active terms; JSON serialization hides partner and cost details.
- PR #10. Verification: local migrations applied, 137 tests pass, Pint passes.
- Next: task 1.10 Admin review queue; task 1.2 production and 1.2b remain owner-blocked.

## 2026-11-01 (task 1.8 reference data)
- Renamed inherited `visa_categories` → `visa_types` and `visa_types` → `visa_formats` to match the data model.
- Seeded 250 countries, 199 nationalities, 7 visa types, 2 formats from `database/data/countries.php`; the seeder
  runs on every deploy without overwriting admin edits. Countries and Visa types admin screens. PR #9.
- Brought the test suite from 18s to 9s (low-cost bcrypt in staging-gate tests).
- Next: 1.9 products and supplier offers.

## 2026-11-01 (task 1.7 partner portal)
- Built the partner portal as a second Filament panel: invite-only join, KYC uploads, encrypted bank details,
  click-to-sign agreement, admin approve/suspend/reactivate, audited KYC downloads. PR #8. 122 tests.
- Renamed role `vendor` → `partner` (and `vendors.manage` → `partners.manage`); kept legacy vendor tables for now.
- The agreement text is a marked placeholder until the owner's lawyer provides the real one.
- Next: 1.8 reference data.

## 2026-11-01 (task 1.6 admin console)
- Owner's first password steps failed: pasting the `read`/`printf` block made `read` take the next pasted line.
  Added `deploy/password.sh` (reads from /dev/tty, one line to run). PR #6.
- Built 1.6 on Filament 5.11 (PR #7): required TOTP 2FA, IP allowlist, audit of every panel write, exchange-rate and
  audit-log resources. Staff signing in on the public site go to the admin login. 109 tests.
- Staging has no admin subdomain, so the panel path is configurable (`staging.govoyza.com/admin`). The owner sets the
  two `.env` lines; Claude's server-file writes are blocked by the permission classifier.
- Next: 1.7 partner portal.

## 2026-11-01 (password fix, Filament decision, 1.2b)
- `create-admin` echoed passwords on Hostinger (`secret()` falls back to visible input without `shell_exec`).
  Fixed in PR #4: hidden prompts with no fallback, asked twice, `--password-stdin`. Added `reset-password` and `send-test-email`.
- Merged PRs #3–#5 into `develop` (Claude may now merge into develop after CI; main/production need the owner's OK, per CLAUDE.md).
  PR #2's work had missed `develop` and came in via #3. Old branches deleted.
- Decided: Filament 5.11 for admin and partner panels (intl present on Hostinger).
- 1.2b: staging Basic Auth gate (PR #5, off until the owner sets the password); coming-soon page live on govoyza.com,
  Hostinger placeholder backed up to `~/domains/govoyza.com/public_html_placeholder_backup/`.
- Next: owner sets the staging password, changes their admin password, and adds MAIL_PASSWORD; then 1.6.

## 2026-11-01 (task 1.5 auth and roles)
- Added `users.type` and staff roles. `super_admin` was renamed to `owner` by migration; new `operations` and `finance` roles are least privilege. Each role maps to one type, and the model enforces it.
- Replaced signed email-verification links with 6-digit OTP codes. Only an HMAC is stored, with a 10-minute TTL, single use, a 5-guess cap and rate limits.
- Added policies for every existing model. `create-admin` takes `--role`. `post-deploy.sh` now syncs permissions.
- Kept the app's own RBAC instead of spatie (as already decided). 80 tests pass. PR #3 is stacked on PR #2.
- Next: owner merges #1 → #2 → #3; then 1.6 Admin console.

## 2026-11-01 (staging live)
- Server setup over SSH: `app/` and `staging-app/` folders outside `public_html`, staging web root symlinked to
  `staging-app/public`, both `.env` files written with an empty DB_PASSWORD (owner typed it; the first nano attempt didn't save).
- First staging deploys failed three ways, each fixed: a missing `tests/Unit` dir (exit 2), an SSH key secret
  corrupted by the PowerShell pipe (re-uploaded from Bash), and Hostinger's disabled `proc_open`/`symlink`
  (`--no-scripts` + `package:discover`, and `ln -s` for storage). The fixes went straight to `develop`.
- Staging is now live: CI deploy green, `/up` returns 200, APP_KEY generated, migrations ran, cron added via the API.
  Production is untouched.
- Found the owner's upload `govoyza-piece1.zip` (with a SQL dump) public in production's web root. The owner deleted it and it now 404s. A scan found nothing else risky in the web roots.
- Next: merge PRs #1/#2, then 1.5 Auth and roles.

## 2026-11-01 (task 1.2 deploy config)
- Redacted the Hostinger username from all unpushed history (filter-branch on `origin/develop..`) before the first push; pushed 1.4 as PR #1.
- Staging moved from a subdomain to a separate website (`staging.govoyza.com`). Partner/admin stay subdomains with root = `public_html`. Decision recorded; `deploy/hostinger.md` rewritten with placeholders only. `STAGING_APP_PATH` secret set.
- Fixed the env template names. Added `EnvTemplateTest` and `RepositoryGuardsTest` (no new decimal money columns, no real Hostinger username in the repo). 58 tests pass.
- Added per-task "convert decimal money columns" checkboxes to 1.12/1.17/1.23/1.24.
- Next: owner sends the real SSH host/port and DB names (still placeholders), then server setup and the first staging deploy.

## 2026-11-01 (task 1.2 progress, task 1.4)
- 1.2: committed `deploy/post-deploy.sh` (CI needs it; switched it to PHP 8.4). Created `partner`/`admin`
  subdomains and the GitHub `staging`/`production` environments; the required reviewer was accepted once the repo went public.
  Generated the deploy key and set 5 of the 8 Actions secrets. Secret scan of the whole git history was clean.
- 1.2 still blocked: the owner's SSH host/port were placeholders, the API shows no databases, and the staging web root sits inside prod's `public_html`.
- 1.4 done: `Money` value object, `Currency` enum, `MoneyCast`, `exchange_rates` + `CurrencyConverter`.
  28 new tests (add/subtract/percent/rounding/conversion/cast); 55 total pass; Pint passes.
- Found: inherited schema uses `decimal` money columns; env names in the deploy template don't match config (both in known-issues).
- Next: open the 1.4 PR, finish 1.2 when the owner replies, then 1.5 Auth and roles.

## 2026-11-01
- Installed PHP dependencies with Composer 2.11 and Node dependencies with Node 26/npm; configured local `.env` to use SQLite and applied migrations.
- Updated CI/docs for PHP 8.4 and Node 26; kept MySQL in `.env.example` and CI. Recorded local runtime/database choices and the Composer PATH fix.
- Fixed the PHPUnit host configuration for domain-scoped routes. Verification: 27 tests / 154 assertions pass; Pint passes.
- Next: implement Phase 1 task 1.4. Task 1.2 remains blocked on Hostinger setup and GitHub Actions secrets from the owner.

## 2026-11-01
- Planning kit created for the Govoyza platform blueprint: CLAUDE.md, planning/, memory/, deploy/, slash commands, GitHub Actions deploy workflow.
- Next: Phase 1 task 1.1 — create the Laravel project and push to GitHub.
