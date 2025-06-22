<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->optional()->sentence(),
            'type' => $this->faker->randomElement(['income', 'expense']),
            'parent_id' => null, // Default to no parent
            'color' => $this->faker->hexColor(),
            'icon' => $this->faker->optional()->randomElement(['fa-home', 'fa-car', 'fa-food', 'fa-shopping', 'fa-medical']),
            'budget_limit' => $this->faker->optional()->randomFloat(2, 100, 5000),
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
        ];
    }

    /**
     * Indicate that the category is for income.
     */
    public function income(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'income',
            'budget_limit' => null, // Income categories typically don't have budget limits
        ]);
    }

    /**
     * Indicate that the category is for expenses.
     */
    public function expense(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'expense',
        ]);
    }

    /**
     * Indicate that the category is active.
     */
    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the category is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set a specific budget limit.
     */
    public function withBudget(float $budget): static
    {
        return $this->state(fn(array $attributes) => [
            'budget_limit' => $budget,
        ]);
    }
}
