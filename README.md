# Vehicle Tracking TOMECO

A local web application for TOMECO parking violation records in Tacloban City. Built with Laravel 12, Nginx, PHP-FPM, PostgreSQL, and Docker Compose.

## Features

- Vehicle registration and printable QR tags
- QR lookup, driver-license QR/PDF417 scanning, and structured parking ticket forms
- Google Maps incident pins and report locations
- Private photo evidence and issued, paid, or appealed ticket status
- Service-based 30-day appearance and 15-day payment follow-up, with an administrator review queue after day 45
- Admin, enforcer, and viewer access levels
- Activity log, search, reports, print view, and CSV export
- Location, time, category, and vehicle-type trends with a simple four-week statistical forecast

The forecast needs at least 20 tickets across 8 of the last 12 weeks. It is a planning estimate, not a computer-vision detector. The app does not call an external AI or cloud API.

## Run locally

See [DOCKER.md](DOCKER.md) for setup, environment variables, and the two developer admin accounts. The passwords live only in your local `.env` file, which is ignored by Git. Copy `.env.example` to `.env`, fill in the required values, then run:

```sh
docker compose up --build -d
```

Open http://localhost:8080. The starter violation categories have zero default penalties; set the official amounts before operational use.

## Checks

```sh
composer install
php artisan test
npm ci
npm run test:frontend
npm run build
```

## GitHub Actions

The CI workflow runs on every branch push and pull request. It runs PHP unit/domain tests, backend HTTP workflow tests, driver-license barcode parser tests, a Vite production build, and Docker builds for the PHP and Nginx images.

The CD workflow runs only after CI succeeds for a push to `main`. It publishes the tested images to GitHub Container Registry (`ghcr.io`) with `latest` and commit-specific tags. It publishes images for deployment elsewhere; it does not deploy to a server.
