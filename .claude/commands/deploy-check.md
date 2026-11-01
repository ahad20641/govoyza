---
description: Check the project is safe to deploy to Hostinger
---

Before merging to `develop` (staging) or `main` (production), check and report pass/fail for each:

1. `php artisan test` passes; `./vendor/bin/pint --test` passes.
2. `.env` is not tracked (`git ls-files .env` is empty); no secrets in the diff (`git diff develop...HEAD`).
3. New migrations are backward-compatible (no dropping/renaming columns still used by the live code).
4. No Node/Redis/Horizon/long-running process assumed on the server (see `memory/known-issues.md`).
5. Scheduler entries in `routes/console.php` are short and idempotent; queue runs via `queue:work --stop-when-empty`.
6. Supplier-leak test passes (once task 1.33 exists).
7. `.github/workflows/deploy.yml` and `deploy/post-deploy.sh` still match `deploy/hostinger.md`.
Then say whether it's safe to merge, and to which branch. $ARGUMENTS
