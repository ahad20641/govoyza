# Deploying Govoyza to Hostinger

Pipeline: **GitHub → GitHub Actions → SSH → Hostinger**.
`develop` branch → staging · `main` branch → production.

Why not Hostinger's built-in Git deploy? It only copies the repo into `public_html`, and you can't
change the web root on shared plans — that would expose `.env` and the whole app. So CI builds the
assets and syncs the code into a folder *beside* `public_html`, and `public_html` becomes a symlink
to Laravel's `public/` folder.

## Server layout (shared / Business / Cloud plans)

```
/home/uXXXXXXX/domains/govoyza.com/
├── app/                     ← Laravel (synced by CI)
│   ├── .env                 ← created once by hand, never in git
│   ├── public/
│   └── storage/             ← persistent; excluded from rsync
└── public_html → app/public ← symlink (replace the original folder)

/home/uXXXXXXX/domains/staging.govoyza.com/   (same layout, for develop)
```

Subdomains `partner.` and `admin.`: in hPanel create them, then point their folders at the
same `app/public` (symlink `public_html` of each subdomain → `../../govoyza.com/app/public`).
Laravel's `Route::domain()` decides which portal answers.

> On a **Hostinger VPS** the layout is the same idea; you also get Redis, supervisor and Node,
> and can set the web root directly in Nginx. Log a decision before switching.

## One-time setup (owner, ~30 min)

1. **hPanel → Websites → govoyza.com → Advanced → PHP Configuration:** PHP 8.4; enable `intl`, `bcmath`, `gd`/`imagick`, `zip`, `pdo_mysql`, `fileinfo`, `exif`. Set PHP 8.4 for both production and staging.
2. **hPanel → Databases → MySQL:** create `govoyza_prod` (and `govoyza_staging`) with a strong password.
3. **hPanel → Advanced → SSH Access:** enable SSH; note host, username (`uXXXXXXX`) and port (usually `65002`).
4. **Create a deploy key** on your Windows PC (PowerShell; OpenSSH is built into Windows 10/11):
   ```powershell
   ssh-keygen -t ed25519 -C "govoyza-deploy" -f "$env:USERPROFILE\.ssh\govoyza_deploy" -N '""'
   Get-Content "$env:USERPROFILE\.ssh\govoyza_deploy.pub"   # copy this into hPanel
   ```
   Add the `.pub` text in hPanel → SSH Access → SSH Keys. Keep the private key out of the project folder.
5. **First login and folders:**
   ```bash
   ssh -p 65002 -i "$env:USERPROFILE\.ssh\govoyza_deploy" uXXXXXXX@HOST   # from PowerShell
   cd ~/domains/govoyza.com
   mkdir -p app/storage/{app/private/documents,framework/{cache,sessions,views},logs}
   mv public_html public_html_old && ln -s app/public public_html
   nano app/.env        # paste from deploy/env.production.example and fill in
   chmod 600 app/.env
   ```
6. **GitHub → repo → Settings → Secrets and variables → Actions**, add:
   | Secret | Example |
   |---|---|
   | `HOSTINGER_SSH_HOST` | `145.x.x.x` or `srvXXX.hstgr.io` |
   | `HOSTINGER_SSH_PORT` | `65002` |
   | `HOSTINGER_SSH_USER` | `u123456789` |
   | `HOSTINGER_SSH_KEY` | contents of `%USERPROFILE%\.ssh\govoyza_deploy` (private key, no `.pub`) |
   | `PROD_APP_PATH` | `/home/u123456789/domains/govoyza.com/app` |
   | `STAGING_APP_PATH` | `/home/u123456789/domains/staging.govoyza.com/app` |
   | `PROD_URL` / `STAGING_URL` | `https://govoyza.com` / `https://staging.govoyza.com` |
7. **GitHub → Settings → Environments:** create `production` and add yourself as a required reviewer (so production deploys wait for your click).
8. **hPanel → Advanced → Cron Jobs** (every minute, for each environment):
   ```
   /usr/bin/php /home/uXXXXXXX/domains/govoyza.com/app/artisan schedule:run >> /dev/null 2>&1
   ```
   If `php -v` over SSH isn't 8.4, use the full path, e.g. `/opt/alt/php84/usr/bin/php`.
9. **First generate the app key** after the first deploy:
   ```bash
   cd ~/domains/govoyza.com/app && php artisan key:generate && php artisan storage:link
   ```
   Back up `APP_KEY` somewhere safe offline — encrypted passport data is unreadable without it.

## What each deploy does
See `.github/workflows/deploy.yml` and `deploy/post-deploy.sh`:
1. CI: `composer install`, `npm ci && npm run build`, run tests + Pint.
2. rsync code to the server (excluding `.env`, `storage/`, `.git`, `node_modules`, `tests`).
3. On the server: maintenance mode → `composer install --no-dev` → `migrate --force` → cache config/routes/views/events → `queue:restart` → back up.
4. Health check: `curl $URL/up` must return 200, else the job fails.

## Rollback
Re-run the previous successful workflow run in GitHub Actions ("Re-run all jobs") or
`git revert` the bad commit and push. Migrations must be backward-compatible (add columns first,
remove in a later release) so a code rollback never needs a DB rollback.
