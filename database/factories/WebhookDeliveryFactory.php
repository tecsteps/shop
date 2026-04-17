<?php

namespace Database\Factories;

use App\Models\WebhookSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WebhookDelivery>
 */
class WebhookDeliveryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subscription_id' => WebhookSubscription::factory(),
            'event_id' => (string) Str::uuid(),
            'attempt_count' => 1,
            'status' => 'pending',
            'last_attempt_at' => null,
            'next_retry_at' => null,
            'response_code' => null,
            'response_body_snippet' => null,
        ];
    }
}
