<?php

namespace Database\Factories;

use App\Enums\WebhookEventType;
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
            'event_type' => WebhookEventType::OrderCreated,
            'target_url' => fake()->url().'/webhooks/shop',
            'signing_secret_encrypted' => 'whsec_'.Str::random(40),
            'status' => WebhookSubscriptionStatus::Active,
        ];
    }
}
