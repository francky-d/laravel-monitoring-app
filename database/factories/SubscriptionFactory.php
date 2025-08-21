<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Application;
use App\Models\ApplicationGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subscribableType = $this->faker->randomElement(['application', 'group']);
        
        return [
            'user_id' => User::factory(),
            'notification_channels' => $this->faker->randomElements(
                ['email', 'slack', 'discord', 'teams'],
                $this->faker->numberBetween(1, 3)
            ),
            'webhook_url' => $this->faker->optional(0.7)->url(),
            'subscribable_type' => $subscribableType === 'application' ? Application::class : ApplicationGroup::class,
            'subscribable_id' => $subscribableType === 'application' ? 
                Application::factory() : 
                ApplicationGroup::factory(),
        ];
    }

    /**
     * Indicate that the subscription is for an application.
     */
    public function forApplication(?Application $application = null): static
    {
        return $this->state(fn (array $attributes) => [
            'subscribable_type' => Application::class,
            'subscribable_id' => $application?->id ?? Application::factory(),
        ]);
    }

    /**
     * Indicate that the subscription is for an application group.
     */
    public function forApplicationGroup(?ApplicationGroup $group = null): static
    {
        return $this->state(fn (array $attributes) => [
            'subscribable_type' => ApplicationGroup::class,
            'subscribable_id' => $group?->id ?? ApplicationGroup::factory(),
        ]);
    }

    /**
     * Indicate that the subscription uses specific notification channels.
     */
    public function withChannels(array $channels): static
    {
        return $this->state(fn (array $attributes) => [
            'notification_channels' => $channels,
        ]);
    }

    /**
     * Indicate that the subscription has a webhook URL.
     */
    public function withWebhook(string $url = null): static
    {
        return $this->state(fn (array $attributes) => [
            'webhook_url' => $url ?? $this->faker->url(),
        ]);
    }

    /**
     * Indicate that the subscription uses email notifications only.
     */
    public function emailOnly(): static
    {
        return $this->withChannels(['email']);
    }

    /**
     * Indicate that the subscription uses Slack notifications.
     */
    public function slack(): static
    {
        return $this->state(fn (array $attributes) => [
            'notification_channels' => ['slack'],
            'webhook_url' => 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX',
        ]);
    }
}
