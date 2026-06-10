<?php

namespace Database\Factories;

use App\Enums\WebhookDeliveryStatus;
use App\Models\WebhookSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
            'event_id' => (string) Str::uuid(),
            'attempt_count' => 0,
            'status' => WebhookDeliveryStatus::Pending,
            'last_attempt_at' => null,
            'response_code' => null,
            'response_body_snippet' => null,
        ];
    }

    public function succeeded(): static
    {
        return $this->state(fn (array $attributes) => [
            'attempt_count' => 1,
            'status' => WebhookDeliveryStatus::Success,
            'last_attempt_at' => now()->subMinutes(5),
            'response_code' => 200,
            'response_body_snippet' => '{"ok":true}',
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'attempt_count' => 6,
            'status' => WebhookDeliveryStatus::Failed,
            'last_attempt_at' => now()->subMinutes(5),
            'response_code' => 500,
            'response_body_snippet' => 'Internal Server Error',
        ]);
    }
}
