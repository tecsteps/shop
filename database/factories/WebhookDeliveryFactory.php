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
            'attempt_count' => 1,
            'status' => WebhookDeliveryStatus::Pending,
            'last_attempt_at' => null,
            'response_code' => null,
            'response_body_snippet' => null,
        ];
    }
}
