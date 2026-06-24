<?php

use App\Models\User;

test('authenticated pages load without javascript errors', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $pages = visit([
        '/dashboard',
        '/transactions',
        '/loans',
        '/budgets',
        '/accounts',
        '/settings/categories',
        '/settings/profile',
        '/settings/security',
    ]);

    $pages->assertNoJavaScriptErrors()->assertNoConsoleLogs();
});
