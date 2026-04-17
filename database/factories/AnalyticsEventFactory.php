<?php

namespace Database\Factories;

use App\Enums\AnalyticsEventType;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AnalyticsEvent>
 */
class AnalyticsEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'type' => AnalyticsEventType::PageView->value,
            'session_id' => Str::random(16),
            'customer_id' => null,
            'properties_json' => [],
            'client_event_id' => null,
            'occurred_at' => now(),
            'created_at' => now(),
        ];
    }
}
