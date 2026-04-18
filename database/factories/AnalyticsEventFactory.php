<?php

namespace Database\Factories;

use App\Enums\AnalyticsEventType;
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
            'type' => AnalyticsEventType::PageView->value,
            'session_id' => $this->faker->uuid(),
            'customer_id' => null,
            'properties_json' => [],
            'client_event_id' => $this->faker->unique()->uuid(),
            'occurred_at' => now(),
            'created_at' => now(),
        ];
    }
}
