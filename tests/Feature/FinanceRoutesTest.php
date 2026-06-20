<?php

use App\Models\User;

test('finance routes require authentication', function (string $route) {
    $this->get(route($route))->assertRedirect(route('login'));
})->with([
    'dashboard',
    'transactions.index',
    'loans.index',
    'budgets.index',
    'accounts.index',
    'settings.categories',
]);

test('authenticated users can visit finance pages', function (string $route) {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route($route))->assertOk();
})->with([
    'dashboard',
    'transactions.index',
    'loans.index',
    'budgets.index',
    'accounts.index',
    'settings.categories',
]);
