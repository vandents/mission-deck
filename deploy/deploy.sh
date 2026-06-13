#!/usr/bin/env bash
#
# Deploy / update Mission Deck on the server: pull code, install production
# dependencies, run migrations, and reload Apache. Safe to re-run any time.
#
#   bash deploy/deploy.sh
#
set -euo pipefail

APP_DIR="/var/www/mission-deck"   # git repo
APP_ROOT="$APP_DIR/app"           # Yii2 application lives in app/
ENV_FILE="/etc/mission-deck.env"

cd "$APP_ROOT"

# Load DB credentials + YII_ENV. The file is root:www-data 640, so read it via sudo.
set -a
source <(sudo cat "$ENV_FILE")
set +a

echo "-> Pulling latest code"
git -C "$APP_DIR" pull --ff-only

echo "-> Installing production dependencies"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

# Apache (www-data) owns the only writable directories; run the console as www-data
# too so files written by CLI and by web requests share one owner.
echo "-> Handing writable directories to Apache"
sudo chown -R www-data:www-data runtime web/assets

echo "-> Running database migrations"
sudo -u www-data --preserve-env=DB_HOST,DB_NAME,DB_USER,DB_PASSWORD,YII_ENV \
  php yii migrate --interactive=0

echo "-> Reloading Apache"
sudo systemctl reload apache2

echo "Deploy complete."
