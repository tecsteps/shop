<?php

namespace Database\Factories;

use App\Models\Customer;
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
        $store = Store::factory();

        return [
            'store_id' => $store,
            'customer_id' => null,
            'type' => fake()->randomElement(['page_view', 'product_view', 'add_to_cart', 'checkout_started', 'checkout_completed', 'search']),
            'session_id' => 'sess_'.fake()->uuid(),
            'client_event_id' => 'evt_'.fake()->uuid(),
            'properties_json' => [],
            'occurred_at' => now(),
        ];
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn (array $attributes): array => [
            'store_id' => $customer->store_id,
            'customer_id' => $customer->id,
        ]);
    }
}
