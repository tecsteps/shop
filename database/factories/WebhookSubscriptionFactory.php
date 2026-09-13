<?php

namespace Database\Factories;

use App\Enums\WebhookSubscriptionStatus;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WebhookSubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'app_installation_id' => null,
            'event_type' => fake()->randomElement(['orders/create', 'orders/paid', 'products/update', 'refunds/create']),
            'target_url' => fake()->url(),
            'signing_secret_encrypted' => Str::random(64),
            'status' => fake()->randomElement(WebhookSubscriptionStatus::cases()),
        ];
    }
}
