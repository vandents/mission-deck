# Mission Deck

Web-based UAS (drone) fleet mission control — plan waypoint missions, monitor live telemetry, and review flight logs for a fleet of unmanned aircraft.

**Stack:** PHP 8.4 / Yii2 · MySQL 8.4 · Apache · Bootstrap 5 — deployed on a hand-configured AWS EC2 instance (Ubuntu).

## Local development

Requires Docker Desktop.

```sh
docker compose up -d --build
```

- App: http://localhost:8080
- MySQL: localhost:3306 (`missiondeck` / `secret`, database `missiondeck`)

The Yii2 app lives in [app/](app/) and is bind-mounted into the web container — edit code and refresh, no rebuild needed. Rebuild only when [docker/php/Dockerfile](docker/php/Dockerfile) changes.

Run Composer or Yii console commands inside the container:

```sh
docker compose exec web composer install
docker compose exec web php yii migrate
```

## Project layout

```
app/                  Yii2 application (controllers, models, views, config)
docker/php/           PHP-Apache image: extensions, vhost (docroot = app/web)
docker-compose.yml    Local dev stack: web (Apache+PHP) + db (MySQL)
```

Database credentials are read from environment variables (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`) — see [app/config/db.php](app/config/db.php). Compose supplies them locally; Apache `SetEnv` supplies them in production.

## Roadmap

- [x] Phase 1a: Dockerized LAMP dev environment + Yii2 scaffold
- [ ] Phase 1b: Auth + fleet/asset CRUD, EC2 deployment
- [ ] Phase 2: Waypoint mission planner (Leaflet map) + MySQL schema
- [ ] Phase 3: Telemetry REST API + flight simulator + live dashboard
- [ ] Phase 4: QGroundControl `.plan` import/export, flight replay
