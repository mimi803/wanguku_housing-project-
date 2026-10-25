# Farmers Connect

Laravel 12 website and farm operations workspace connecting local farmers with customers and a farmer-to-farmer resource network. The platform grew from the Wanguku farm in Juja, operating since 2021.

## Included features

- Public farm vision and produce marketplace with farmer and customer accounts.
- Farmer listings with photo uploads, profile/location information and WhatsApp contact.
- Interactive OpenStreetMap showing only farms whose owners opt in; customers see their saved location only in their own signed-in view or can locate themselves for a one-time private pin.
- Persistent light and dark themes with a responsive research, innovation and farmer-connect experience.
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
- Internet access in the browser for Leaflet and OpenStreetMap map tiles.

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

## Free Render trial

The repository includes a Dockerfile and `render.yaml` Blueprint for a temporary public trial. Push the project to GitHub, sign in to Render, choose **New → Blueprint**, connect `mimi803/wanguku_housing-project-`, and apply the Blueprint from the repository root. It creates a free web service and a free PostgreSQL database, runs migrations at startup, and sets an application key. Render shows the public `onrender.com` address in the service dashboard after the first deploy succeeds.

Render's free web service sleeps after 15 minutes without traffic and its filesystem is temporary, so uploaded profile/product photos can disappear after a restart or deploy. The free PostgreSQL database expires after 30 days and has no backups; export any trial data before expiry. Use this setup for evaluation only, not real farm/customer records. Resource Center synchronization is disabled in the trial until that separate app is hosted at a public HTTPS address and its shared token is configured. Password reset emails also require configuring a production mail provider.

## Sensor API

Register a device from **Farm workspace → Sensor monitoring**. The app shows its bearer token once; keep it private. A sensor posts JSON containing `value` and optional `captured_at` to `/api/farm/devices/{device-id}/readings`. Use HTTPS for a public deployment. Hardware must be installed and configured separately.

## Safety and deployment notes

The circular-systems page records planning assumptions and material flows; it does not assess structural safety, wastewater quality, food safety, animal welfare or whether material reuse is safe. Obtain qualified local review before acting on those plans. Health records support observation and follow-up; they do not diagnose or prescribe treatment.

The Laravel app must be served from this project's `public/` directory. For a public deployment, configure secure HTTPS, mail delivery for password resets, backups and the appropriate production database. Configure a payment provider only after its merchant credentials and callback endpoint are ready. Keep all credentials out of committed files.

## Resource Center map connection

When the Resource Center Docker app is running, set `RESOURCE_MAP_SYNC_ENABLED=true`, `RESOURCE_MAP_URL=http://127.0.0.1:8080`, `RESOURCE_MAP_PUBLIC_URL=http://127.0.0.1:8080/map`, and the shared `RESOURCE_MAP_TOKEN` in `.env`. Use the same token as `WANGUKU_SYNC_TOKEN` in the Resource Center root `.env`. Run `php artisan resource-map:sync-farms` once to publish existing farmers who opted in. Thereafter profile consent, new produce listings and stock changes update the Resource Center automatically. Only opted-in farmer locations and available product information are sent; buyer locations are never sent to the Resource Center.
# Farmers Connect deployment setup

The Render service keeps the existing `wanguku-housing-project` address while displaying the Farmers Connect brand. Before phone-based signup, OTP login, or password reset can send messages, add these private values in **Render → service → Environment**:

- `AFRICASTALKING_USERNAME`
- `AFRICASTALKING_API_KEY`
- `AFRICASTALKING_SENDER_ID` (optional; use only a sender name approved for the account)

Do not commit provider keys. Farmer accounts remain private until an administrator reviews and approves them. Marketplace orders currently request an order; they do not collect payment. M-Pesa/Daraja credentials and callback handling are not part of this deployment.
