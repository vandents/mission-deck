#!/usr/bin/env bash
#
# One-time provisioning for a fresh Ubuntu 24.04 server to host Mission Deck.
# Installs the LAMP stack (Apache + MySQL + PHP 8.4), deploys the app, and
# hardens the box. Run as the default 'ubuntu' user; it uses passwordless sudo.
# Safe to re-run.
#
#   bash deploy/setup-server.sh
#
set -euo pipefail

DOMAIN="mission-deck.app"
APP_DIR="/var/www/mission-deck"
REPO="https://github.com/vandents/mission-deck.git"
ENV_FILE="/etc/mission-deck.env"

echo "==> [1/7] System packages (PHP 8.4 from the ondrej PPA — newer than Ubuntu's 8.3)"
sudo apt-get update -y
sudo apt-get install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt-get update -y
sudo apt-get install -y \
  apache2 mysql-server \
  php8.4 libapache2-mod-php8.4 \
  php8.4-mysql php8.4-intl php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip \
  git unzip ufw fail2ban unattended-upgrades \
  certbot python3-certbot-apache

echo "==> [2/7] Composer (verified installer)"
if ! command -v composer >/dev/null 2>&1; then
  expected="$(curl -sS https://composer.github.io/installer.sig)"
  curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php
  actual="$(php -r "echo hash_file('sha384', '/tmp/composer-setup.php');")"
  [ "$expected" = "$actual" ] || { echo "Composer installer checksum mismatch"; exit 1; }
  sudo php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
  rm -f /tmp/composer-setup.php
fi

echo "==> [3/7] Firewall: allow only SSH + HTTP/HTTPS"
sudo ufw allow OpenSSH
sudo ufw allow 'Apache Full'
sudo ufw --force enable

echo "==> [4/7] Application code"
sudo mkdir -p "$APP_DIR"
sudo chown -R "$USER":www-data "$APP_DIR"
if [ -d "$APP_DIR/.git" ]; then
  git -C "$APP_DIR" pull --ff-only
else
  git clone "$REPO" "$APP_DIR"
fi

echo "==> [5/7] Database credentials + MySQL database/user"
# Generate the app DB password once; reuse it on re-runs so the user keeps working.
if sudo test -f "$ENV_FILE"; then
  db_password="$(sudo grep -E '^DB_PASSWORD=' "$ENV_FILE" | cut -d= -f2-)"
else
  db_password="$(openssl rand -base64 24)"
fi
sudo tee "$ENV_FILE" >/dev/null <<ENV
DB_HOST=localhost
DB_NAME=missiondeck
DB_USER=missiondeck
DB_PASSWORD=${db_password}
YII_ENV=prod
ENV
sudo chgrp www-data "$ENV_FILE"
sudo chmod 640 "$ENV_FILE"

# MySQL 8 on Ubuntu ships secure-by-default (root via auth_socket, bound to localhost,
# no anonymous users or test DB), so we only create the application database and user.
sudo mysql <<SQL
CREATE DATABASE IF NOT EXISTS missiondeck CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'missiondeck'@'localhost' IDENTIFIED BY '${db_password}';
ALTER USER 'missiondeck'@'localhost' IDENTIFIED BY '${db_password}';
GRANT ALL PRIVILEGES ON missiondeck.* TO 'missiondeck'@'localhost';
FLUSH PRIVILEGES;
SQL

echo "==> [6/7] Apache virtual host"
# DocumentRoot is web/ so nothing above it (config, vendor, source) is web-reachable.
# certbot will clone this vhost to an SSL one, carrying the SetEnv credentials over.
sudo tee /etc/apache2/sites-available/mission-deck.conf >/dev/null <<CONF
<VirtualHost *:80>
    ServerName ${DOMAIN}
    ServerAlias www.${DOMAIN}
    DocumentRoot ${APP_DIR}/web

    <Directory ${APP_DIR}/web>
        AllowOverride All
        Require all granted
    </Directory>

    SetEnv DB_HOST localhost
    SetEnv DB_NAME missiondeck
    SetEnv DB_USER missiondeck
    SetEnv DB_PASSWORD "${db_password}"
    SetEnv YII_ENV prod

    ErrorLog \${APACHE_LOG_DIR}/mission-deck-error.log
    CustomLog \${APACHE_LOG_DIR}/mission-deck-access.log combined
</VirtualHost>
CONF
sudo a2enmod rewrite
sudo a2dissite 000-default.conf >/dev/null 2>&1 || true
sudo a2ensite mission-deck.conf

echo "==> [7/7] Deploy app (dependencies, migrations, permissions)"
bash "$APP_DIR/deploy/deploy.sh"
sudo systemctl restart apache2

cat <<DONE

Provisioning complete. The site is live over HTTP at this server's IP.

Next:
  1. Create your login user (no public signup):
       cd $APP_DIR
       sudo -u www-data --preserve-env=DB_HOST,DB_NAME,DB_USER,DB_PASSWORD,YII_ENV \\
         php yii user/create <username> <email> <password>

  2. Once DNS for $DOMAIN points at this box, enable HTTPS:
       sudo certbot --apache -d $DOMAIN -d www.$DOMAIN
DONE
