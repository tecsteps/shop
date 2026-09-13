<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnalyticsEventFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'type' => fake()->randomElement(['page_view', 'product_view', 'add_to_cart', 'remove_from_cart', 'checkout_started', 'checkout_completed', 'search']),
            'session_id' => fake()->uuid(),
            'customer_id' => null,
            'properties_json' => [],
            'client_event_id' => fake()->uuid(),
            'occurred_at' => fake()->dateTime(),
        ];
    }
}
