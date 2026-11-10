#!/usr/bin/env bash
# Runs on the Hostinger server for every deploy (called by .github/workflows/deploy.yml).
# Unpacks a release next to the previous ones, links the shared .env, storage and uploads, installs PHP
# dependencies, migrates, warms the caches and only then switches `current`, so visitors never see a half-deployed
# site. The last five releases are kept for a quick rollback: ln -sfn releases/<older> current
set -euo pipefail

APP="${APP_DIR:-$HOME/apps/creativemark}"
ARCHIVE="$1"
SHA="${2:-manual}"
RELEASE="$APP/releases/$(date +%Y%m%d%H%M%S)-${SHA:0:12}"

mkdir -p "$RELEASE"
tar -xzf "$ARCHIVE" -C "$RELEASE"
rm -f "$ARCHIVE"

ln -sfn "$APP/shared/.env" "$RELEASE/.env"
rm -rf "$RELEASE/storage" && ln -sfn "$APP/shared/storage" "$RELEASE/storage"
# Images uploaded in the dashboard live outside the releases (photography shipped with the site comes with the code).
rm -rf "$RELEASE/public/media/uploads" && ln -sfn "$APP/shared/media-uploads" "$RELEASE/public/media/uploads"

cd "$RELEASE"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress
php artisan migrate --force --database=mariadb_migrate
php artisan optimize

# Atomic switch: the web server follows the symlink to the new release.
ln -sfn "$RELEASE" "$APP/current.next" && mv -Tf "$APP/current.next" "$APP/current"

cd "$APP/releases" && ls -1dt */ | tail -n +6 | xargs -r rm -rf
echo "Deployed $RELEASE"
