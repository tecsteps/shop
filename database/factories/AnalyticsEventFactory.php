<?php

namespace Database\Factories;

use App\Enums\AnalyticsEventType;
use App\Models\Customer;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
        $type = fake()->randomElement(AnalyticsEventType::cases());

        return [
            'store_id' => Store::factory(),
            'type' => $type,
            'session_id' => 'sess_'.Str::random(16),
            'customer_id' => null,
            'properties_json' => [
                'url' => fake()->url(),
                'referrer' => fake()->optional()->url(),
            ],
            'client_event_id' => 'evt_'.Str::uuid()->toString(),
            'occurred_at' => now(),
            'created_at' => now(),
        ];
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn (array $attributes): array => [
            'store_id' => $customer->store_id,
            'customer_id' => $customer->getKey(),
        ]);
    }
}
