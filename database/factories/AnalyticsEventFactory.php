<?php

namespace Database\Factories;

use App\Models\AnalyticsEvent;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnalyticsEvent>
 */
class AnalyticsEventFactory extends Factory
{
    protected $model = AnalyticsEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'type' => fake()->randomElement(['page_view', 'add_to_cart', 'checkout_started', 'checkout_completed']),
            'session_id' => fake()->uuid(),
            'customer_id' => null,
            'properties_json' => [],
            'client_event_id' => null,
            'occurred_at' => now(),
        ];
    }
}
