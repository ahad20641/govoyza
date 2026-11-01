#!/usr/bin/env bash
# Runs ON the Hostinger server after CI has rsynced the code.
# Usage: bash deploy/post-deploy.sh /home/uXXXXXXX/domains/govoyza.com/app
set -euo pipefail

APP_PATH="${1:?app path required}"
cd "$APP_PATH"

# Pick PHP 8.4 CLI (Hostinger CloudLinux paths first)
for candidate in /opt/alt/php84/usr/bin/php /usr/bin/php8.4 /usr/local/bin/php /usr/bin/php php; do
  if command -v "$candidate" >/dev/null 2>&1; then PHP="$candidate"; break; fi
done
echo "Using PHP: $($PHP -v | head -n1)"

COMPOSER="$(command -v composer || echo "$PHP $HOME/composer.phar")"

if [ ! -f .env ]; then
  echo "ERROR: $APP_PATH/.env missing. Create it from deploy/env.production.example first." >&2
  exit 1
fi

mkdir -p storage/app/public storage/app/private/documents storage/framework/{cache,sessions,views} storage/logs bootstrap/cache

$PHP artisan down --retry=30 || true
trap '$PHP artisan up || true' EXIT

# Hostinger disables proc_open, so Composer can't run its scripts; run package discovery directly.
$COMPOSER install --no-dev --prefer-dist --no-interaction --optimize-autoloader --no-scripts
$PHP artisan package:discover --ansi

$PHP artisan migrate --force
$PHP artisan govoyza:sync-permissions   # idempotent: roles + config/permissions.php

# PHP's symlink() is disabled too, so `artisan storage:link` can't work; the shell can.
[ -L public/storage ] || ln -s ../storage/app/public public/storage

$PHP artisan optimize:clear
$PHP artisan optimize          # config, routes, views, events cache
$PHP artisan queue:restart

$PHP artisan up
trap - EXIT
echo "Deploy finished at $(date -u +%FT%TZ)"
