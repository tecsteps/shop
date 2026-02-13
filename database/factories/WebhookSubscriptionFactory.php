<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\WebhookSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WebhookSubscription>
 */
class WebhookSubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'app_installation_id' => null,
            'target_url' => fake()->url(),
            'event_types_json' => ['order.created', 'order.paid'],
            'signing_secret_encrypted' => Str::random(32),
            'is_active' => true,
            'consecutive_failures' => 0,
            'paused_at' => null,
        ];
    }

    /**
     * Indicate that the subscription is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the subscription is paused due to failures.
     */
    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'consecutive_failures' => 5,
            'paused_at' => now(),
        ]);
    }
}
