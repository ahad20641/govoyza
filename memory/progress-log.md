# Progress log

> Newest first. One short entry per working session: date, what was done, what's next.
> Claude appends here during `/save-memory`.

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
