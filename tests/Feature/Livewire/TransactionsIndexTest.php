<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

test('search filters by note', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    Transaction::factory()->for($user)->for($account)->create(['note' => 'Grocery shopping']);
    Transaction::factory()->for($user)->for($account)->create(['note' => 'Electric bill']);

    Livewire::actingAs($user)
        ->test('pages::transactions.index')
        ->set('search', 'Grocery')
        ->assertSee('Grocery shopping')
        ->assertDontSee('Electric bill');
});

test('type filter narrows results', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    Transaction::factory()->for($user)->for($account)->create(['note' => 'Salary', 'type' => 'income']);
    Transaction::factory()->for($user)->for($account)->create(['note' => 'Electric bill', 'type' => 'expense']);

    Livewire::actingAs($user)
        ->test('pages::transactions.index')
        ->set('type', 'income')
        ->assertSee('Salary')
        ->assertDontSee('Electric bill');
});

test('category filter narrows results', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $groceries = Category::factory()->for($user)->create(['name' => 'Groceries']);
    $rent = Category::factory()->for($user)->create(['name' => 'Rent']);
    Transaction::factory()->for($user)->for($account)->for($groceries, 'category')->create(['note' => 'Weekly shop']);
    Transaction::factory()->for($user)->for($account)->for($rent, 'category')->create(['note' => 'Monthly rent']);

    Livewire::actingAs($user)
        ->test('pages::transactions.index')
        ->set('category_id', (string) $groceries->id)
        ->assertSee('Weekly shop')
        ->assertDontSee('Monthly rent');
});

test('account filter narrows results', function () {
    $user = User::factory()->create();
    $cash = Account::factory()->for($user)->create();
    $bank = Account::factory()->for($user)->create();
    Transaction::factory()->for($user)->for($cash)->create(['note' => 'Cash purchase']);
    Transaction::factory()->for($user)->for($bank)->create(['note' => 'Bank transfer']);

    Livewire::actingAs($user)
        ->test('pages::transactions.index')
        ->set('account_id', (string) $cash->id)
        ->assertSee('Cash purchase')
        ->assertDontSee('Bank transfer');
});

test('date range filter narrows results', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    Transaction::factory()->for($user)->for($account)->create(['note' => 'In range', 'transacted_at' => '2026-06-15']);
    Transaction::factory()->for($user)->for($account)->create(['note' => 'Out of range', 'transacted_at' => '2026-01-15']);

    Livewire::actingAs($user)
        ->test('pages::transactions.index')
        ->set('date_from', '2026-06-01')
        ->set('date_to', '2026-06-30')
        ->assertSee('In range')
        ->assertDontSee('Out of range');
});

test('deleting an expense transaction reverses the account balance', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 1000]);
    $transaction = Transaction::factory()->for($user)->for($account)->create([
        'type' => 'expense',
        'amount' => 200,
    ]);

    Livewire::actingAs($user)
        ->test('pages::transactions.index')
        ->call('confirmDelete', $transaction->id)
        ->assertSet('confirmingDelete', true)
        ->call('delete')
        ->assertSet('confirmingDelete', false);

    expect(Transaction::find($transaction->id))->toBeNull();
    expect($account->refresh()->balance)->toEqual('1000.00');
});

test('cannot delete another users transaction', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $account = Account::factory()->for($owner)->create();
    $transaction = Transaction::factory()->for($owner)->for($account)->create();

    expect(fn () => Livewire::actingAs($intruder)
        ->test('pages::transactions.index')
        ->call('confirmDelete', $transaction->id)
        ->call('delete'))->toThrow(ModelNotFoundException::class);

    expect(Transaction::find($transaction->id))->not->toBeNull();
});
