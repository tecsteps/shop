<?php

namespace Database\Factories;

use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WebhookDelivery> */
class WebhookDeliveryFactory extends Factory
{
    protected $model = WebhookDelivery::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'webhook_subscription_id' => WebhookSubscription::factory(),
            'event_type' => 'orders/create',
            'payload_json' => json_encode(['id' => 1, 'total' => 1000]),
            'attempts' => 0,
            'created_at' => now(),
        ];
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'response_status' => 200,
            'attempts' => 1,
            'delivered_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'response_status' => 500,
            'response_body' => 'Internal Server Error',
            'attempts' => 1,
            'failed_at' => now(),
        ]);
    }
}
