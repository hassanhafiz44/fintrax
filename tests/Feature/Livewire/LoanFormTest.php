<?php

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
