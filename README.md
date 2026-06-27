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
- **Loans** — track money lent or borrowed, log payments with optional account deduction/credit, auto-settle when paid off
- **Budgets** — monthly/weekly/custom budgets with spend progress
- **Categories** — custom income/expense categories per user
- **Dashboard** — balance summary, monthly income/expense/net, recent transactions

---

## Demo Account

A demo account is seeded automatically (dev and prod). Log in with:

- **Email:** `demo@example.com`
- **Password:** `password`

Comes preloaded with 3 accounts, 27 transactions across 3 months, 4 active loans, and 4 budgets.
The demo account resets daily at midnight — any changes made to it will be wiped.

To reseed manually:

```bash
docker compose exec app php artisan demo:reset
```

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
**Mailpit** (local mail catcher) runs at http://localhost:8025 — all outgoing
emails (verification, password reset) are captured there instead of being sent.

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
docker compose exec app php artisan demo:reset   # reseed demo account
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

**Production no longer uses Docker.** It runs on bare-metal PHP-FPM + nginx and
deploys a CI-built artifact into atomic release directories. (Docker is still
used for *local development* via `docker-compose.yml`.)

Server layout under `/opt/fintrax`:

```text
releases/<git-sha>/   full app (code + vendor + public/build), one per deploy
shared/.env           production env, symlinked into each release
shared/storage/       logs/sessions/cache/uploads, persists across releases
shared/database/      SQLite db, persists across releases
current -> releases/<git-sha>   atomic symlink, flipped last
```

**One-time provisioning** (installs PHP 8.5, the FPM pool, the queue systemd
unit, the scheduler cron, and a scoped sudoers rule):

```bash
sudo DEPLOY_USER=<ssh-deploy-user> bash deploy/provision.sh
# then edit /opt/fintrax/shared/.env (set APP_KEY, APP_URL, DB_DATABASE)
```

Provisioning inputs live in `deploy/`:

- `provision.sh` — idempotent one-time setup script.
- `php-fpm/fintrax.conf`, `php-fpm/zz-fintrax.ini` — PHP-FPM pool + opcache/limits.
- `fintrax-queue.service` — systemd unit for `queue:work`.
- `fintrax.hassanpi.com.conf` — host nginx site (serves `current/public` via the
  `/run/php/fintrax.sock` FPM socket; TLS via Certbot).
- `.env.production.example` — template for `shared/.env`.

**Deploys** are automatic on push to `main` (`.github/workflows/deploy.yml`):
GitHub Actions builds the artifact (`composer install --no-dev` + `npm run build`),
ships it over SSH, then runs `migrate` + `DemoSeeder` + `config/route/view:cache`
in the new release, flips the `current` symlink, and reloads php-fpm / restarts
the queue / reloads nginx. The scheduler (`demo:reset` daily) runs via the
www-data cron installed by `provision.sh`.

**Rollback** — point `current` at a previous release and reload:

```bash
ln -sfn /opt/fintrax/releases/<old-sha> /opt/fintrax/current
sudo systemctl reload php8.5-fpm && sudo systemctl restart fintrax-queue && sudo systemctl reload nginx
```

---

## Database

Default is **SQLite** — zero setup, bind-mounted `database/` in dev. In
production the SQLite file lives at `/opt/fintrax/shared/database/database.sqlite`
(`DB_DATABASE` in `shared/.env`) so it persists across releases; each release
symlinks `database/database.sqlite` to it.

**MySQL is optional and not bundled.** If you want MySQL, run it as its own
service or managed instance, then point `shared/.env` at it:

```env
DB_CONNECTION=mysql
DB_HOST=<mysql-host>
DB_PORT=3306
DB_DATABASE=fintrax
DB_USERNAME=fintrax
DB_PASSWORD=<password>
```
