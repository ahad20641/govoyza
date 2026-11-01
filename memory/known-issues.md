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

## GitHub
- Required reviewers on an environment need a public repo or a paid plan (Pro/Team). While the repo
  was private on Free, the API returned 422. It's now public and the `production` rule is in place.

## Money
- The existing schema (inherited Phase 1 app) stores money as `decimal(12,2)` / `decimal(14,2)` in
  services, applications, payments, ledger and agencies. That breaks the BIGINT-minor-units rule.
  Move each table to `*_minor` + `currency` with `MoneyCast` when its task rebuilds it (1.12, 1.17, 1.23, 1.24).
  New tables must use `MoneyCast` from the start.

- Subdomains created in hPanel/API default to `domains/<main>/public_html/<sub>`, inside the main site's web root.
  For a separate app (staging), give the subdomain a folder outside `public_html`. Use the public directory only for same-app hosts (partner/admin).
- `deploy/post-deploy.sh` must be committed. CI rsyncs the checkout, so untracked deploy files never reach the server.

## Laravel
- The local Windows PHP has no `intl`, so don't use `NumberFormatter` (Money formats by hand). Production has intl.
- If `composer` is not recognized in PowerShell after installation, ensure its install directory is on PATH and restart the terminal (or reload VS Code); verify with `composer --version`.
