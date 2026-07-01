<?php

use App\Models\Account;
use App\Models\Loan;
use App\Models\User;
use Livewire\Livewire;

test('can create a loan', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::loans.form')
        ->call('openLoanForm')
        ->set('contact_name', 'Ali')
        ->set('direction', 'lent')
        ->set('amount', '500')
        ->call('saveLoan')
        ->assertHasNoErrors()
        ->assertDispatched('loan-saved');

    $loan = Loan::first();
    expect($loan->contact_name)->toBe('Ali');
    expect((float) $loan->remaining)->toBe(500.0);
    expect($loan->status)->toBe('active');
    expect($loan->loaned_at->format('Y-m-d'))->toBe(now()->format('Y-m-d'));
});

test('loaned_at can be set and edited', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::loans.form')
        ->call('openLoanForm')
        ->set('contact_name', 'Sara')
        ->set('direction', 'borrowed')
        ->set('amount', '300')
        ->set('loaned_at', '2026-01-15')
        ->call('saveLoan')
        ->assertHasNoErrors();

    $loan = Loan::where('contact_name', 'Sara')->first();
    expect($loan->loaned_at->format('Y-m-d'))->toBe('2026-01-15');

    Livewire::actingAs($user)
        ->test('pages::loans.form')
        ->call('openLoanForm', $loan->id)
        ->assertSet('loaned_at', '2026-01-15')
        ->set('loaned_at', '2026-02-01')
        ->call('saveLoan')
        ->assertHasNoErrors();

    expect($loan->refresh()->loaned_at->format('Y-m-d'))->toBe('2026-02-01');
});

test('loaned_at cannot be in the future', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::loans.form')
        ->call('openLoanForm')
        ->set('contact_name', 'Ali')
        ->set('direction', 'lent')
        ->set('amount', '500')
        ->set('loaned_at', now()->addDay()->format('Y-m-d'))
        ->call('saveLoan')
        ->assertHasErrors(['loaned_at']);
});

test('logging a full payment auto-settles the loan', function () {
    $user = User::factory()->create();
    $loan = Loan::factory()->for($user)->create(['amount' => 200, 'remaining' => 200, 'status' => 'active']);

    Livewire::actingAs($user)
        ->test('pages::loans.form')
        ->call('openPaymentForm', $loan->id)
        ->set('payment_amount', '200')
        ->set('payment_paid_at', now()->format('Y-m-d'))
        ->call('savePayment')
        ->assertHasNoErrors()
        ->assertDispatched('loan-payment-saved');

    expect((float) $loan->refresh()->remaining)->toBe(0.0);
    expect($loan->status)->toBe('settled');
});

test('payment amount cannot exceed remaining balance', function () {
    $user = User::factory()->create();
    $loan = Loan::factory()->for($user)->create(['amount' => 100, 'remaining' => 100, 'status' => 'active']);

    Livewire::actingAs($user)
        ->test('pages::loans.form')
        ->call('openPaymentForm', $loan->id)
        ->set('payment_amount', '999')
        ->set('payment_paid_at', now()->format('Y-m-d'))
        ->call('savePayment')
        ->assertHasErrors(['payment_amount']);
});

test('creating a lent loan with an account deducts from that account', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 1000]);

    Livewire::actingAs($user)
        ->test('pages::loans.form')
        ->call('openLoanForm')
        ->set('contact_name', 'Ali')
        ->set('direction', 'lent')
        ->set('amount', '300')
        ->set('account_id', $account->id)
        ->call('saveLoan')
        ->assertHasNoErrors();

    expect((float) $account->refresh()->balance)->toBe(700.0);
});

test('creating a borrowed loan with an account adds to that account', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 1000]);

    Livewire::actingAs($user)
        ->test('pages::loans.form')
        ->call('openLoanForm')
        ->set('contact_name', 'Sara')
        ->set('direction', 'borrowed')
        ->set('amount', '400')
        ->set('account_id', $account->id)
        ->call('saveLoan')
        ->assertHasNoErrors();

    expect((float) $account->refresh()->balance)->toBe(1400.0);
});

test('creating a loan without an account does not change any balance', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 1000]);

    Livewire::actingAs($user)
        ->test('pages::loans.form')
        ->call('openLoanForm')
        ->set('contact_name', 'Ali')
        ->set('direction', 'lent')
        ->set('amount', '300')
        ->call('saveLoan')
        ->assertHasNoErrors();

    expect((float) $account->refresh()->balance)->toBe(1000.0);
});

test('editing a disbursed loan amount adjusts the account balance', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 1000]);
    // Factory create fires the observer, disbursing 300 → balance 700.
    $loan = Loan::factory()->for($user)->create([
        'direction' => 'lent',
        'amount' => 300,
        'remaining' => 300,
        'account_id' => $account->id,
        'loaned_at' => now()->subDay(),
        'status' => 'active',
    ]);
    expect((float) $account->refresh()->balance)->toBe(700.0);

    Livewire::actingAs($user)
        ->test('pages::loans.form')
        ->call('openLoanForm', $loan->id)
        ->assertSet('account_id', $account->id)
        ->set('amount', '500')
        ->call('saveLoan')
        ->assertHasNoErrors();

    // 700 + 300 (reverse old) − 500 (apply new) = 500
    expect((float) $account->refresh()->balance)->toBe(500.0);
});

test('changing the account on edit moves the disbursement', function () {
    $user = User::factory()->create();
    $from = Account::factory()->for($user)->create(['balance' => 1000]);
    $to = Account::factory()->for($user)->create(['balance' => 1000]);
    // Factory create disburses 300 from $from → 700.
    $loan = Loan::factory()->for($user)->create([
        'direction' => 'lent',
        'amount' => 300,
        'remaining' => 300,
        'account_id' => $from->id,
        'loaned_at' => now()->subDay(),
        'status' => 'active',
    ]);
    expect((float) $from->refresh()->balance)->toBe(700.0);

    Livewire::actingAs($user)
        ->test('pages::loans.form')
        ->call('openLoanForm', $loan->id)
        ->set('account_id', $to->id)
        ->call('saveLoan')
        ->assertHasNoErrors();

    expect((float) $from->refresh()->balance)->toBe(1000.0); // restored
    expect((float) $to->refresh()->balance)->toBe(700.0);    // charged
});
