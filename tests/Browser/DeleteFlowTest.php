<?php

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Loan;
use App\Models\Transaction;
use App\Models\User;

test('deleting a transaction via the confirm modal removes it', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $transaction = Transaction::factory()->for($user)->for($account)->create(['note' => 'Grocery run']);

    $this->actingAs($user);
    $page = visit('/transactions');

    $page->assertSee('Grocery run')
        ->click("[wire\\:click=\"confirmDelete({$transaction->id})\"]")
        ->assertSee('Delete transaction?')
        ->click('button:has-text("Delete")')
        ->assertDontSee('Grocery run');

    expect(Transaction::find($transaction->id))->toBeNull();
});

test('deleting a loan via the confirm modal removes it', function () {
    $user = User::factory()->create();
    $loan = Loan::factory()->for($user)->create(['contact_name' => 'Ali Raza']);

    $this->actingAs($user);
    $page = visit('/loans');

    $page->assertSee('Ali Raza')
        ->click("[wire\\:click=\"confirmDelete({$loan->id})\"]")
        ->assertSee('Delete loan?')
        ->click('button:has-text("Delete")')
        ->assertDontSee('Ali Raza');

    expect(Loan::find($loan->id))->toBeNull();
});

test('deleting a budget via the confirm modal removes it', function () {
    $user = User::factory()->create();
    $budget = Budget::factory()->for($user)->create(['name' => 'Groceries Budget']);

    $this->actingAs($user);
    $page = visit('/budgets');

    $page->assertSee('Groceries Budget')
        ->click("[wire\\:click=\"confirmDelete({$budget->id})\"]")
        ->assertSee('Delete budget?')
        ->click('button:has-text("Delete")')
        ->assertDontSee('Groceries Budget');

    expect(Budget::find($budget->id))->toBeNull();
});

test('deleting an account via the confirm modal removes it', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['name' => 'Meezan Bank']);

    $this->actingAs($user);
    $page = visit('/accounts');

    $page->assertSee('Meezan Bank')
        ->click("[wire\\:click=\"confirmDelete({$account->id})\"]")
        ->assertSee('Delete account?')
        ->click('button:has-text("Delete")')
        ->assertDontSee('Meezan Bank');

    expect(Account::find($account->id))->toBeNull();
});

test('deleting a category via the confirm modal removes it', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['name' => 'Side Hustle']);

    $this->actingAs($user);
    $page = visit('/settings/categories');

    $page->assertSee('Side Hustle')
        ->click("[wire\\:click=\"confirmDelete({$category->id})\"]")
        ->assertSee('Delete category?')
        ->click('button:has-text("Delete")')
        ->assertDontSee('Side Hustle');

    expect(Category::find($category->id))->toBeNull();
});
