<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\LoanDateExtension;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoanDateExtension>
 */
class LoanDateExtensionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $previousDueDate = fake()->dateTimeBetween('-2 months', '-1 day');

        return [
            'loan_id' => Loan::factory(),
            'previous_due_date' => $previousDueDate,
            'new_due_date' => fake()->dateTimeBetween('+1 day', '+2 months'),
            'reason' => fake()->optional()->sentence(),
            'extended_at' => now(),
        ];
    }
}
