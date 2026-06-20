<?php

use App\Models\User;
use Livewire\Livewire;

test('can create a custom category', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::settings.categories')
        ->call('openForm')
        ->set('name', 'Pets')
        ->set('type', 'expense')
        ->call('save')
        ->assertHasNoErrors();

    expect($user->categories()->where('name', 'Pets')->exists())->toBeTrue();
});

test('cannot delete a system category', function () {
    $user = User::factory()->create();
    $systemCategory = $user->categories()->where('is_system', true)->first();

    Livewire::actingAs($user)
        ->test('pages::settings.categories')
        ->call('confirmDelete', $systemCategory->id)
        ->call('delete');

    expect($systemCategory->fresh())->not->toBeNull();
});
