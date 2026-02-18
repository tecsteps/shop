<?php

namespace Database\Factories;

use App\Enums\WebhookDeliveryStatus;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
            'event_id' => Str::uuid()->toString(),
            'attempt_count' => 1,
            'status' => WebhookDeliveryStatus::Pending,
            'last_attempt_at' => null,
            'response_code' => null,
            'response_body_snippet' => null,
        ];
    }

    public function success(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WebhookDeliveryStatus::Success,
            'response_code' => 200,
            'last_attempt_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WebhookDeliveryStatus::Failed,
            'response_code' => 500,
            'last_attempt_at' => now(),
        ]);
    }
}
