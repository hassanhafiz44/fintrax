<?php

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

test('list renders with spent and progress', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);
    $budget = Budget::factory()->for($user)->for($category)->create([
        'name' => 'Groceries Budget',
        'amount' => 1000,
        'start_date' => now()->startOfMonth(),
    ]);
    Transaction::factory()->for($user)->for($account)->for($category, 'category')->create([
        'type' => 'expense',
        'amount' => 850,
        'transacted_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test('pages::budgets.index')
        ->assertSee('Groceries Budget')
        ->assertSee('85%');

    expect($budget->refresh()->progress_percent)->toBe(85);
});

test('list paginates beyond the first page', function () {
    $user = User::factory()->create();
    Budget::factory()->for($user)->count(16)->create();

    $component = Livewire::actingAs($user)->test('pages::budgets.index');

    expect($component->get('budgets')->count())->toBe(15);
    expect($component->get('budgets')->total())->toBe(16);
});

test('deleting a budget removes it', function () {
    $user = User::factory()->create();
    $budget = Budget::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test('pages::budgets.index')
        ->call('confirmDelete', $budget->id)
        ->assertSet('confirmingDelete', true)
        ->call('delete')
        ->assertSet('confirmingDelete', false);

    expect(Budget::find($budget->id))->toBeNull();
});

test('cannot delete another users budget', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $budget = Budget::factory()->for($owner)->create();

    expect(fn () => Livewire::actingAs($intruder)
        ->test('pages::budgets.index')
        ->call('confirmDelete', $budget->id)
        ->call('delete'))->toThrow(ModelNotFoundException::class);

    expect(Budget::find($budget->id))->not->toBeNull();
});
