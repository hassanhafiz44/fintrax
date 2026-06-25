<?php

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

test('deleting an account cascades to its transactions', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['name' => 'Meezan Bank']);
    $transaction = Transaction::factory()->for($user)->for($account)->create();

    Livewire::actingAs($user)
        ->test('pages::accounts.index')
        ->call('confirmDelete', $account->id)
        ->assertSet('confirmingDelete', true)
        ->call('delete')
        ->assertSet('confirmingDelete', false);

    expect(Account::find($account->id))->toBeNull();
    expect(Transaction::find($transaction->id))->toBeNull();
});

test('list paginates beyond the first page', function () {
    $user = User::factory()->create();
    Account::factory()->for($user)->count(15)->create();

    $component = Livewire::actingAs($user)->test('pages::accounts.index');

    expect($component->get('accounts')->count())->toBe(15);
    expect($component->get('accounts')->total())->toBe(16);
});

test('cannot delete another users account', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $account = Account::factory()->for($owner)->create();

    expect(fn () => Livewire::actingAs($intruder)
        ->test('pages::accounts.index')
        ->call('confirmDelete', $account->id)
        ->call('delete'))->toThrow(ModelNotFoundException::class);

    expect(Account::find($account->id))->not->toBeNull();
});
