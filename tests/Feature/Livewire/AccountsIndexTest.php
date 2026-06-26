<?php

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

test('an account without transactions can be deleted', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['name' => 'Meezan Bank']);

    Livewire::actingAs($user)
        ->test('pages::accounts.index')
        ->call('confirmDelete', $account->id)
        ->assertSet('confirmingDelete', true)
        ->call('delete')
        ->assertSet('confirmingDelete', false);

    expect(Account::find($account->id))->toBeNull();
});

test('an account with transactions cannot be deleted', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['name' => 'Meezan Bank']);
    $transaction = Transaction::factory()->for($user)->for($account)->create();

    Livewire::actingAs($user)
        ->test('pages::accounts.index')
        ->call('confirmDelete', $account->id)
        ->call('delete')
        ->assertSet('confirmingDelete', false);

    expect(Account::find($account->id))->not->toBeNull();
    expect(Transaction::find($transaction->id))->not->toBeNull();
});

test('an account that is a transfer destination cannot be deleted', function () {
    $user = User::factory()->create();
    $source = Account::factory()->for($user)->create();
    $destination = Account::factory()->for($user)->create();

    Transaction::factory()->for($user)->for($source)->create([
        'type' => 'transfer',
        'to_account_id' => $destination->id,
    ]);

    Livewire::actingAs($user)
        ->test('pages::accounts.index')
        ->call('confirmDelete', $destination->id)
        ->call('delete')
        ->assertSet('confirmingDelete', false);

    expect(Account::find($destination->id))->not->toBeNull();
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
