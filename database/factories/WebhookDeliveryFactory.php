<?php

namespace Database\Factories;

use App\Enums\WebhookDeliveryStatus;
use App\Models\WebhookSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\WebhookDelivery>
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
            'event_id' => (string) Str::uuid(),
            'attempt_count' => 1,
            'status' => WebhookDeliveryStatus::Pending,
            'last_attempt_at' => now(),
            'response_code' => null,
            'response_body_snippet' => null,
        ];
    }

    /**
     * Indicate that the delivery succeeded.
     */
    public function success(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WebhookDeliveryStatus::Success,
            'response_code' => 200,
            'response_body_snippet' => 'OK',
        ]);
    }

    /**
     * Indicate that the delivery failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WebhookDeliveryStatus::Failed,
            'response_code' => 500,
            'response_body_snippet' => 'Internal Server Error',
        ]);
    }
}
