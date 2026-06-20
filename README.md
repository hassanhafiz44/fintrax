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
