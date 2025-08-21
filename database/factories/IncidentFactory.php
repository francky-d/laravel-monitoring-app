<?php

namespace Database\Factories;

use App\Models\Application;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Incident>
 */
class IncidentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(6),
            'description' => $this->faker->paragraph(3),
            'severity' => $this->faker->randomElement(IncidentSeverity::cases()),
            'status' => $this->faker->randomElement(IncidentStatus::cases()),
            'response_code' => $this->faker->optional()->randomElement([404, 500, 502, 503, 504]),
            'response_time' => $this->faker->optional()->numberBetween(100, 10000),
            'error_message' => $this->faker->optional()->sentence(),
            'application_id' => Application::factory(),
            'resolved_at' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Indicate that the incident is open.
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => IncidentStatus::OPEN,
            'resolved_at' => null,
        ]);
    }

    /**
     * Indicate that the incident is resolved.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => IncidentStatus::RESOLVED,
            'resolved_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the incident has a specific severity.
     */
    public function severity(IncidentSeverity $severity): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => $severity,
        ]);
    }

    /**
     * Indicate that the incident is critical.
     */
    public function critical(): static
    {
        return $this->severity(IncidentSeverity::CRITICAL);
    }

    /**
     * Indicate that the incident is high severity.
     */
    public function high(): static
    {
        return $this->severity(IncidentSeverity::HIGH);
    }

    /**
     * Indicate that the incident is low severity.
     */
    public function low(): static
    {
        return $this->severity(IncidentSeverity::LOW);
    }
}
