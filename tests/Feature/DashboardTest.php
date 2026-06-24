<?php

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Loan;
use App\Models\Transaction;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('computed properties reflect seeded data', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 0]);

    Transaction::factory()->for($user)->for($account)->create([
        'type' => 'income',
        'amount' => 1000,
        'transacted_at' => now(),
        'note' => 'Salary',
    ]);
    Transaction::factory()->for($user)->for($account)->create([
        'type' => 'expense',
        'amount' => 400,
        'transacted_at' => now(),
        'note' => 'Rent',
    ]);
    $account->update(['balance' => 600]);

    $overdueLoan = Loan::factory()->for($user)->create([
        'contact_name' => 'Ali',
        'status' => 'active',
        'due_date' => now()->subDay(),
    ]);

    $category = Category::factory()->for($user)->create(['type' => 'expense']);
    Budget::factory()->for($user)->for($category)->create([
        'name' => 'Tight Budget',
        'amount' => 100,
        'start_date' => now()->startOfMonth(),
    ]);
    Transaction::factory()->for($user)->for($account)->for($category, 'category')->create([
        'type' => 'expense',
        'amount' => 90,
        'transacted_at' => now(),
    ]);

    $component = Livewire::actingAs($user)->test('pages::dashboard');

    expect($component->get('totalBalance'))->toEqual((float) $user->accounts()->sum('balance'));
    expect($component->get('monthIncome'))->toBe(1000.0);
    expect($component->get('monthExpense'))->toBe(490.0);
    expect($component->get('netThisMonth'))->toBe(510.0);
    expect($component->get('activeLoans')->pluck('id'))->toContain($overdueLoan->id);
    expect($component->get('budgetAlerts')->pluck('name'))->toContain('Tight Budget');
    expect($component->get('recentTransactions')->pluck('note'))->toContain('Salary', 'Rent');
});
