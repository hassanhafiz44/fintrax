<?php

use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use Livewire\Livewire;

test('can create a budget', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);

    Livewire::actingAs($user)
        ->test('pages::budgets.form')
        ->call('open')
        ->set('name', 'Groceries')
        ->set('category_id', (string) $category->id)
        ->set('amount', '1000')
        ->set('start_date', now()->startOfMonth()->format('Y-m-d'))
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('budget-saved');

    expect(Budget::where('name', 'Groceries')->exists())->toBeTrue();
});

test('end date must be after or equal to start date', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::budgets.form')
        ->call('open')
        ->set('name', 'Bad budget')
        ->set('amount', '500')
        ->set('start_date', '2026-06-10')
        ->set('end_date', '2026-06-01')
        ->call('save')
        ->assertHasErrors(['end_date']);
});

test('custom period requires an end date', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::budgets.form')
        ->call('open')
        ->set('name', 'Custom budget')
        ->set('amount', '500')
        ->set('period', 'custom')
        ->set('start_date', '2026-06-01')
        ->set('end_date', '')
        ->call('save')
        ->assertHasErrors(['end_date']);
});

test('custom period saves with a valid end date', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::budgets.form')
        ->call('open')
        ->set('name', 'Custom budget')
        ->set('amount', '500')
        ->set('period', 'custom')
        ->set('start_date', '2026-06-01')
        ->set('end_date', '2026-06-15')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('budget-saved');

    expect(Budget::query()
        ->where('name', 'Custom budget')
        ->where('period', 'custom')
        ->whereDate('start_date', '2026-06-01')
        ->whereDate('end_date', '2026-06-15')
        ->exists())->toBeTrue();
});
