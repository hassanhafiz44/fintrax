<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
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
            'name' => fake()->randomElement(['Cash', 'Bank Account', 'Savings']),
            'type' => fake()->randomElement(['cash', 'bank', 'mobile_wallet', 'other']),
            'balance' => fake()->randomFloat(2, 0, 100000),
            'currency' => 'PKR',
            'color' => fake()->hexColor(),
            'is_default' => false,
        ];
    }
}
