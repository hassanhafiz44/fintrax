<?php

use App\Models\Loan;
use App\Models\LoanDateExtension;
use App\Models\LoanPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

test('status filter narrows results', function () {
    $user = User::factory()->create();
    Loan::factory()->for($user)->create(['contact_name' => 'Active Ali', 'status' => 'active']);
    Loan::factory()->for($user)->create(['contact_name' => 'Settled Sara', 'status' => 'settled']);

    Livewire::actingAs($user)
        ->test('pages::loans.index')
        ->assertSee('Active Ali')
        ->assertDontSee('Settled Sara')
        ->set('statusFilter', 'settled')
        ->assertDontSee('Active Ali')
        ->assertSee('Settled Sara')
        ->set('statusFilter', '')
        ->assertSee('Active Ali')
        ->assertSee('Settled Sara');
});

test('list paginates beyond the first page', function () {
    $user = User::factory()->create();
    Loan::factory()->for($user)->count(16)->create(['status' => 'active']);

    $component = Livewire::actingAs($user)->test('pages::loans.index');

    expect($component->get('loans')->count())->toBe(15);
    expect($component->get('loans')->total())->toBe(16);
});

test('changing the status filter resets to page one', function () {
    $user = User::factory()->create();
    Loan::factory()->for($user)->count(16)->create(['status' => 'active']);
    Loan::factory()->for($user)->create(['status' => 'settled']);

    Livewire::actingAs($user)
        ->test('pages::loans.index')
        ->call('gotoPage', 2)
        ->set('statusFilter', 'settled')
        ->assertSet('paginators.page', 1);
});

test('deleting a loan cascades to its payments and date extensions', function () {
    $user = User::factory()->create();
    $loan = Loan::factory()->for($user)->create();
    $payment = LoanPayment::factory()->for($loan)->create();
    $extension = LoanDateExtension::factory()->for($loan)->create();

    Livewire::actingAs($user)
        ->test('pages::loans.index')
        ->call('confirmDelete', $loan->id)
        ->assertSet('confirmingDelete', true)
        ->call('delete')
        ->assertSet('confirmingDelete', false);

    expect(Loan::find($loan->id))->toBeNull();
    expect(LoanPayment::find($payment->id))->toBeNull();
    expect(LoanDateExtension::find($extension->id))->toBeNull();
});

test('cannot delete another users loan', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $loan = Loan::factory()->for($owner)->create();

    expect(fn () => Livewire::actingAs($intruder)
        ->test('pages::loans.index')
        ->call('confirmDelete', $loan->id)
        ->call('delete'))->toThrow(ModelNotFoundException::class);

    expect(Loan::find($loan->id))->not->toBeNull();
});
