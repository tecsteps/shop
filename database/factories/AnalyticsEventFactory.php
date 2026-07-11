<?php

namespace Database\Factories;

use App\Enums\AnalyticsEventType;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AnalyticsEvent>
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
            'type' => fake()->randomElement(AnalyticsEventType::cases()),
            'session_id' => fake()->uuid(),
            'customer_id' => null,
            'properties_json' => ['url' => '/'.fake()->slug(), 'referrer' => fake()->boolean(40) ? fake()->url() : null],
            'client_event_id' => fake()->uuid(),
            'occurred_at' => fake()->dateTimeBetween('-7 days'),
            'created_at' => fake()->dateTimeBetween('-7 days'),
        ];
    }

    public function pageView(): static
    {
        return $this->state(fn (): array => ['type' => AnalyticsEventType::PageView]);
    }

    public function addToCart(): static
    {
        return $this->state(fn (): array => [
            'type' => AnalyticsEventType::AddToCart,
            'properties_json' => ['variant_id' => fake()->randomNumber(), 'quantity' => 1],
        ]);
    }
}
