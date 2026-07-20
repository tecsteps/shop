<?php

namespace Database\Factories;

use App\Enums\WebhookSubscriptionStatus;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\WebhookSubscription>
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
            'event_type' => 'order.created',
            'target_url' => 'https://example.com/webhooks/'.Str::random(8),
            'signing_secret_encrypted' => 'whsec_'.Str::random(32),
            'status' => WebhookSubscriptionStatus::Active,
        ];
    }

    /**
     * Indicate that the subscription is paused.
     */
    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WebhookSubscriptionStatus::Paused,
        ]);
    }
}
