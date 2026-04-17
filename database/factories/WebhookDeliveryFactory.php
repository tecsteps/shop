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
    protected $model = WebhookDelivery::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subscription_id' => WebhookSubscription::factory(),
            'event_type' => 'order.created',
            'payload_json' => ['order_id' => fake()->randomNumber()],
            'response_status' => null,
            'response_body' => null,
            'attempt_count' => 1,
            'status' => 'pending',
            'delivered_at' => null,
        ];
    }

    public function success(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'success',
            'response_status' => 200,
            'response_body' => 'OK',
            'delivered_at' => now()->toIso8601String(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'response_status' => 500,
            'response_body' => 'Internal Server Error',
        ]);
    }
}
