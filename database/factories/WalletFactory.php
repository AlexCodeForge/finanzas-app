<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Wallet>
 */
class WalletFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true) . ' Wallet',
            'type' => $this->faker->randomElement(['bank_account', 'cash', 'credit_card', 'savings', 'investment', 'other']),
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP', 'CAD']),
            'balance' => $this->faker->randomFloat(2, 0, 10000),
            'initial_balance' => $this->faker->randomFloat(2, 0, 10000),
            'description' => $this->faker->optional()->sentence(),
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
        ];
    }

    /**
     * Indicate that the wallet is active.
     */
    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the wallet is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set a specific balance.
     */
    public function withBalance(float $balance): static
    {
        return $this->state(fn(array $attributes) => [
            'balance' => $balance,
            'initial_balance' => $balance,
        ]);
    }
}
