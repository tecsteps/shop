<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\AnalyticsEvent>
 */
class AnalyticsEventFactory extends Factory
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
            'type' => 'page_view',
            'session_id' => fake()->uuid(),
            'customer_id' => null,
            'properties_json' => [],
            'client_event_id' => fake()->uuid(),
            'occurred_at' => now(),
            'created_at' => now(),
        ];
    }
}
