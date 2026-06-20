<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Loan>
 */
class LoanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 500, 20000);

        return [
            'user_id' => User::factory(),
            'contact_name' => fake()->name(),
            'direction' => fake()->randomElement(['lent', 'borrowed']),
            'amount' => $amount,
            'remaining' => $amount,
            'due_date' => fake()->optional()->dateTimeBetween('now', '+3 months'),
            'status' => 'active',
            'note' => fake()->optional()->sentence(),
        ];
    }
}
