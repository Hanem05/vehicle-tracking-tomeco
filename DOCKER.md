# Docker setup

This is a Laravel 12 parking violation system with Nginx, PHP-FPM, and a local PostgreSQL database in Docker. It includes vehicle QR tags, browser-based driver-license barcode scanning, ticket recording, Google Maps incident locations, private photo evidence, ticket status, role-based accounts, reports, a CSV export, an activity log, and a simple data-based forecast.

## Run locally

1. Copy `.env.example` to `.env` if `.env` does not exist.
2. Generate an application key without installing the Laravel dependencies: run `php -r "echo 'base64:'.base64_encode(random_bytes(32)), PHP_EOL;"` and copy the output into `.env` as the value of `APP_KEY`. If you already installed the dependencies, `php artisan key:generate` can set it for you. Set `DEV_ADMIN_1_*` and `DEV_ADMIN_2_*` in the local `.env` file, using distinct passwords of at least 20 characters.
3. Run `docker compose up --build -d`.
4. Open <http://localhost:8080> and sign in with either developer admin account listed in `.env`.

To use the incident map and location picker, set `GOOGLE_MAPS_API_KEY` in `.env` to a Google Maps JavaScript API key, enable that API in the associated Google Cloud project, then run `docker compose up -d app` to apply the setting. Restrict the browser key to the Maps JavaScript API and the app's HTTP referrer. Without a key, the rest of the ticket form works and the map panels explain what needs configuration.

The app waits for PostgreSQL to become healthy, runs database migrations and category seeding, then syncs exactly two local developer admin accounts from `.env`. Nginx serves the public files and sends PHP requests to the app container. Database data and Laravel's `storage` directory are kept in Docker volumes. The sync updates the two accounts' passwords from `.env` on each restart, so change these passwords in `.env` and restart the app when rotating them.

Admins can register vehicles, print QR tags, manage violation types and accounts, update ticket statuses, and record subpoena actions taken outside the app. Enforcers can scan vehicle tags, scan QR/PDF417 driver's-license barcodes in the browser, and issue tickets. License images and decoded barcode data are not uploaded; only the reviewed driver name and license number are saved with a ticket. The license number is encrypted using `APP_KEY` and shown only to admins and enforcers. Viewers have read-only access. Five example violation types are seeded on startup with a zero default penalty; an admin should set the official amounts before operational use. Forecasting starts after 20 tickets across at least 8 of the last 12 weeks. Its numbers are statistical estimates, not a guarantee or a computer-vision detector.

The follow-up schedule starts from the recorded notice service date: appearance is due within 30 calendar days; if no timely appearance is recorded, the fee payment window ends 45 days after service. After that deadline, the ticket appears in an administrator review queue. The app does not create, issue, or serve a subpoena automatically; an administrator can record an action completed outside the app.

Use `docker compose logs -f app` to inspect startup errors. Use `docker compose down` to stop the services; this keeps their volumes.

## Configuration

`compose.yaml` uses the `APP_KEY`, `DB_PASSWORD`, and optional `GOOGLE_MAPS_API_KEY` values from `.env`. Google Maps keys are sent to the user's browser, so restrict the key to Maps JavaScript API and your development/production HTTP referrers. The example database password is for local development only. Change it before putting the app on a shared network or server. If you change the password after the database volume has been initialized, update the existing PostgreSQL user password too; changing the Compose variable alone will not change that user.

The local `.env` file is excluded from Git and Docker build context. It contains plaintext secrets, so keep access to the workspace and Docker daemon limited to developers. Never copy the developer admin variables into a production deployment. The app does not call an external AI or cloud API; its trend and forecast calculations run in Laravel against PostgreSQL.

The default setup serves HTTP on port 8080 for local development. A real deployment needs HTTPS, production environment settings, and regular backups. Phone camera scanning in a browser also requires a secure context.
