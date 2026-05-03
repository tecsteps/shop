<?php

namespace Database\Factories;

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
            'event_type' => fake()->randomElement(['order.created', 'order.paid', 'product.updated', 'checkout.completed']),
            'target_url' => 'https://example.com/webhooks/'.Str::random(8),
            'signing_secret_encrypted' => Str::random(40),
            'status' => 'active',
            'consecutive_failures' => 0,
        ];
    }
}
