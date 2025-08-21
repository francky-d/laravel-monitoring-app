<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApplicationGroup>
 */
class ApplicationGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word() . ' Group',
            'description' => fake()->optional()->sentence(10),
            'user_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the application group has a specific name.
     */
    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }

    /**
     * Indicate that the application group has a description.
     */
    public function withDescription(string $description): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => $description,
        ]);
    }

    /**
     * Create common application group types.
     */
    public function production(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Production',
            'description' => 'Production applications and services',
        ]);
    }

    public function staging(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Staging',
            'description' => 'Staging environment applications',
        ]);
    }

    public function development(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Development',
            'description' => 'Development environment applications',
        ]);
    }
}
