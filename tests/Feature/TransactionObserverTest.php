<?php

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;

test('transfer transaction moves balance from source to destination account', function () {
    $user = User::factory()->create();
    $source = Account::factory()->for($user)->create(['balance' => 100]);
    $destination = Account::factory()->for($user)->create(['balance' => 20]);

    Transaction::factory()->for($source)->create([
        'user_id' => $user->id,
        'type' => 'transfer',
        'amount' => 30,
        'to_account_id' => $destination->id,
    ]);

    expect((float) $source->refresh()->balance)->toBe(70.0);
    expect((float) $destination->refresh()->balance)->toBe(50.0);
});

test('deleting a transfer transaction reverses both account balances', function () {
    $user = User::factory()->create();
    $source = Account::factory()->for($user)->create(['balance' => 100]);
    $destination = Account::factory()->for($user)->create(['balance' => 20]);

    $transfer = Transaction::factory()->for($source)->create([
        'user_id' => $user->id,
        'type' => 'transfer',
        'amount' => 30,
        'to_account_id' => $destination->id,
    ]);

    $transfer->delete();

    expect((float) $source->refresh()->balance)->toBe(100.0);
    expect((float) $destination->refresh()->balance)->toBe(20.0);
});
