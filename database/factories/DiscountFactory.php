<?php

namespace Database\Factories;

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Discount;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Discount>
 */
class DiscountFactory extends Factory
{
    protected $model = Discount::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'type' => DiscountType::Code,
            'code' => strtoupper(fake()->bothify('SAVE##')),
            'value_type' => fake()->randomElement([
                DiscountValueType::Percent,
                DiscountValueType::Fixed,
                DiscountValueType::FreeShipping,
            ]),
            'value_amount' => fake()->numberBetween(0, 2000),
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->addDays(30),
            'usage_limit' => null,
            'usage_count' => 0,
            'rules_json' => [],
            'status' => DiscountStatus::Active,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'status' => DiscountStatus::Expired,
            'ends_at' => now()->subDay(),
        ]);
    }
}
