# Fintrax

Personal finance tracker built on Laravel + Livewire. Track accounts, income/expense/transfer
transactions, loans (lent/borrowed), and budgets with progress alerts.

## Stack

- PHP 8.5, Laravel 13
- Livewire 4 (native SFC, no Volt)
- Livewire Flux (Free edition) for UI
- Fortify for authentication (login, register, password reset, email verification, 2FA, passkeys)
- Tailwind CSS 4
- SQLite (default) / MySQL (optional)

## Features

- **Accounts** — cash, bank, and mobile wallet balances
- **Transactions** — income, expense, and transfer entries with category and account filters
- **Loans** — track money lent or borrowed, log payments, auto-settle when paid off
- **Budgets** — monthly/weekly/custom budgets with spend progress
- **Categories** — custom income/expense categories per user
- **Dashboard** — balance summary, monthly income/expense/net, recent transactions

## Setup

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

## Testing

```bash
php artisan test --compact
```

## Code Style

```bash
vendor/bin/pint --dirty --format agent
```

See `CLAUDE.md` for full architecture notes and build conventions.
