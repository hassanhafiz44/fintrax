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

test('can create a transfer transaction and moves balance between accounts', function () {
    $user = User::factory()->create();
    $source = Account::factory()->for($user)->create(['balance' => 100]);
    $destination = Account::factory()->for($user)->create(['balance' => 20]);

    Livewire::actingAs($user)
        ->test('pages::transactions.form')
        ->call('open')
        ->set('type', 'transfer')
        ->set('amount', '30')
        ->set('account_id', (string) $source->id)
        ->set('to_account_id', (string) $destination->id)
        ->set('transacted_at', now()->format('Y-m-d'))
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('transaction-saved');

    expect((float) $source->refresh()->balance)->toBe(70.0);
    expect((float) $destination->refresh()->balance)->toBe(50.0);
});

test('transfer requires a destination account different from the source account', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 100]);

    Livewire::actingAs($user)
        ->test('pages::transactions.form')
        ->call('open')
        ->set('type', 'transfer')
        ->set('amount', '30')
        ->set('account_id', (string) $account->id)
        ->set('to_account_id', (string) $account->id)
        ->set('transacted_at', now()->format('Y-m-d'))
        ->call('save')
        ->assertHasErrors(['to_account_id']);
});

test('a failure while saving rolls back the balance (atomicity)', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 0]);

    $transaction = Transaction::factory()->for($account)->create([
        'user_id' => $user->id,
        'type' => 'expense',
        'amount' => 30,
    ]);

    expect((float) $account->refresh()->balance)->toBe(-30.0);

    // Inject a failure on the recreate step: save() deletes (balance → 0), then create() throws.
    // Without the DB::transaction wrapper the delete would persist and the balance would stick at 0 (drift).
    // The closure binds to this test's event dispatcher, which the next test replaces — so it cannot leak.
    Transaction::creating(function (): void {
        throw new RuntimeException('simulated failure mid-save');
    });

    expect(function () use ($user, $transaction) {
        Livewire::actingAs($user)
            ->test('pages::transactions.form')
            ->call('open', $transaction->id)
            ->set('amount', '10')
            ->call('save');
    })->toThrow(RuntimeException::class);

    // All-or-nothing: the delete was rolled back, the original row is intact, balance unchanged.
    expect(Transaction::count())->toBe(1);
    expect((float) $transaction->refresh()->amount)->toBe(30.0);
    expect((float) $account->refresh()->balance)->toBe(-30.0);
});

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
