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

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'type' => DiscountType::Code,
            'code' => strtoupper($this->faker->unique()->lexify('SAVE????')),
            'value_type' => DiscountValueType::Percent,
            'value_amount' => 10,
            'starts_at' => now()->subDay(),
            'ends_at' => null,
            'usage_limit' => null,
            'usage_count' => 0,
            'rules_json' => [],
            'status' => DiscountStatus::Active,
        ];
    }

    public function percent(int $percent): static
    {
        return $this->state([
            'value_type' => DiscountValueType::Percent,
            'value_amount' => $percent,
        ]);
    }

    public function fixed(int $cents): static
    {
        return $this->state([
            'value_type' => DiscountValueType::Fixed,
            'value_amount' => $cents,
        ]);
    }

    public function freeShipping(): static
    {
        return $this->state([
            'value_type' => DiscountValueType::FreeShipping,
            'value_amount' => 0,
        ]);
    }
}
