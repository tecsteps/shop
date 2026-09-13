<?php

namespace Database\Factories;

use App\Enums\WebhookDeliveryStatus;
use App\Models\WebhookSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

class WebhookDeliveryFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'subscription_id' => WebhookSubscription::factory(),
            'event_id' => fake()->uuid(),
            'attempt_count' => 1,
            'status' => fake()->randomElement(WebhookDeliveryStatus::cases()),
            'last_attempt_at' => fake()->dateTime(),
            'response_code' => fake()->randomElement([200, 201, 400, 500]),
            'response_body_snippet' => fake()->sentence(),
        ];
    }
}
