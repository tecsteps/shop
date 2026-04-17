<?php

namespace Database\Factories;

use App\Models\AnalyticsEvent;
use App\Models\Customer;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AnalyticsEvent>
 */
class AnalyticsEventFactory extends Factory
{
    protected $model = AnalyticsEvent::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'type' => fake()->randomElement(['page_view', 'product_view', 'add_to_cart', 'checkout_started']),
            'session_id' => fake()->uuid(),
            'customer_id' => Customer::factory(),
            'properties_json' => ['source' => 'factory'],
            'client_event_id' => fake()->uuid(),
            'occurred_at' => now(),
            'created_at' => now(),
        ];
    }
}
