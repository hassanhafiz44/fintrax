<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- livewire/flux (FLUXUI_FREE) - v2
- livewire/livewire (LIVEWIRE) - v4
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== livewire/core rules ===

# Livewire

- Livewire allow to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

</laravel-boost-guidelines>

# Fintrax — Personal Finance Tracker (Livewire Starter Kit)

## Stack Versions (installed — verified June 2026)

| Dependency      | Installed   | Notes                                                       |
|-----------------|-------------|-------------------------------------------------------------|
| PHP             | 8.5.7       | composer.json requires ^8.3                                  |
| Laravel         | 13.16.1     |                                                             |
| Livewire        | 4.3.1       | **Native SFC** — NOT Volt, NOT v3                            |
| Livewire Flux   | 2.15.0      | **FREE** edition (`livewire/flux`), not Flux Pro            |
| Fortify         | 1.37.2      | Auth backend (login/register/reset/verify/passkeys/2FA)     |
| Tailwind CSS    | 4.0.7       | CSS-first config, no tailwind.config.js                     |
| Alpine.js       | bundled     | Ships inside Flux/Livewire — not a direct npm dep           |
| DB (default)    | SQLite      | `config/database.php` default = sqlite; MySQL optional      |

> ⚠️ **No Volt.** This starter kit uses Livewire 4 **native single-file components**.
> Do NOT use `Livewire\Volt\{state, computed, on}` or `Volt::route()` — they don't exist here.
> ⚠️ **Flux FREE only.** `flux:chart`, `flux:date-picker`, `flux:tabs`,
> `flux:accordion`, `flux:command`, `flux:editor`, `flux:kanban` are **Pro-only** —
> not installed. `flux:table`, `flux:card`, and `flux:pagination` ARE in the free
> edition (verified in installed `livewire/flux` v2.15 — use them directly).
> ⚠️ Auth is **Fortify** (already wired in `FortifyServiceProvider`), not Breeze/Jetstream.

---

## Cost Guardrail — Free / Open-Source ONLY

This project uses **nothing paid**. Do not add, suggest, or write code against any
paid package, edition, or hosted service. Confirmed current state: all composer +
npm deps are free/OSS, no `auth.json`, no Flux Pro, `boost.json` has `cloud:false`
and `nightwatch:false`.

**Banned (paid / licensed):**
- **Flux Pro** components — `flux:chart`, `flux:date-picker`/`calendar`, `flux:tabs`,
  `flux:accordion`, `flux:command`, `flux:editor`, `flux:kanban`. Never add
  `livewire/flux-pro` or an `auth.json`. (`flux:table`, `flux:card`, `flux:pagination`
  are free-edition — allowed.)
- Paid Laravel products: **Nova, Spark, Pulse (paid tier), Nightwatch, Forge,
  Envoyer, Laravel Cloud** (and any "Deploy now" hosted offering).
- Paid third-party APIs (e.g. currency/FX, OCR, SMS) — if multi-currency or
  receipts are needed, use a free/open option or defer the feature.

**Allowed (free/OSS) substitutes:** plain Tailwind markup, Laravel default
pagination views (`->links()`), Chart.js or ApexCharts (MIT) for charts, SQLite
or self-hosted MySQL/Postgres, `php artisan serve` / Sail for local, log mailer
for dev. If a task seems to need something paid, **stop and ask** before adding it.

---

## Project Overview
Personal finance tracker. Features: Auth, Accounts, Transactions (income/expense/transfer),
Loans/Borrowings, Budgets, Dashboard summary.

---

## Phase 1 — Project Bootstrap (DONE)

App already scaffolded from the Livewire starter kit (Laravel 13 + Livewire 4 +
Flux Free + Fortify + Tailwind 4). Existing auth (login/register/reset/verify/
passkeys) and settings (profile/security/appearance) pages are in place. Skip
re-scaffolding — build the finance domain on top.

DB ships as **SQLite** (`database/database.sqlite`). Keep SQLite for local dev, or
switch to MySQL by editing `.env` (`DB_CONNECTION=mysql`, `DB_DATABASE=fintrax`, …)
— decimal/enum columns below work on both.

```bash
php artisan migrate          # baseline auth migrations already present
npm install && npm run build # or: composer run dev  (serve+queue+pail+vite)
```

---

## Phase 2 — Database Schema

### Migrations (create and run in this order)

#### `accounts` — user wallets/banks
```php
Schema::create('accounts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('name');                           // e.g. "Cash", "HBL", "JazzCash"
    $table->enum('type', ['cash', 'bank', 'mobile_wallet', 'other'])->default('cash');
    $table->decimal('balance', 15, 2)->default(0);
    $table->string('currency', 3)->default('PKR');
    $table->string('color', 7)->default('#6366f1');
    $table->boolean('is_default')->default(false);
    $table->timestamps();
});
```

#### `categories`
```php
Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->enum('type', ['income', 'expense']);
    $table->string('icon')->nullable();
    $table->string('color', 7)->default('#6366f1');
    $table->boolean('is_system')->default(false); // seeded defaults
    $table->timestamps();
});
```

#### `transactions`
```php
Schema::create('transactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('account_id')->constrained()->cascadeOnDelete();
    $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
    $table->enum('type', ['income', 'expense', 'transfer']);
    $table->decimal('amount', 15, 2);
    $table->string('note')->nullable();
    $table->date('transacted_at');
    $table->timestamps();
});
```

#### `loans` — covers both "I lent" and "I borrowed"
```php
Schema::create('loans', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('contact_name');
    $table->enum('direction', ['lent', 'borrowed']); // lent = I gave, borrowed = I took
    $table->decimal('amount', 15, 2);
    $table->decimal('remaining', 15, 2);
    $table->date('due_date')->nullable();
    $table->enum('status', ['active', 'settled'])->default('active');
    $table->string('note')->nullable();
    $table->timestamps();
});
```

#### `loan_payments`
```php
Schema::create('loan_payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
    $table->decimal('amount', 15, 2);
    $table->string('note')->nullable();
    $table->date('paid_at');
    $table->timestamps();
});
```

#### `budgets`
```php
Schema::create('budgets', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
    $table->string('name');
    $table->decimal('amount', 15, 2);
    $table->enum('period', ['monthly', 'weekly', 'custom'])->default('monthly');
    $table->date('start_date');
    $table->date('end_date')->nullable(); // null = auto-computed from period
    $table->timestamps();
});
```

---

## Phase 3 — Models & Relationships

### Model style (match existing `app/Models/User.php`)

House style: `#[ObservedBy]` attribute for observers (Laravel 13), and a
`casts()` **method** (not a `$casts` property) — same as `User::casts()`.

```php
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(TransactionObserver::class)]
class Transaction extends Model
{
    protected $guarded = ['id'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'transacted_at' => 'date',
            'amount'        => 'decimal:2',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
}
```

> `User` already declares `#[Fillable]`/`#[Hidden]` attributes and a `casts()`
> method — just add the `HasMany` relations below; don't restructure it.

### `User` (add to existing model)
```php
public function accounts(): HasMany { return $this->hasMany(Account::class); }
public function transactions(): HasMany { return $this->hasMany(Transaction::class); }
public function categories(): HasMany { return $this->hasMany(Category::class); }
public function loans(): HasMany { return $this->hasMany(Loan::class); }
public function budgets(): HasMany { return $this->hasMany(Budget::class); }
```

### `Loan`
```php
/** @return array<string, string> */
protected function casts(): array
{
    return ['due_date' => 'date', 'amount' => 'decimal:2', 'remaining' => 'decimal:2'];
}

public function payments(): HasMany { return $this->hasMany(LoanPayment::class); }

public function isOverdue(): bool
{
    return $this->status === 'active' && $this->due_date?->isPast();
}
```

### `Budget`
Use the modern `Attribute` accessor (Laravel 9+) instead of the legacy
`getXAttribute` magic — computed columns, nothing stored.

```php
use Illuminate\Database\Eloquent\Casts\Attribute;

/** @return Attribute<float, never> */
protected function spent(): Attribute
{
    return Attribute::get(fn (): float => (float) Transaction::query()
        ->where('user_id', $this->user_id)
        ->where('category_id', $this->category_id)
        ->where('type', 'expense')
        ->whereBetween('transacted_at', [
            $this->start_date,
            $this->end_date ?? now()->endOfMonth(),
        ])
        ->sum('amount'));
}

/** @return Attribute<int, never> */
protected function progressPercent(): Attribute
{
    return Attribute::get(fn (): int => $this->amount > 0
        ? min(100, (int) (($this->spent / $this->amount) * 100))
        : 0);
}
```

---

## Phase 4 — Observers

### `TransactionObserver` — keeps account balance in sync

```php
public function created(Transaction $transaction): void
{
    match ($transaction->type) {
        'income'   => $transaction->account->increment('balance', $transaction->amount),
        'expense'  => $transaction->account->decrement('balance', $transaction->amount),
        default    => null, // transfer handled separately
    };
}

public function deleted(Transaction $transaction): void
{
    // reverse
    match ($transaction->type) {
        'income'   => $transaction->account->decrement('balance', $transaction->amount),
        'expense'  => $transaction->account->increment('balance', $transaction->amount),
        default    => null,
    };
}
```

### `LoanPaymentObserver` — decrements remaining and auto-settles
```php
public function created(LoanPayment $payment): void
{
    $loan = $payment->loan;
    $loan->decrement('remaining', $payment->amount);
    if ($loan->fresh()->remaining <= 0) {
        $loan->update(['status' => 'settled']);
    }
}
```

### `UserObserver` — seeds defaults on registration
```php
public function created(User $user): void
{
    // Default account
    $user->accounts()->create(['name' => 'Cash', 'type' => 'cash', 'is_default' => true]);

    // Default categories
    $expense = ['Food', 'Transport', 'Utilities', 'Shopping', 'Health', 'Entertainment', 'Rent', 'Other'];
    $income  = ['Salary', 'Freelance', 'Business', 'Gift', 'Other'];

    foreach ($expense as $name) {
        $user->categories()->create(['name' => $name, 'type' => 'expense', 'is_system' => true]);
    }
    foreach ($income as $name) {
        $user->categories()->create(['name' => $name, 'type' => 'income', 'is_system' => true]);
    }
}
```

**Registration:** prefer the `#[ObservedBy(...)]` attribute on each model
(matches the house style on `User`) — no `AppServiceProvider` wiring needed.
Add `#[ObservedBy(UserObserver::class)]` to the existing `User` model.

---

## Phase 5 — Policies

```bash
php artisan make:policy TransactionPolicy --model=Transaction
php artisan make:policy LoanPolicy --model=Loan
php artisan make:policy BudgetPolicy --model=Budget
php artisan make:policy AccountPolicy --model=Account
```

All policies: user can only view/update/delete their **own** records.

**No registration needed** — Laravel 11+ auto-discovers `App\Policies\XPolicy`
for `App\Models\X`. There is no `AuthServiceProvider` in this app. Only call
`Gate::policy()` in `AppServiceProvider::boot()` if you break the naming convention.

---

## Phase 6 — Livewire Components (NATIVE SFC, not Volt)

This app registers component namespaces in `config/livewire.php`:
`pages` → `resources/views/pages`, `layouts` → `resources/views/layouts`.
Full-page finance components go under `resources/views/pages/` so they resolve
as `pages::<name>` — same as the existing `pages::settings.profile`.

SFC files use an **emoji prefix** (`⚡name.blade.php`) and a `new class extends
Component` block followed by markup — exactly like the existing settings pages.

Generate with:
```bash
php artisan make:livewire pages.transactions.index --sfc --emoji=true
```

### Component List

| Component                  | View id (route target)          | Route               | Purpose                             |
|----------------------------|---------------------------------|---------------------|-------------------------------------|
| dashboard                  | `pages::dashboard`              | `/dashboard`        | Summary cards + recent transactions |
| transactions/index         | `pages::transactions.index`    | `/transactions`     | Filterable, paginated list          |
| transactions/form          | `pages::transactions.form`     | modal (slide-over)  | Add/Edit transaction                |
| loans/index                | `pages::loans.index`           | `/loans`            | Active + settled loans              |
| loans/form                 | `pages::loans.form`            | modal               | Add loan/borrow + log payment       |
| budgets/index              | `pages::budgets.index`         | `/budgets`          | Budget list with progress bars      |
| budgets/form               | `pages::budgets.form`          | modal               | Add/Edit budget                     |
| accounts/index             | `pages::accounts.index`        | `/accounts`         | Account cards with balances         |
| accounts/form              | `pages::accounts.form`         | modal               | Add/Edit account                    |
| settings/categories        | `pages::settings.categories`   | `/settings/categories` | Manage custom categories         |

### Native SFC Pattern (Livewire 4 — the style this app actually uses)

```php
<?php
// resources/views/pages/transactions/⚡index.blade.php

use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Transactions')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';
    public string $type = '';
    public string $category_id = '';
    public string $account_id = '';
    public string $date_from = '';
    public string $date_to = '';
    public int $per_page = 15;

    #[Computed]
    public function transactions(): LengthAwarePaginator
    {
        return auth()->user()->transactions()
            ->with(['category', 'account'])
            ->when($this->search,      fn ($q) => $q->where('note', 'like', "%{$this->search}%"))
            ->when($this->type,        fn ($q) => $q->where('type', $this->type))
            ->when($this->category_id, fn ($q) => $q->where('category_id', $this->category_id))
            ->when($this->account_id,  fn ($q) => $q->where('account_id', $this->account_id))
            ->when($this->date_from,   fn ($q) => $q->where('transacted_at', '>=', $this->date_from))
            ->when($this->date_to,     fn ($q) => $q->where('transacted_at', '<=', $this->date_to))
            ->orderByDesc('transacted_at')
            ->paginate($this->per_page);
    }

    public function updating($name): void
    {
        // reset paging whenever a filter changes
        $this->resetPage();
    }

    #[\Livewire\Attributes\On('transaction-saved')]
    public function refreshList(): void
    {
        unset($this->transactions); // bust the computed cache
    }
}; ?>

<x-layouts::app :title="__('Transactions')">
    <div class="space-y-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search…') }}" icon="magnifying-glass" />

        {{-- flux:table is free-edition — prefer it over plain markup below --}}
        <div class="divide-y divide-zinc-200 dark:divide-zinc-700 rounded-xl border border-zinc-200 dark:border-zinc-700">
            @forelse ($this->transactions as $tx)
                <div class="flex items-center justify-between p-4" wire:key="tx-{{ $tx->id }}">
                    <div>
                        <p class="font-medium">{{ $tx->note ?? $tx->category?->name }}</p>
                        <flux:text class="text-xs">{{ $tx->transacted_at->format('d M Y') }}</flux:text>
                    </div>
                    <flux:badge :color="$tx->type === 'income' ? 'green' : ($tx->type === 'expense' ? 'red' : 'blue')">
                        {{ $tx->amount }}
                    </flux:badge>
                </div>
            @empty
                <div class="p-8 text-center"><flux:text>{{ __('No transactions yet') }}</flux:text></div>
            @endforelse
        </div>

        {{-- flux:pagination is free-edition — prefer it over $paginator->links() --}}
        {{ $this->transactions->links() }}
    </div>
</x-layouts::app>
```

### Dashboard computed properties (native, inside the class)
```php
#[Computed] public function totalBalance(): float { return (float) auth()->user()->accounts()->sum('balance'); }

#[Computed] public function monthIncome(): float {
    return (float) auth()->user()->transactions()
        ->where('type', 'income')->whereMonth('transacted_at', now()->month)->sum('amount');
}

#[Computed] public function monthExpense(): float {
    return (float) auth()->user()->transactions()
        ->where('type', 'expense')->whereMonth('transacted_at', now()->month)->sum('amount');
}

#[Computed] public function netThisMonth(): float { return $this->monthIncome - $this->monthExpense; }
```
In Blade reference computed props with `$this->` (e.g. `{{ $this->totalBalance }}`).

### Key Livewire 4 Patterns (as used in this codebase)
- `wire:navigate` for SPA-feel transitions (already used in layouts/nav).
- Modal → list refresh: child `$this->dispatch('transaction-saved')`, parent
  `#[On('transaction-saved')]` method that does `unset($this->transactions)`.
- Authorize inside actions: `$this->authorize('update', $model)`.
- `wire:model.live` (optionally `.debounce.300ms`) for instant filter reactivity.
- `#[Locked]` on IDs passed via wire (see `pages::settings.security`).
- `#[Url]` on filters to keep state shareable/bookmarkable.
- Toasts via `Flux::toast(variant: 'success', text: __('Saved.'))` (see profile page).
- Child/modal components embed with `<livewire:pages::transactions.form />`.

---

## Phase 7 — Routes

Use `Route::livewire(uri, 'pages::component')` — the same helper the existing
`routes/settings.php` uses. No Volt.

```php
// routes/web.php — Livewire 4 full-page components
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('/dashboard', 'pages::dashboard')->name('dashboard');
    Route::livewire('/transactions', 'pages::transactions.index')->name('transactions.index');
    Route::livewire('/loans', 'pages::loans.index')->name('loans.index');
    Route::livewire('/budgets', 'pages::budgets.index')->name('budgets.index');
    Route::livewire('/accounts', 'pages::accounts.index')->name('accounts.index');
    Route::livewire('/settings/categories', 'pages::settings.categories')->name('settings.categories');
});
```

> The current `web.php` defines `/dashboard` via `Route::view('dashboard', 'dashboard')`.
> Replace that line with the `Route::livewire` version above once the dashboard
> component exists.

---

## Phase 8 — Flux UI Components to Use

Flux **FREE** is bundled. Use these (all available in Free):

| Need              | Flux Component (Free)                                   |
|-------------------|--------------------------------------------------------|
| Buttons           | `<flux:button>`                                        |
| Inputs            | `<flux:input>`, `<flux:textarea>`                      |
| Selects           | `<flux:select>`                                        |
| Checkbox / radio  | `<flux:checkbox>`, `<flux:radio>` (segmented)          |
| Modals            | `<flux:modal>`                                         |
| Badges            | `<flux:badge>`                                         |
| Headings / text   | `<flux:heading>`, `<flux:text>`, `<flux:subheading>`   |
| Navigation        | `<flux:sidebar>`, `<flux:navbar>`, `<flux:navlist>`    |
| Menus / dropdowns | `<flux:dropdown>`, `<flux:menu>`, `<flux:profile>`     |
| Toasts / alerts   | `<flux:toast>`, `<flux:callout>`                       |
| Misc              | `<flux:separator>`, `<flux:tooltip>`, `<flux:avatar>`, `<flux:icon.*>` |
| Tables            | `<flux:table>`, `<flux:table.columns>`, `<flux:table.rows>` (free edition) |
| Cards             | `<flux:card>` (free edition)                            |
| Pagination        | `<flux:pagination :paginator="...">` or `<flux:table :paginate="...">` (free edition) |

> ❌ **NOT in Free** (Flux Pro — do not use): `flux:chart`, `flux:date-picker`/
> `calendar`, `flux:tabs`, `flux:accordion`, `flux:command`, `flux:editor`,
> `flux:kanban`.
>
> Substitutes:
> - **Charts** → a JS lib (Chart.js/ApexCharts) wired through Alpine, or skip for v1.

---

## Phase 9 — Tailwind CSS v4 Notes

No `tailwind.config.js`. Config lives in CSS. `resources/css/app.css` **already**
imports `tailwindcss` + `flux.css`, sets `@source` paths, a `dark` custom variant,
zinc overrides, and an `@theme` block with accent colors. **Do not overwrite it** —
add the finance colors into the existing `@theme` block:

```css
/* append inside the existing @theme { … } in resources/css/app.css */
--color-income: oklch(0.723 0.219 149.579);   /* green   */
--color-expense: oklch(0.577 0.245 27.325);   /* red     */
--color-transfer: oklch(0.623 0.214 259.815); /* blue    */
```

Then use `text-income`, `bg-expense/10`, etc. Content detection is automatic via
the existing `@source` lines — no `content: []` array needed. Rebuild after
changes: `npm run build` (or `npm run dev`).

---

## Phase 10 — UI Design Guidelines

- Use Flux sidebar layout (comes pre-built in the starter kit)
- Transaction type badge colors: `income` → green, `expense` → red, `transfer` → blue
- Loan badges: `overdue` → orange, `active` → yellow, `settled` → green
- Budget progress bar: <70% green, 70-90% yellow, >90% red

### Dashboard Layout (top to bottom)
1. **Summary row** — Total Balance | Month Income | Month Expense | Net
2. **Active Loans strip** — horizontal scroll, overdue highlighted
3. **Budget Alerts** — budgets >80% spent
4. **Recent Transactions** — last 10 + quick-add FAB

---

## Build Order for Claude Code

App is already bootstrapped. Run the remaining phases in strict order:
1. (done) Starter kit installed — Livewire + Fortify + Flux Free.
2. Pick DB: keep SQLite or set MySQL in `.env`.
3. New migrations → `php artisan migrate`.
4. Models (User additions, Account, Category, Transaction, Loan, LoanPayment, Budget) —
   `casts()` method + `#[ObservedBy]` attribute style.
5. Policies → **auto-discovered**, no registration (no `AuthServiceProvider`).
6. Observers (Transaction, LoanPayment, User) → register via `#[ObservedBy]`
   attribute on each model (add it to existing `User`).
7. Routes (`routes/web.php`) via `Route::livewire(...)`.
8. Native SFC components — order: dashboard → transactions → loans → budgets →
   accounts → categories. Add Pest feature tests per component (test enforcement).

---

## Artisan Commands Reference

```bash
# Migrations
php artisan make:migration create_accounts_table
php artisan make:migration create_categories_table
php artisan make:migration create_transactions_table
php artisan make:migration create_loans_table
php artisan make:migration create_loan_payments_table
php artisan make:migration create_budgets_table
php artisan migrate

# Models
php artisan make:model Account
php artisan make:model Category
php artisan make:model Transaction
php artisan make:model Loan
php artisan make:model LoanPayment
php artisan make:model Budget

# Observers
php artisan make:observer TransactionObserver --model=Transaction
php artisan make:observer LoanPaymentObserver --model=LoanPayment
php artisan make:observer UserObserver --model=User

# Policies
php artisan make:policy TransactionPolicy --model=Transaction
php artisan make:policy LoanPolicy --model=Loan
php artisan make:policy BudgetPolicy --model=Budget
php artisan make:policy AccountPolicy --model=Account

# Native Livewire 4 SFCs (⚡-prefixed .blade.php under resources/views/pages/)
php artisan make:livewire pages.dashboard --sfc --emoji=true
php artisan make:livewire pages.transactions.index --sfc --emoji=true
php artisan make:livewire pages.transactions.form --sfc --emoji=true
php artisan make:livewire pages.loans.index --sfc --emoji=true
php artisan make:livewire pages.loans.form --sfc --emoji=true
php artisan make:livewire pages.budgets.index --sfc --emoji=true
php artisan make:livewire pages.budgets.form --sfc --emoji=true
php artisan make:livewire pages.accounts.index --sfc --emoji=true
php artisan make:livewire pages.accounts.form --sfc --emoji=true
php artisan make:livewire pages.settings.categories --sfc --emoji=true
```

> `--sfc --emoji=true` produces `resources/views/pages/<path>/⚡<name>.blade.php`,
> resolving as `pages::<path>.<name>` — matching the existing settings pages.
> Verify each flag with `php artisan make:livewire --help` if output differs.

---

## Key Notes

- All user-scoped queries behind `auth()->user()->relation()` — never `Model::all()`
- Default currency PKR — add `currency` to `users` table later if multi-currency needed
- `loan.direction` = `lent` (I gave money) vs `borrowed` (I received money)
- `budget.end_date = null` means auto-compute as `start_date->endOfMonth()` for monthly periods
- Laravel 13 supports PHP native `#[Attribute]` syntax on models — use it for `#[ObservedBy]`
- Flux UI components require Livewire 4 — do NOT mix with Livewire 3 docs
- Tailwind v4 uses `@theme` blocks in CSS, not JS config files