<?php

namespace Database\Factories;

use App\Enums\WebhookSubscriptionStatus;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WebhookSubscription>
 */
class WebhookSubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'app_installation_id' => null,
            'event_type' => 'order.created',
            'target_url' => 'https://example.test/webhooks/'.Str::random(8),
            'signing_secret_encrypted' => Str::random(32),
            'status' => WebhookSubscriptionStatus::Active,
        ];
    }
}
