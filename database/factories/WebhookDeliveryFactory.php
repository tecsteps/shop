<?php

namespace Database\Factories;

use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebhookDelivery>
 */
class WebhookDeliveryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subscription_id' => WebhookSubscription::factory(),
            'event_type' => 'order.created',
            'payload_json' => ['order_id' => 1, 'total' => 5000],
            'response_status' => null,
            'response_body' => null,
            'delivered_at' => null,
            'attempts' => 0,
            'next_retry_at' => null,
        ];
    }

    /**
     * Indicate a successful delivery.
     */
    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'response_status' => 200,
            'response_body' => 'OK',
            'delivered_at' => now(),
            'attempts' => 1,
        ]);
    }

    /**
     * Indicate a failed delivery.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'response_status' => 500,
            'response_body' => 'Internal Server Error',
            'delivered_at' => null,
            'attempts' => 1,
        ]);
    }
}
