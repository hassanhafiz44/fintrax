<?php

namespace Database\Factories;

use App\Models\Budget;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Budget>
 */
class BudgetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => null,
            'name' => fake()->words(2, true),
            'amount' => fake()->randomFloat(2, 1000, 50000),
            'period' => 'monthly',
            'start_date' => now()->startOfMonth(),
            'end_date' => null,
        ];
    }
}
