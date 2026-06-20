<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

test('can create an income transaction and account balance updates', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 100]);
    $category = Category::factory()->for($user)->create(['type' => 'income']);

    Livewire::actingAs($user)
        ->test('pages::transactions.form')
        ->call('open')
        ->set('type', 'income')
        ->set('amount', '50')
        ->set('account_id', (string) $account->id)
        ->set('category_id', (string) $category->id)
        ->set('transacted_at', now()->format('Y-m-d'))
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('transaction-saved');

    expect((float) $account->refresh()->balance)->toBe(150.0);
    expect(Transaction::count())->toBe(1);
});

test('editing a transaction reverses old balance effect and applies new one', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 0]);

    $transaction = Transaction::factory()->for($account)->create([
        'user_id' => $user->id,
        'type' => 'expense',
        'amount' => 30,
    ]);

    expect((float) $account->refresh()->balance)->toBe(-30.0);

    Livewire::actingAs($user)
        ->test('pages::transactions.form')
        ->call('open', $transaction->id)
        ->set('amount', '10')
        ->call('save')
        ->assertHasNoErrors();

    expect((float) $account->refresh()->balance)->toBe(-10.0);
    expect(Transaction::count())->toBe(1);
});

test('cannot open another users transaction for editing', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $account = Account::factory()->for($owner)->create();
    $transaction = Transaction::factory()->for($account)->create(['user_id' => $owner->id]);

    // Scoped lookup via auth()->user()->transactions() means a cross-user id
    // 404s before authorization even runs — the correct, more secure outcome.
    Livewire::actingAs($intruder)
        ->test('pages::transactions.form')
        ->call('open', $transaction->id);
})->throws(ModelNotFoundException::class);

test('amount is required and must be numeric', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test('pages::transactions.form')
        ->call('open')
        ->set('account_id', (string) $account->id)
        ->set('amount', 'not-a-number')
        ->set('transacted_at', now()->format('Y-m-d'))
        ->call('save')
        ->assertHasErrors(['amount']);
});
