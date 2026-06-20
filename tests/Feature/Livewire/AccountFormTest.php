<?php

use App\Models\Account;
use App\Models\User;
use Livewire\Livewire;

test('can create an account', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::accounts.form')
        ->call('open')
        ->set('name', 'HBL')
        ->set('type', 'bank')
        ->set('balance', '500')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('account-saved');

    expect(Account::where('name', 'HBL')->exists())->toBeTrue();
});

test('setting an account as default unsets the previous default', function () {
    $user = User::factory()->create();
    $cash = $user->accounts()->where('is_default', true)->first();
    $bank = Account::factory()->for($user)->create(['is_default' => false]);

    Livewire::actingAs($user)
        ->test('pages::accounts.index')
        ->call('setDefault', $bank->id);

    expect($bank->refresh()->is_default)->toBeTrue();
    expect($cash->refresh()->is_default)->toBeFalse();
});
