<?php

use App\Models\Loan;
use App\Models\User;
use Livewire\Livewire;

test('extending an active loan creates a history row and updates the due date', function () {
    $user = User::factory()->create();
    $loan = Loan::factory()->for($user)->create(['status' => 'active', 'due_date' => '2026-06-01']);

    Livewire::actingAs($user)
        ->test('pages::loans.form')
        ->call('openExtendForm', $loan->id)
        ->assertSet('current_due_date', '2026-06-01')
        ->set('new_due_date', '2026-07-01')
        ->set('extend_reason', 'Payday delayed')
        ->call('extendDueDate')
        ->assertHasNoErrors()
        ->assertDispatched('loan-saved');

    $loan->refresh();
    expect($loan->due_date->format('Y-m-d'))->toBe('2026-07-01');
    expect($loan->dateExtensions)->toHaveCount(1);

    $extension = $loan->dateExtensions->first();
    expect($extension->previous_due_date->format('Y-m-d'))->toBe('2026-06-01');
    expect($extension->new_due_date->format('Y-m-d'))->toBe('2026-07-01');
    expect($extension->reason)->toBe('Payday delayed');
});

test('extension is rejected when new due date is not after the current due date', function () {
    $user = User::factory()->create();
    $loan = Loan::factory()->for($user)->create(['status' => 'active', 'due_date' => '2026-06-01']);

    Livewire::actingAs($user)
        ->test('pages::loans.form')
        ->call('openExtendForm', $loan->id)
        ->set('new_due_date', '2026-06-01')
        ->call('extendDueDate')
        ->assertHasErrors(['new_due_date']);

    expect($loan->refresh()->due_date->format('Y-m-d'))->toBe('2026-06-01');
    expect($loan->dateExtensions)->toHaveCount(0);
});

test('extending a loan with no prior due date succeeds', function () {
    $user = User::factory()->create();
    $loan = Loan::factory()->for($user)->create(['status' => 'active', 'due_date' => null]);

    Livewire::actingAs($user)
        ->test('pages::loans.form')
        ->call('openExtendForm', $loan->id)
        ->assertSet('current_due_date', '')
        ->set('new_due_date', '2026-08-01')
        ->call('extendDueDate')
        ->assertHasNoErrors();

    $loan->refresh();
    expect($loan->due_date->format('Y-m-d'))->toBe('2026-08-01');
    expect($loan->dateExtensions->first()->previous_due_date)->toBeNull();
});

test('settled loans cannot be extended', function () {
    $user = User::factory()->create();
    $loan = Loan::factory()->for($user)->create(['status' => 'settled', 'due_date' => '2026-06-01']);

    Livewire::actingAs($user)
        ->test('pages::loans.form')
        ->call('openExtendForm', $loan->id)
        ->assertForbidden();
});
