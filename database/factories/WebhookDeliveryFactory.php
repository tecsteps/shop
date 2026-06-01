<?php

namespace Database\Factories;

use App\Enums\WebhookDeliveryStatus;
use App\Models\WebhookSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WebhookDelivery>
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
            'event_id' => fake()->uuid(),
            'attempt_count' => 1,
            'status' => WebhookDeliveryStatus::Pending->value,
            'last_attempt_at' => null,
            'response_code' => null,
            'response_body_snippet' => null,
        ];
    }

    /**
     * Indicate a failed delivery.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WebhookDeliveryStatus::Failed->value,
            'response_code' => 500,
        ]);
    }
}
