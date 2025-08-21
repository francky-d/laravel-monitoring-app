<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\ApplicationGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Application>
 */
class ApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word() . ' ' . fake()->randomElement(['API', 'Service', 'App']),
            'url' => fake()->url(),
            'url_to_watch' => fake()->optional()->url(),
            'expected_http_code' => fake()->randomElement([200, 201, 204]),
            'monitoring_interval' => fake()->randomElement([5, 10, 15, 30, 60]),
            'user_id' => User::factory(),
            'application_group_id' => ApplicationGroup::factory(),
        ];
    }

    /**
     * Indicate that the application has a specific URL to watch.
     */
    public function withWatchUrl(): static
    {
        return $this->state(fn (array $attributes) => [
            'url_to_watch' => $this->faker->url(),
        ]);
    }

    /**
     * Indicate that the application expects a specific HTTP code.
     */
    public function expectsCode(int $code): static
    {
        return $this->state(fn (array $attributes) => [
            'expected_http_code' => $code,
        ]);
    }

    /**
     * Indicate that the application has a specific monitoring interval.
     */
    public function withInterval(int $minutes): static
    {
        return $this->state(fn (array $attributes) => [
            'monitoring_interval' => $minutes,
        ]);
    }
}
