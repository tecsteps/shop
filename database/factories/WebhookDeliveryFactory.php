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
            'status' => 'pending',
            'response_status' => null,
            'response_body' => null,
            'attempt_count' => 0,
            'completed_at' => null,
        ];
    }

    public function successful(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'success',
            'response_status' => 200,
            'response_body' => '{"ok": true}',
            'attempt_count' => 1,
            'completed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'failed',
            'response_status' => 500,
            'response_body' => 'Internal Server Error',
            'attempt_count' => 6,
            'completed_at' => now(),
        ]);
    }
}
