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

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'type' => 'page_view',
            'session_id' => fake()->uuid(),
            'properties_json' => [],
            'client_event_id' => fake()->unique()->uuid(),
            'occurred_at' => now(),
        ];
    }
}
