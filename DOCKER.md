# Docker setup

This is a Laravel 12 parking violation system with Nginx, PHP-FPM, and a local PostgreSQL database in Docker. It includes vehicle QR tags, ticket recording, private photo evidence, ticket status, role-based accounts, reports, a CSV export, an activity log, and a simple data-based forecast.

## Run locally

1. Copy `.env.example` to `.env` if `.env` does not exist.
2. Generate an application key: `php artisan key:generate` (or set `APP_KEY` in `.env` to a Laravel-generated key). Set `DEV_ADMIN_1_*` and `DEV_ADMIN_2_*` in the local `.env` file, using distinct passwords of at least 20 characters.
3. Run `docker compose up --build -d`.
4. Open <http://localhost:8080> and sign in with either developer admin account listed in `.env`.

The app waits for PostgreSQL to become healthy, runs database migrations and category seeding, then syncs exactly two local developer admin accounts from `.env`. Nginx serves the public files and sends PHP requests to the app container. Database data and Laravel's `storage` directory are kept in Docker volumes. The sync updates the two accounts' passwords from `.env` on each restart, so change these passwords in `.env` and restart the app when rotating them.

Admins can register vehicles, print QR tags, manage violation types and accounts, and update ticket statuses. Enforcers can scan tags and issue tickets. Viewers have read-only access. Five example violation types are seeded on startup with a zero default penalty; an admin should set the official amounts before operational use. Forecasting starts after 20 tickets across at least 8 of the last 12 weeks. Its numbers are statistical estimates, not a guarantee or a computer-vision detector.

Use `docker compose logs -f app` to inspect startup errors. Use `docker compose down` to stop the services; this keeps their volumes.

## Configuration

`compose.yaml` uses the `APP_KEY` and `DB_PASSWORD` values from `.env`. The example database password is for local development only. Change it before putting the app on a shared network or server. If you change the password after the database volume has been initialized, update the existing PostgreSQL user password too; changing the Compose variable alone will not change that user.

The local `.env` file is excluded from Git and Docker build context. It contains plaintext secrets, so keep access to the workspace and Docker daemon limited to developers. Never copy the developer admin variables into a production deployment. The app does not call an external AI or cloud API; its trend and forecast calculations run in Laravel against PostgreSQL.

The default setup serves HTTP on port 8080 for local development. A real deployment needs HTTPS, production environment settings, and regular backups. Phone camera scanning in a browser also requires a secure context.
