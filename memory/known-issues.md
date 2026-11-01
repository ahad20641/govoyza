# Known issues and gotchas

> Things that bit us once and must not bite again. Add one line each, with the fix.

## Hostinger
- Built-in hPanel Git auto-deploy only copies into `public_html` — do not use it for Laravel. Use the GitHub Actions workflow.
- SSH port is usually **65002**, not 22.
- Node is not reliably available on shared hosting — build Vite assets in GitHub Actions, never on the server.
- No long-running processes: never rely on `queue:work` without `--stop-when-empty` or on Horizon.
- PHP CLI version can differ from the web PHP version. Set both to 8.4 in hPanel and call `php` via full path in cron if needed (`/opt/alt/php84/usr/bin/php` on CloudLinux).
- `deploy/env.production.example` uses env names that `config/govoyza.php` doesn't read
  (`GOVOYZA_DOMAIN_PUBLIC/_PARTNER/_ADMIN`, `GOVOYZA_DEFAULT_CURRENCY` vs `GOVOYZA_DOMAIN`,
  `GOVOYZA_PARTNER_DOMAIN`, `GOVOYZA_ADMIN_DOMAIN`, `GOVOYZA_CURRENCY`). Fix the template before writing server `.env` files (task 1.2).

## Tooling
- In this Windows Bash tool, a long command with several quoted heredocs (`<<'EOF'`) holding PHP code can
  fail to parse ("unexpected EOF while looking for matching `'`"), and then nothing in it runs. Write PHP files
  with the Write tool instead.

## GitHub
- Stacked PRs: merging the bottom PR does not retarget the ones above it. PR #2 was merged into the dead
  `feat/1.4` branch, so its work missed `develop` until PR #3 carried it in. Retarget each stacked PR to
  `develop` *before* merging it.
- Required reviewers on an environment need a public repo or a paid plan (Pro/Team). While the repo
  was private on Free, the API returned 422. It's now public and the `production` rule is in place.

## Money
- The existing schema (inherited Phase 1 app) stores money as `decimal(12,2)` / `decimal(14,2)` in
  services, applications, payments, ledger and agencies. That breaks the BIGINT-minor-units rule.
  Move each table to `*_minor` + `currency` with `MoneyCast` when its task rebuilds it (1.12, 1.17, 1.23, 1.24).
  New tables must use `MoneyCast` from the start.

- A subdomain's web root is always under `domains/<main>/public_html/…`. The API rejects `..`, and a custom
  folder name still lands inside `public_html`. So a separate app (staging) must be its own *website*,
  never a subdomain. Subdomains are only for same-app hosts (partner/admin, root = `public_html`).
- Hostinger PHP `disable_functions` includes `proc_open`, `exec`, `shell_exec`, `system`, `popen`, `symlink`.
  - Composer can't run scripts, so use `composer install --no-scripts`, then `php artisan package:discover` (done in `post-deploy.sh`).
  - `artisan storage:link` fails, so create `public/storage` with `ln -s ../storage/app/public public/storage`; rsync excludes `/public/storage`.
  - Without `shell_exec`, Laravel's `secret()` **shows** the password (its hidden-input fallback). Use
    `PromptsForNewPassword` (fallback off) and `--password-stdin` with the shell's `read -s`.
- Config is cached on deploy: an `.env` edit on the server needs `php artisan config:cache` to take effect.
- Don't give the owner a block of `read -s …` lines to paste: `read` takes the next pasted line as its input. Give
  one-line commands (`bash deploy/password.sh …`).
- Claude's SSH writes to server files (e.g. `.env`) are blocked by the auto-mode permission classifier; reads work.
  Server-side edits go to the owner (File Manager) unless they add a permission rule.

## Filament
- A `Route::get('/')` on the admin domain in `routes/web.php` silently replaces the panel's dashboard route
  (same host + URI). Keep `web.php` free of admin-domain routes.
- Audit/IP middleware must be `isPersistent: true` (or keyed off `Filament::getCurrentPanel()`), or Livewire update
  requests, where saves happen, skip them.
- On Windows, a bare `python` in a command can hang (Store alias). Don't use it; use PHP or the file tools.
  - laravel-backup's `mysqldump` (task 1.31) needs `exec`/`proc_open`, so plan another way to dump the DB.
- A secret piped through PowerShell 5.1 (`Get-Content -Raw | gh secret set`) got corrupted
  (CI: `Load key ... error in libcrypto`). Upload files from Git Bash: `gh secret set NAME < file`.
- PowerShell 5.1 mangles `gh ... --jq '...'` and `--body` strings with quotes. Use Git Bash, or `--body-file`.
- phpunit.xml lists a `tests/Unit` suite; if the folder is missing, `php artisan test` exits 2 before running anything (keep `tests/Unit/.gitkeep`).
- No `crontab` command on Hostinger shared hosting. Add cron jobs through the Hostinger API
  (`hosting_cron-jobs_create`) or hPanel → Advanced → Cron Jobs.
- **Never upload files into `public_html` by hand.** Anything there can be downloaded by anyone
  (a zip with a SQL dump was public at govoyza.com/… until deleted). Uploads go outside the web roots, and code arrives only via CI.
- A server `.env` value containing `$` must be in **single quotes** (`DB_PASSWORD='pa$$word'`).
  Unquoted or double-quoted, Laravel's dotenv expands `$name` as a variable and the password silently breaks.
- nano: Ctrl+X alone discards edits unless you answer `Y`; save with Ctrl+O, Enter first. Verify
  a secret was saved by checking its length over SSH, never by printing it.
- The owner sometimes pastes template placeholders (`HOST_HERE`, `PROD_DB_NAME`, …) instead of real values.
  Never guess or proceed with them; ask again.
- `deploy/post-deploy.sh` must be committed. CI rsyncs the checkout, so untracked deploy files never reach the server.

## Laravel
- The local Windows PHP has no `intl`, so don't use `NumberFormatter` (Money formats by hand). Production has intl.
- If `composer` is not recognized in PowerShell after installation, ensure its install directory is on PATH and restart the terminal (or reload VS Code); verify with `composer --version`.
