# Wanguku Housing

Laravel 12 website and farm workspace for Wanguku Housing. This project is separate from the earlier PHP site and its `wanguku_farm` database.

## Included features

- Public farm vision and produce marketplace with farmer and customer accounts.
- Farmer listings with photo uploads, profile/location information and WhatsApp contact.
- Customer order requests, stock reservation, farmer status updates and customer order history. Payment is coordinated directly with the farm; online checkout is not connected.
- Farmer resource and knowledge exchange board.
- Daily flock and production records for headcount, eggs, feed, water and deaths.
- Incubation batches with estimated candling and hatch dates, plus hatch outcome records.
- Preventive-care schedules and health observation notes.
- Sensor registration and a token-protected water/feed reading API with low-threshold alerts. It records telemetry; it does not control pumps, valves or feeders.
- Vision 2035 seven-floor planning and circular resource-flow records.
- Date-filtered reports for production, stock, order activity and paid orders recorded in the app.

## Requirements

- PHP 8.2 or newer with PDO SQLite enabled for the local setup below; PDO MySQL is also available for MySQL/MariaDB deployments.
- Composer 2.

## Run locally on macOS with XAMPP PHP

From this project folder:

```sh
/Applications/XAMPP/bin/php ../composer.phar install --no-dev
/Applications/XAMPP/bin/php artisan key:generate
touch database/database.sqlite
env DB_CONNECTION=sqlite DB_DATABASE="$PWD/database/database.sqlite" SESSION_DRIVER=file CACHE_STORE=file /Applications/XAMPP/bin/php artisan migrate --force
env DB_CONNECTION=sqlite DB_DATABASE="$PWD/database/database.sqlite" SESSION_DRIVER=file CACHE_STORE=file /Applications/XAMPP/bin/php artisan serve --port=8001
```

Open the address Artisan prints. If port 8001 is busy, use another port and open that address. The SQLite database, `.env`, uploaded files and generated credentials are excluded from Git.

## Sensor API

Register a device from **Farm workspace → Sensor monitoring**. The app shows its bearer token once; keep it private. A sensor posts JSON containing `value` and optional `captured_at` to `/api/farm/devices/{device-id}/readings`. Use HTTPS for a public deployment. Hardware must be installed and configured separately.

## Safety and deployment notes

The circular-systems page records planning assumptions and material flows; it does not assess structural safety, wastewater quality, food safety, animal welfare or whether material reuse is safe. Obtain qualified local review before acting on those plans. Health records support observation and follow-up; they do not diagnose or prescribe treatment.

The Laravel app must be served from this project's `public/` directory. For a public deployment, configure secure HTTPS, mail delivery for password resets, backups and the appropriate production database. Configure a payment provider only after its merchant credentials and callback endpoint are ready. Keep all credentials out of committed files.
