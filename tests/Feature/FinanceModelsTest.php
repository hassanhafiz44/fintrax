<?php

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Transaction;
use App\Models\User;

test('user has finance relations', function () {
    $user = User::factory()->create();

    // UserObserver seeds 1 default account + 13 default categories on creation.
    Account::factory()->for($user)->create();
    Category::factory()->for($user)->create();
    Loan::factory()->for($user)->create();
    Budget::factory()->for($user)->create();

    expect($user->accounts)->toHaveCount(2);
    expect($user->categories)->toHaveCount(14);
    expect($user->loans)->toHaveCount(1);
    expect($user->budgets)->toHaveCount(1);
});

test('transaction belongs to account and category', function () {
    $account = Account::factory()->create();
    $category = Category::factory()->create();

    $transaction = Transaction::factory()
        ->for($account)
        ->create(['category_id' => $category->id, 'user_id' => $account->user_id]);

    expect($transaction->account->is($account))->toBeTrue();
    expect($transaction->category->is($category))->toBeTrue();
});

test('loan payment belongs to loan', function () {
    $loan = Loan::factory()->create();
    $payment = LoanPayment::factory()->for($loan)->create();

    expect($payment->loan->is($loan))->toBeTrue();
    expect($loan->payments)->toHaveCount(1);
});

test('loan is overdue when active and past due date', function () {
    $overdue = Loan::factory()->create(['status' => 'active', 'due_date' => now()->subDay()]);
    $upcoming = Loan::factory()->create(['status' => 'active', 'due_date' => now()->addDay()]);
    $settled = Loan::factory()->create(['status' => 'settled', 'due_date' => now()->subDay()]);

    expect($overdue->isOverdue())->toBeTrue();
    expect($upcoming->isOverdue())->toBeFalse();
    expect($settled->isOverdue())->toBeFalse();
});

test('budget computes spent and progress percent from expense transactions', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);
    $account = Account::factory()->for($user)->create();

    $budget = Budget::factory()->for($user)->create([
        'category_id' => $category->id,
        'amount' => 1000,
        'start_date' => now()->startOfMonth(),
        'end_date' => null,
    ]);

    Transaction::factory()->for($account)->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => 250,
        'transacted_at' => now(),
    ]);

    Transaction::factory()->for($account)->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'type' => 'income',
        'amount' => 999,
        'transacted_at' => now(),
    ]);

    expect((float) $budget->spent)->toBe(250.0);
    expect($budget->progress_percent)->toBe(25);
});

test('user observer seeds default account and categories on registration', function () {
    $user = User::factory()->create();

    expect($user->accounts)->toHaveCount(1);
    expect($user->accounts->first())
        ->name->toBe('Cash')
        ->is_default->toBeTrue();

    expect($user->categories()->where('type', 'expense')->count())->toBe(8);
    expect($user->categories()->where('type', 'income')->count())->toBe(5);
});

test('transaction observer syncs account balance on create and delete', function () {
    $account = Account::factory()->create(['balance' => 1000]);

    $income = Transaction::factory()->for($account)->create([
        'user_id' => $account->user_id,
        'type' => 'income',
        'amount' => 200,
    ]);
    expect((float) $account->refresh()->balance)->toBe(1200.0);

    $expense = Transaction::factory()->for($account)->create([
        'user_id' => $account->user_id,
        'type' => 'expense',
        'amount' => 300,
    ]);
    expect((float) $account->refresh()->balance)->toBe(900.0);

    $income->delete();
    expect((float) $account->refresh()->balance)->toBe(700.0);

    $expense->delete();
    expect((float) $account->refresh()->balance)->toBe(1000.0);
});

test('loan payment observer decrements remaining and auto-settles loan', function () {
    $loan = Loan::factory()->create(['amount' => 500, 'remaining' => 500, 'status' => 'active']);

    LoanPayment::factory()->for($loan)->create(['amount' => 300]);
    expect((float) $loan->refresh()->remaining)->toBe(200.0);
    expect($loan->status)->toBe('active');

    LoanPayment::factory()->for($loan)->create(['amount' => 200]);
    expect((float) $loan->refresh()->remaining)->toBe(0.0);
    expect($loan->status)->toBe('settled');
});
