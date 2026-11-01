# Deploying Govoyza to Hostinger

Pipeline: **GitHub → GitHub Actions → SSH → Hostinger**.
`develop` branch → staging · `main` branch → production.

> **Never commit real server details** (Hostinger username, SSH host/IP, port, DB names, passwords).
> They live only in GitHub Actions secrets and the server `.env` files. This doc uses placeholders:
> `uXXXXXXX` (username), `HOST`, `PORT`. `tests/Unit/RepositoryGuardsTest.php` fails if a real
> `u` + 9-digit username shows up in the repo.

Why not Hostinger's built-in Git deploy? It only copies the repo into `public_html`, and you can't
change the web root on shared plans. That would expose `.env` and the whole app. So CI builds the
assets and syncs the code into a folder *outside* any `public_html`, and each site's `public_html`
becomes a symlink to that app's Laravel `public/` folder.

## Server layout (shared / Business / Cloud plans)

```
/home/uXXXXXXX/domains/govoyza.com/
├── app/                        ← production Laravel (synced by CI), outside public_html
│   ├── .env                    ← production only; created by hand, never in git
│   ├── public/
│   └── storage/                ← persistent; excluded from rsync
├── staging-app/                ← staging Laravel (synced by CI), outside any public_html
│   ├── .env                    ← staging only; its own APP_KEY and its own database
│   ├── public/
│   └── storage/
└── public_html → app/public    ← symlink (replaces the original folder)

/home/uXXXXXXX/domains/staging.govoyza.com/   ← staging is its own *website* on the plan
└── public_html → ../govoyza.com/staging-app/public   ← symlink to staging-app/public only
```

- **Production and staging share nothing** except the hosting account: separate app folders,
  separate `.env`, separate `APP_KEY`, separate MySQL database and user.
- **Staging is a separate website, not a subdomain.** Hostinger always puts a subdomain's web root
  under `domains/govoyza.com/public_html/…`, which is the production app once `public_html`
  becomes a symlink. A separate website gets its own `domains/staging.govoyza.com/public_html`.
- **`partner.` and `admin.`** are subdomains of govoyza.com created with "use the website's
  public directory", so their web root is `domains/govoyza.com/public_html`, the same production
  app. Laravel's `Route::domain()` decides which portal answers. On staging only the public site
  host exists; test the partner/admin portals locally until they get staging hostnames.
- **Website slots used:** 2 (govoyza.com + staging.govoyza.com). The partner/admin subdomains don't
  count as websites. Parked domains and separate websites for the portals were considered and
  rejected (see `memory/decisions.md`, "Hostinger hosting layout").

> On a **Hostinger VPS** the layout is the same idea; you also get Redis, supervisor and Node,
> and can set the web root directly in Nginx. Log a decision before switching.

## One-time setup (owner + Claude)

1. **hPanel → Websites → govoyza.com → Advanced → PHP Configuration:** PHP 8.4; enable `intl`,
   `bcmath`, `gd`/`imagick`, `zip`, `pdo_mysql`, `fileinfo`, `exif`. Do the same for the
   `staging.govoyza.com` website.
2. **hPanel → Databases → MySQL:** one database + user for production and a **different** one for
   staging, each with its own strong password. Write the passwords down offline. They go only into
   the server `.env` (step 6), never into chat, git or GitHub.
3. **hPanel → Advanced → SSH Access:** enable SSH; note `HOST`, username `uXXXXXXX` and `PORT`
   (usually `65002`).
4. **Deploy key** on your Windows PC (PowerShell; OpenSSH is built into Windows 10/11):
   ```powershell
   ssh-keygen -t ed25519 -C "govoyza-deploy" -f "$env:USERPROFILE\.ssh\govoyza_deploy" -N '""'
   Get-Content "$env:USERPROFILE\.ssh\govoyza_deploy.pub"   # copy this into hPanel
   ```
   Add the `.pub` text in hPanel → SSH Access → SSH Keys. Keep the private key out of the project folder.
5. **Folders and symlinks** (over SSH, run once):
   ```bash
   ssh -p PORT -i ~/.ssh/govoyza_deploy uXXXXXXX@HOST

   # production
   cd ~/domains/govoyza.com
   mkdir -p app/storage/{app/private/documents,framework/{cache,sessions,views},logs}
   mv public_html public_html_old && ln -s app/public public_html

   # staging: the app lives beside production, outside any public_html
   mkdir -p staging-app/storage/{app/private/documents,framework/{cache,sessions,views},logs}
   cd ~/domains/staging.govoyza.com
   mv public_html public_html_old && ln -s ../govoyza.com/staging-app/public public_html
   ```
6. **Server `.env` files** from `deploy/env.production.example`, one per app, `chmod 600`.
   Fill everything except `DB_PASSWORD`, then the owner types the password over SSH:
   ```bash
   nano ~/domains/govoyza.com/app/.env          # production: set DB_PASSWORD=
   nano ~/domains/govoyza.com/staging-app/.env  # staging:    set DB_PASSWORD=
   ```
   Staging differs from production in: `APP_ENV=staging`, `APP_URL=https://staging.govoyza.com`,
   `GOVOYZA_DOMAIN=staging.govoyza.com`, `LOG_LEVEL=debug`, its own `DB_DATABASE`/`DB_USERNAME`,
   sandbox payment keys only.
7. **GitHub → repo → Settings → Secrets and variables → Actions** (values only here, never in git):
   | Secret | Shape |
   |---|---|
   | `HOSTINGER_SSH_HOST` | `HOST` from hPanel |
   | `HOSTINGER_SSH_PORT` | `PORT` from hPanel (usually `65002`) |
   | `HOSTINGER_SSH_USER` | `uXXXXXXX` |
   | `HOSTINGER_SSH_KEY` | contents of `%USERPROFILE%\.ssh\govoyza_deploy` (private key, no `.pub`) |
   | `PROD_APP_PATH` | `/home/uXXXXXXX/domains/govoyza.com/app` |
   | `STAGING_APP_PATH` | `/home/uXXXXXXX/domains/govoyza.com/staging-app` |
   | `PROD_URL` / `STAGING_URL` | `https://govoyza.com` / `https://staging.govoyza.com` |
   Set them from a terminal without echoing: `Get-Content <keyfile> -Raw | gh secret set HOSTINGER_SSH_KEY`.
8. **GitHub → Settings → Environments:** `staging` and `production`. Production gets yourself as a
   required reviewer and deploys from `main` only. Required reviewers need a **public repo or a paid
   plan** (Pro/Team); on a private Free repo the rule is refused.
9. **Cron, every minute, one per app** (crontab over SSH, or hPanel → Advanced → Cron Jobs):
   ```
   * * * * * /opt/alt/php84/usr/bin/php /home/uXXXXXXX/domains/govoyza.com/app/artisan schedule:run >> /dev/null 2>&1
   * * * * * /opt/alt/php84/usr/bin/php /home/uXXXXXXX/domains/govoyza.com/staging-app/artisan schedule:run >> /dev/null 2>&1
   ```
   Check the PHP path with `ls /opt/alt/php84/usr/bin/php` over SSH. Add each line only after that
   app's first deploy, or it fails every minute.
10. **After each app's first deploy:**
    ```bash
    cd ~/domains/govoyza.com/staging-app && php artisan key:generate --force && php artisan optimize
    ```
    `post-deploy.sh` creates the `public/storage` link with `ln -s` on every deploy. Hostinger's PHP
    disables `symlink()`, `proc_open()` and `exec()`, so `artisan storage:link` and Composer scripts
    don't work there (see `memory/known-issues.md`). Hostinger has no `crontab` command: add step 9's
    lines through the Hostinger API or hPanel.

> **Production's `public_html` is still Hostinger's placeholder.** Swap it for the `app/public`
> symlink (step 5, production part) only at production's first deploy. Before then the symlink
> points at an empty app, and govoyza.com, partner. and admin. would all go down.
    Back up each `APP_KEY` somewhere safe offline. Encrypted passport data is unreadable without it.

## What each deploy does
See `.github/workflows/deploy.yml` and `deploy/post-deploy.sh`:
1. CI: `composer install`, `npm ci && npm run build`, run tests + Pint.
2. rsync code to the server (excluding `.env`, `storage/`, `.git`, `node_modules`, `tests`).
3. On the server: maintenance mode → `composer install --no-dev` → `migrate --force` → `govoyza:sync-permissions` → `db:seed --class=ReferenceDataSeeder` (idempotent) → cache config/routes/views/events → `queue:restart`.
4. Health check: `curl $URL/up` must return 200, else the job fails.

## Changing `.env` on the server
Config is cached on every deploy, so a `.env` edit (for example `MAIL_PASSWORD` via File Manager)
does nothing until you rebuild the cache:
```bash
cd ~/domains/govoyza.com/staging-app && php artisan config:cache
```

## Staff passwords (create-admin, reset-password)
Hostinger disables `shell_exec()`, so PHP can't hide what you type, and a normal prompt would show the
password on screen. `govoyza:create-admin` and `govoyza:reset-password` refuse to prompt there. Use
`deploy/password.sh` instead: it asks twice with the shell's `read -s` (reading straight from the terminal) and
passes the password in with `--password-stdin`. Nothing ends up in shell history or on screen.

Run **one line at a time**; each asks for the password itself, so there is nothing to paste after it:
```bash
cd ~/domains/govoyza.com/staging-app        # production: ~/domains/govoyza.com/app
bash deploy/password.sh reset-password you@example.com
bash deploy/password.sh create-admin --name="Your Name" --email=you@example.com --role=owner
```
Don't paste a block of `read …` lines: the first `read` takes the next pasted line as the password.
Staff passwords need 12+ characters with upper and lower case, a number and a symbol.
`reset-password` signs the account out of every session.

## Staging password
All of staging (every host and path except `/up`) sits behind HTTP Basic Auth (`App\Http\Middleware\StagingGate`)
while `GOVOYZA_STAGING_PASSWORD_HASH` is set in staging's `.env`. Only a bcrypt hash is stored. 20 wrong guesses
per minute per IP lock that IP out for a minute. Set or change the password (username defaults to `govoyza`):
```bash
cd ~/domains/govoyza.com/staging-app
bash deploy/password.sh staging-password
```
The command refreshes the config cache itself and refuses to run when `APP_ENV=production`. To turn the gate
off, delete the `GOVOYZA_STAGING_PASSWORD_HASH` line and run `php artisan config:cache`.

## Admin console (Filament)
- Production: `https://admin.govoyza.com/` (`GOVOYZA_ADMIN_DOMAIN=admin.govoyza.com`, no `GOVOYZA_ADMIN_PATH`).
- Staging has no admin subdomain, so its `.env` has `GOVOYZA_ADMIN_DOMAIN=staging.govoyza.com` and
  `GOVOYZA_ADMIN_PATH=admin`, which puts the console at `https://staging.govoyza.com/admin`.
- Sign in there with a staff account (`create-admin`). The first sign-in makes you set up an authenticator app
  (2FA is required) and shows recovery codes once. Keep them offline.
- Optional IP allowlist: `GOVOYZA_ADMIN_IPS=1.2.3.4,10.0.0.0/8`, then `php artisan config:cache`.
- Filament's CSS/JS are published on each deploy by `post-deploy.sh` (`filament:assets`).

## Partner portal (Filament)
- Production: `https://partner.govoyza.com/`. Staging: `GOVOYZA_PARTNER_DOMAIN=staging.govoyza.com` and
  `GOVOYZA_PARTNER_PATH=partner` → `https://staging.govoyza.com/partner`.
- Partners join only through an invite: admin console → Partners → "Invite partner". The emailed link works once
  and expires after 7 days. Never link the portal from the public site.
- Before any real partner signs: replace `resources/agreements/partner-agreement.md` with the lawyer-approved
  supplier agreement and raise `agreement_version` in `config/govoyza.php`.

## Coming-soon page (production, until the first deploy)
`deploy/coming-soon/` (`index.html` + `.htaccess`) is copied into `~/domains/govoyza.com/public_html`. The
`.htaccess` adds `noindex` for the partner./admin. hosts, which share that web root. Hostinger's placeholder is
kept in `~/domains/govoyza.com/public_html_placeholder_backup/`. At production's first real deploy,
`public_html` becomes the symlink to `app/public` (see "One-time setup"), which replaces this page.

## Test email
After setting `MAIL_PASSWORD` and running `config:cache`: `php artisan govoyza:send-test-email you@example.com`.

## Rollback
Re-run the previous successful workflow run in GitHub Actions ("Re-run all jobs") or
`git revert` the bad commit and push. Migrations must be backward-compatible (add columns first,
remove in a later release) so a code rollback never needs a DB rollback.
