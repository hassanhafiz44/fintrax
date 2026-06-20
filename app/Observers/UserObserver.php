<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $user->accounts()->create(['name' => 'Cash', 'type' => 'cash', 'is_default' => true]);

        $expenseCategories = ['Food', 'Transport', 'Utilities', 'Shopping', 'Health', 'Entertainment', 'Rent', 'Other'];
        $incomeCategories = ['Salary', 'Freelance', 'Business', 'Gift', 'Other'];

        foreach ($expenseCategories as $name) {
            $user->categories()->create(['name' => $name, 'type' => 'expense', 'is_system' => true]);
        }

        foreach ($incomeCategories as $name) {
            $user->categories()->create(['name' => $name, 'type' => 'income', 'is_system' => true]);
        }
    }
}
