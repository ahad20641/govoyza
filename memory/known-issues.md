# Known issues and gotchas

> Things that bit us once and must not bite again. Add one line each, with the fix.

## Hostinger
- Built-in hPanel Git auto-deploy only copies into `public_html` — do not use it for Laravel. Use the GitHub Actions workflow.
- SSH port is usually **65002**, not 22.
- Node is not reliably available on shared hosting — build Vite assets in GitHub Actions, never on the server.
- No long-running processes: never rely on `queue:work` without `--stop-when-empty` or on Horizon.
- PHP CLI version can differ from the web PHP version. Set both to 8.3 in hPanel and call `php` via full path in cron if needed (`/opt/alt/php83/usr/bin/php` on CloudLinux).

## Laravel
- If `composer` is not recognized in PowerShell after installation, ensure its install directory is on PATH and restart the terminal (or reload VS Code); verify with `composer --version`.
