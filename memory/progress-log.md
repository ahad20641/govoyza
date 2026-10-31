# Progress log

> Newest first. One short entry per working session: date, what was done, what's next.
> Claude appends here during `/save-memory`.

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
