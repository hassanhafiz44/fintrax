<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        if (! $user->wasRecentlyCreated) {
            $this->command->info('Demo data already exists, skipping.');

            return;
        }

        // ── Accounts ──────────────────────────────────────────────────────────
        $cash = $user->accounts()->where('name', 'Cash')->first();

        $hbl = $user->accounts()->create([
            'name' => 'HBL Bank',
            'type' => 'bank',
            'balance' => 0,
            'currency' => 'PKR',
            'color' => '#22c55e',
            'is_default' => false,
        ]);

        $jazz = $user->accounts()->create([
            'name' => 'JazzCash',
            'type' => 'mobile_wallet',
            'balance' => 0,
            'currency' => 'PKR',
            'color' => '#f97316',
            'is_default' => false,
        ]);

        // ── Categories (seeded by UserObserver) ───────────────────────────────
        $cat = fn (string $name) => $user->categories()->where('name', $name)->first();

        $salary = $cat('Salary');
        $freelance = $cat('Freelance');
        $food = $cat('Food');
        $transport = $cat('Transport');
        $utilities = $cat('Utilities');
        $shopping = $cat('Shopping');
        $health = $cat('Health');
        $entertainment = $cat('Entertainment');
        $rent = $cat('Rent');

        // ── Transactions (TransactionObserver fires → balances auto-update) ───
        $transactions = [
            // Month -2
            [$hbl,  $salary,        'income',  85000, 'Monthly salary',          now()->subMonths(2)->startOfMonth()->addDays(1)],
            [$hbl,  $rent,          'expense', 22000, 'House rent',               now()->subMonths(2)->startOfMonth()->addDays(2)],
            [$hbl,  $utilities,     'expense',  3200, 'Electricity bill',         now()->subMonths(2)->startOfMonth()->addDays(3)],
            [$cash, $food,          'expense',  1800, 'Groceries',                now()->subMonths(2)->startOfMonth()->addDays(5)],
            [$cash, $transport,     'expense',   650, 'Fuel',                     now()->subMonths(2)->startOfMonth()->addDays(7)],
            [$jazz, $freelance,     'income',  18000, 'Web project payment',      now()->subMonths(2)->startOfMonth()->addDays(10)],
            [$cash, $food,          'expense',  2400, 'Restaurant dinner',        now()->subMonths(2)->startOfMonth()->addDays(14)],
            [$hbl,  $health,        'expense',  4500, 'Doctor visit + medicine',  now()->subMonths(2)->startOfMonth()->addDays(18)],
            [$cash, $shopping,      'expense',  5600, 'Clothing',                 now()->subMonths(2)->startOfMonth()->addDays(22)],
            [$hbl,  $entertainment, 'expense',  1200, 'Netflix + Spotify',        now()->subMonths(2)->startOfMonth()->addDays(25)],

            // Month -1
            [$hbl,  $salary,        'income',  85000, 'Monthly salary',           now()->subMonth()->startOfMonth()->addDays(1)],
            [$hbl,  $rent,          'expense', 22000, 'House rent',               now()->subMonth()->startOfMonth()->addDays(2)],
            [$hbl,  $utilities,     'expense',  2900, 'Gas + electricity',        now()->subMonth()->startOfMonth()->addDays(3)],
            [$cash, $food,          'expense',  2100, 'Groceries',                now()->subMonth()->startOfMonth()->addDays(6)],
            [$cash, $transport,     'expense',   800, 'Uber rides',               now()->subMonth()->startOfMonth()->addDays(9)],
            [$jazz, $freelance,     'income',  25000, 'App development milestone', now()->subMonth()->startOfMonth()->addDays(12)],
            [$cash, $food,          'expense',  3100, 'Family dinner out',        now()->subMonth()->startOfMonth()->addDays(16)],
            [$hbl,  $shopping,      'expense',  8900, 'Electronics',              now()->subMonth()->startOfMonth()->addDays(20)],
            [$cash, $health,        'expense',  1500, 'Gym membership',           now()->subMonth()->startOfMonth()->addDays(23)],
            [$hbl,  $entertainment, 'expense',  2200, 'Movie tickets + outing',   now()->subMonth()->startOfMonth()->addDays(27)],

            // Current month
            [$hbl,  $salary,    'income',  85000, 'Monthly salary',        now()->startOfMonth()->addDays(1)],
            [$hbl,  $rent,      'expense', 22000, 'House rent',             now()->startOfMonth()->addDays(2)],
            [$hbl,  $utilities, 'expense',  3500, 'Electricity bill',       now()->startOfMonth()->addDays(3)],
            [$cash, $food,      'expense',  1600, 'Groceries',              now()->startOfMonth()->addDays(5)],
            [$jazz, $freelance, 'income',  12000, 'Logo design payment',    now()->startOfMonth()->addDays(8)],
            [$cash, $transport, 'expense',   950, 'Fuel + parking',         now()->startOfMonth()->addDays(10)],
            [$cash, $food,      'expense',  2800, 'Restaurant',             now()->startOfMonth()->addDays(13)],
        ];

        foreach ($transactions as [$account, $category, $type, $amount, $note, $date]) {
            $user->transactions()->create([
                'account_id' => $account->id,
                'category_id' => $category?->id,
                'type' => $type,
                'amount' => $amount,
                'note' => $note,
                'transacted_at' => $date,
            ]);
        }

        // ── Loans ─────────────────────────────────────────────────────────────
        $loanAhmed = $user->loans()->create([
            'contact_name' => 'Ahmed Raza',
            'direction' => 'lent',
            'amount' => 15000,
            'remaining' => 15000,
            'loaned_at' => now()->subMonths(2)->startOfMonth(),
            'due_date' => now()->addMonth(),
            'status' => 'active',
            'note' => 'Personal loan',
        ]);

        $user->loans()->create([
            'contact_name' => 'Sara Khan',
            'direction' => 'borrowed',
            'amount' => 30000,
            'remaining' => 30000,
            'loaned_at' => now()->subMonths(3)->startOfMonth(),
            'due_date' => now()->subDays(10),
            'status' => 'active',
            'note' => 'Borrowed for car repair',
        ]);

        $user->loans()->create([
            'contact_name' => 'Usman Ali',
            'direction' => 'lent',
            'amount' => 5000,
            'remaining' => 5000,
            'loaned_at' => now()->subMonth()->startOfMonth(),
            'due_date' => now()->addMonths(2),
            'status' => 'active',
            'note' => 'Lunch money',
        ]);

        $user->loans()->create([
            'contact_name' => 'Bilal Sheikh',
            'direction' => 'borrowed',
            'amount' => 10000,
            'remaining' => 0,
            'loaned_at' => now()->subMonths(4)->startOfMonth(),
            'due_date' => now()->subMonths(2),
            'status' => 'settled',
            'note' => 'Already repaid',
        ]);

        // Partial payment on Ahmed's loan (lent → receiving back → cash increases)
        $loanAhmed->payments()->create([
            'account_id' => $cash->id,
            'amount' => 5000,
            'note' => 'Partial repayment',
            'paid_at' => now()->subMonth()->addDays(15),
        ]);

        // ── Budgets ───────────────────────────────────────────────────────────
        $user->budgets()->create([
            'category_id' => $food->id,
            'name' => 'Food & Dining',
            'amount' => 8000,
            'period' => 'monthly',
            'start_date' => now()->startOfMonth(),
            'end_date' => null,
        ]);

        $user->budgets()->create([
            'category_id' => $transport->id,
            'name' => 'Transport',
            'amount' => 3000,
            'period' => 'monthly',
            'start_date' => now()->startOfMonth(),
            'end_date' => null,
        ]);

        $user->budgets()->create([
            'category_id' => $entertainment->id,
            'name' => 'Entertainment',
            'amount' => 2500,
            'period' => 'monthly',
            'start_date' => now()->startOfMonth(),
            'end_date' => null,
        ]);

        $user->budgets()->create([
            'category_id' => $utilities->id,
            'name' => 'Utilities',
            'amount' => 5000,
            'period' => 'monthly',
            'start_date' => now()->startOfMonth(),
            'end_date' => null,
        ]);

        $this->command->info('Demo user seeded: demo@example.com / password');
    }
}
