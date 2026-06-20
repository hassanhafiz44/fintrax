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
