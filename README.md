# Fintrax

Personal finance tracker built on Laravel + Livewire. Track accounts, income/expense/transfer
transactions, loans (lent/borrowed), and budgets with progress alerts.

## Stack

- PHP 8.5, Laravel 13
- Livewire 4 (native SFC, no Volt)
- Livewire Flux (Free edition) for UI
- Fortify for authentication (login, register, password reset, email verification, 2FA, passkeys)
- Tailwind CSS 4
- SQLite (default) / MySQL (optional, see [Database](#database))

## Features

- **Accounts** — cash, bank, and mobile wallet balances
- **Transactions** — income, expense, and transfer entries with category and account filters
- **Loans** — track money lent or borrowed, log payments, auto-settle when paid off
- **Budgets** — monthly/weekly/custom budgets with spend progress
- **Categories** — custom income/expense categories per user
- **Dashboard** — balance summary, monthly income/expense/net, recent transactions

---

## Developer Setup

### Option A — Docker (recommended, zero local PHP/Node needed)

```bash
git clone <repo>
cd fintrax
docker compose up -d
```

That's it. The `app` container installs Composer/npm deps, generates `APP_KEY`,
runs migrations, and serves Laravel at http://localhost:8000. The `vite`
container runs the dev server with hot reload at http://localhost:5173.

Your working directory is bind-mounted into both containers — edit any file on
your host and Laravel/Vite picks it up immediately (PHP changes reload on
request, Vite HMRs JS/CSS). `vendor/` and `node_modules/` live in named Docker
volumes so they don't clutter your host or fight with host-installed deps.

Re-run dependency installs automatically on next `docker compose up` if
`composer.json`/`package.json` change — just `docker compose restart app vite`
if a new dependency doesn't pick up.

Useful commands:

```bash
docker compose exec app php artisan tinker
docker compose exec app php artisan test --compact
docker compose exec app vendor/bin/pint --dirty --format agent
docker compose down            # stop
docker compose down -v         # stop + wipe vendor/node_modules volumes
```

### Option B — Local PHP/Node

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install && npm run build
```

Run locally:

```bash
composer run dev   # serves app + queue + pail + vite, all at once
```

### Testing

```bash
php artisan test --compact
```

### Code Style

```bash
vendor/bin/pint --dirty --format agent
```

See `CLAUDE.md` for full architecture notes and build conventions.

---

## Production Deployment

Production runs from prebuilt images (`docker-compose.prod.yml`), not the
source tree — no bind mount, no Composer/npm install at runtime.

```bash
cp .env.example .env
# edit .env: APP_ENV=production, APP_DEBUG=false, APP_URL=https://your-domain,
# APP_KEY=$(php artisan key:generate --show), DB_* if using MySQL (see below)

docker compose -f docker-compose.prod.yml pull
docker compose -f docker-compose.prod.yml up -d
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
```

This starts two services:

- **`app`** — PHP-FPM runtime (`ghcr.io/hassanhafiz44/fintrax-app`), reads
  `.env`, persists `database/` and `storage/` in named volumes.
- **`web`** — nginx (`ghcr.io/hassanhafiz44/fintrax-web`), serves built
  assets and proxies PHP to `app`, exposed on `127.0.0.1:8088`. Put your own
  reverse proxy (nginx/Caddy + TLS) in front of that port.

Images are built and published from the `Dockerfile`'s `app`/`web` stages —
`docker compose -f docker-compose.prod.yml build` works too if you'd rather
build locally instead of pulling from GHCR.

Useful commands:

```bash
docker compose -f docker-compose.prod.yml exec app php artisan tinker
docker compose -f docker-compose.prod.yml logs -f app
docker compose -f docker-compose.prod.yml pull && docker compose -f docker-compose.prod.yml up -d   # deploy new image
```

---

## Database

Default is **SQLite** — zero setup, file lives in a named volume
(`fintrax-db` in prod, bind-mounted `database/` in dev) and just works for
single-instance deployments.

**MySQL is optional and not bundled** in either compose file — there's no
`mysql`/`db` service in `docker-compose.yml` or `docker-compose.prod.yml`. If
you want MySQL, run it as its own separate container or managed instance,
then point `.env` at it:

```env
DB_CONNECTION=mysql
DB_HOST=<mysql-host>
DB_PORT=3306
DB_DATABASE=fintrax
DB_USERNAME=fintrax
DB_PASSWORD=<password>
```

For dev, add a `mysql` service to your own `docker-compose.override.yml` and
point `DB_HOST` at it. For prod, run MySQL on its own host/container (managed
DB or a separate compose file) rather than folding it into
`docker-compose.prod.yml` — keeps the app stack disposable and the
database's lifecycle independent of app deploys.
