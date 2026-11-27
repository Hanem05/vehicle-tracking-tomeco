# Vehicle Tracking TOMECO

A local web application for TOMECO parking violation records in Tacloban City. Built with Laravel 12, Nginx, PHP-FPM, PostgreSQL, and Docker Compose.

## Features

- Vehicle registration and printable QR tags
- QR lookup and structured parking ticket forms
- Private photo evidence and issued, paid, or appealed ticket status
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
php artisan test
```
