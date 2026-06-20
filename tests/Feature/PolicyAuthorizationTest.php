<?php

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Loan;
use App\Models\Transaction;
use App\Models\User;

test('user can update and delete their own records', function (string $model) {
    $user = User::factory()->create();
    $record = $model::factory()->for($user)->create();

    expect($user->can('view', $record))->toBeTrue();
    expect($user->can('update', $record))->toBeTrue();
    expect($user->can('delete', $record))->toBeTrue();
})->with([
    'account' => Account::class,
    'transaction' => Transaction::class,
    'loan' => Loan::class,
    'budget' => Budget::class,
]);

test('user cannot view update or delete records owned by another user', function (string $model) {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $record = $model::factory()->for($owner)->create();

    expect($intruder->can('view', $record))->toBeFalse();
    expect($intruder->can('update', $record))->toBeFalse();
    expect($intruder->can('delete', $record))->toBeFalse();
})->with([
    'account' => Account::class,
    'transaction' => Transaction::class,
    'loan' => Loan::class,
    'budget' => Budget::class,
]);

test('user can manage their own non-system category but not another users', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $category = Category::factory()->for($owner)->create(['is_system' => false]);

    expect($owner->can('update', $category))->toBeTrue();
    expect($owner->can('delete', $category))->toBeTrue();
    expect($intruder->can('update', $category))->toBeFalse();
    expect($intruder->can('delete', $category))->toBeFalse();
});

test('user cannot update or delete a system category even if they own it', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['is_system' => true]);

    expect($user->can('update', $category))->toBeFalse();
    expect($user->can('delete', $category))->toBeFalse();
});
