<?php

namespace Database\Factories;

use App\Models\AnalyticsEvent;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
            'type' => fake()->randomElement(['page_view', 'product_view', 'add_to_cart', 'search']),
            'session_id' => Str::uuid()->toString(),
            'customer_id' => null,
            'properties_json' => [],
            'client_event_id' => null,
            'occurred_at' => now(),
            'created_at' => now(),
        ];
    }

    public function pageView(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'page_view',
            'properties_json' => ['url' => '/'],
        ]);
    }

    public function productView(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'product_view',
            'properties_json' => ['product_id' => fake()->numberBetween(1, 100)],
        ]);
    }
}
