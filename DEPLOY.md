# Deployment

Mission Deck runs on a single hand-configured AWS EC2 instance (Ubuntu 24.04) — a
traditional LAMP box: **L**inux + **A**pache + **M**ySQL + **P**HP 8.4, serving the
Yii2 app. No managed services, no containers in production; the server is provisioned
by one script so the setup is reproducible and reviewable.

## Server facts

| | |
|---|---|
| Host | EC2 t3.micro, Ubuntu 24.04, Elastic IP `44.209.186.140` |
| Domain | `mission-deck.app` (HTTPS only — `.app` is HSTS-preloaded) |
| App path | `/var/www/mission-deck` (repo); Yii app in `app/` |
| Web server | Apache 2.4 + mod_php 8.4, DocumentRoot `app/web/` |
| Database | MySQL 8 on localhost, database `missiondeck` |
| Secrets | `/etc/mission-deck.env` (root:www-data, 640) — DB password, `API_TOKEN`, `YII_ENV`; exposed to Apache via `conf-enabled/mission-deck-env.conf` |
| SSH | `ssh -i ~/.ssh/mission-deck.pem ubuntu@44.209.186.140` |

## First-time provisioning

From your machine, SSH to the box, then:

```sh
git clone https://github.com/vandents/mission-deck.git /tmp/md && \
  bash /tmp/md/deploy/setup-server.sh
```

`setup-server.sh` ([deploy/setup-server.sh](deploy/setup-server.sh)) is idempotent and:

1. Installs Apache, MySQL, PHP 8.4 (from the `ondrej/php` PPA), Composer, and security tools.
2. Enables the UFW firewall (SSH + HTTP/HTTPS only).
3. Clones the repo to `/var/www/mission-deck`.
4. Generates a DB password, writes `/etc/mission-deck.env`, and creates the MySQL database + user.
5. Writes the Apache virtual host (DocumentRoot `web/`, DB credentials via `SetEnv`).
6. Runs [deploy/deploy.sh](deploy/deploy.sh) to install dependencies and migrate.

### Create your login user

There is no public signup. After provisioning:

```sh
cd /var/www/mission-deck/app
sudo -u www-data --preserve-env=DB_HOST,DB_NAME,DB_USER,DB_PASSWORD,YII_ENV \
  php yii user/create <username> <email> <password>
```

### Enable HTTPS

Required before the domain works in a browser (`.app` forces HTTPS). Run **after** the
DNS A record for `mission-deck.app` points at the Elastic IP:

```sh
sudo certbot --apache -d mission-deck.app -d www.mission-deck.app
```

certbot obtains the certificate, adds the SSL virtual host, and sets up the HTTP→HTTPS
redirect and auto-renewal.

## Routine deploys (after the first time)

```sh
ssh -i ~/.ssh/mission-deck.pem ubuntu@44.209.186.140
bash /var/www/mission-deck/deploy/deploy.sh
```

[deploy/deploy.sh](deploy/deploy.sh) pulls the latest code, installs production
dependencies (`composer install --no-dev`), runs new migrations, and reloads Apache.

## DNS (GoDaddy)

| Type | Name | Value |
|------|------|-------|
| A | `@` | `44.209.186.140` |
| CNAME | `www` | `@` (GoDaddy default — leave as is) |

## Notes

- **Why one box, not RDS/ECS:** the role is a LAMP server-administration role; managing
  Apache and MySQL directly is the point. A nightly `mysqldump` is sufficient backup for this app.
- **`YII_ENV=prod`** disables the debug toolbar and Gii (both dev-only) and hides stack traces.
- **If your SSH stops working**, your home IP changed — update the security group's port 22
  rule to your new IP.
