<?php

namespace Database\Factories;

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiscountFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $valueType = fake()->randomElement(DiscountValueType::cases());

        return [
            'store_id' => Store::factory(),
            'type' => DiscountType::Code,
            'code' => fake()->unique()->regexify('[A-Z0-9]{8}'),
            'value_type' => $valueType,
            'value_amount' => $valueType === DiscountValueType::Percent
                ? fake()->numberBetween(5, 50)
                : fake()->numberBetween(100, 5000),
            'starts_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'ends_at' => fake()->dateTimeBetween('+1 week', '+1 year'),
            'usage_limit' => fake()->numberBetween(10, 1000),
            'usage_count' => 0,
            'rules_json' => [],
            'status' => fake()->randomElement(DiscountStatus::cases()),
        ];
    }
}
