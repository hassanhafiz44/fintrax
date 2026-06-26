<?php

use App\Models\Account;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Transaction;
use App\Models\User;

/**
 * Corrupt a stored balance directly via the query builder. A model save would
 * be skipped by Eloquent's dirty-check (the in-memory model is stale after the
 * observer mutated the row), so we write the wrong value with raw SQL.
 */
function corruptBalance(Account $account, float $balance): void
{
    Account::whereKey($account->id)->update(['balance' => $balance]);
}

test('recalculate repairs a drifted account balance from transactions', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 0]);

    // Observer drives balance to 100 - 30 = 70.
    Transaction::factory()->for($account)->create(['user_id' => $user->id, 'type' => 'income', 'amount' => 100]);
    Transaction::factory()->for($account)->create(['user_id' => $user->id, 'type' => 'expense', 'amount' => 30]);
    expect((float) $account->refresh()->balance)->toBe(70.0);

    corruptBalance($account, 5);

    $this->artisan('accounts:recalculate')->assertSuccessful();

    expect((float) $account->refresh()->balance)->toBe(70.0);
});

test('recalculate includes transfers and loan payments', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 0]);
    $other = Account::factory()->for($user)->create(['balance' => 0]);

    // +200 income, -50 transfer out → 150.
    Transaction::factory()->for($account)->create(['user_id' => $user->id, 'type' => 'income', 'amount' => 200]);
    Transaction::factory()->for($account)->create([
        'user_id' => $user->id, 'type' => 'transfer', 'amount' => 50, 'to_account_id' => $other->id,
    ]);

    // Lent loan, repayment into this account → +80 → 230.
    $loan = Loan::factory()->for($user)->create(['direction' => 'lent', 'amount' => 80, 'remaining' => 80]);
    LoanPayment::factory()->for($loan)->create(['amount' => 80, 'account_id' => $account->id]);

    expect((float) $account->refresh()->balance)->toBe(230.0);

    corruptBalance($account, 0);
    $this->artisan('accounts:recalculate')->assertSuccessful();

    expect((float) $account->refresh()->balance)->toBe(230.0);
});

test('dry-run reports drift without writing', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 0]);
    Transaction::factory()->for($account)->create(['user_id' => $user->id, 'type' => 'income', 'amount' => 100]);

    corruptBalance($account, 5);

    $this->artisan('accounts:recalculate --dry-run')->assertSuccessful();

    expect((float) $account->refresh()->balance)->toBe(5.0);
});

test('recalculate can scope to a single user', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $accountA = Account::factory()->for($userA)->create(['balance' => 0]);
    $accountB = Account::factory()->for($userB)->create(['balance' => 0]);
    Transaction::factory()->for($accountA)->create(['user_id' => $userA->id, 'type' => 'income', 'amount' => 100]);
    Transaction::factory()->for($accountB)->create(['user_id' => $userB->id, 'type' => 'income', 'amount' => 100]);

    corruptBalance($accountA, 0);
    corruptBalance($accountB, 0);

    $this->artisan('accounts:recalculate', ['--user' => $userA->id])->assertSuccessful();

    expect((float) $accountA->refresh()->balance)->toBe(100.0); // repaired
    expect((float) $accountB->refresh()->balance)->toBe(0.0);   // untouched (other user)
});
