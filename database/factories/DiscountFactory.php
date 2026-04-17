<?php

namespace Database\Factories;

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Discount>
 */
class DiscountFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'type' => DiscountType::Code->value,
            'code' => strtoupper(Str::random(8)),
            'value_type' => DiscountValueType::Percent->value,
            'value_amount' => 10,
            'starts_at' => now()->subHour(),
            'ends_at' => null,
            'usage_limit' => null,
            'usage_count' => 0,
            'rules_json' => [],
            'status' => DiscountStatus::Active->value,
        ];
    }

    public function percent(int $percent): self
    {
        return $this->state(fn (): array => [
            'value_type' => DiscountValueType::Percent->value,
            'value_amount' => $percent,
        ]);
    }

    public function fixed(int $amountCents): self
    {
        return $this->state(fn (): array => [
            'value_type' => DiscountValueType::Fixed->value,
            'value_amount' => $amountCents,
        ]);
    }

    public function freeShipping(): self
    {
        return $this->state(fn (): array => [
            'value_type' => DiscountValueType::FreeShipping->value,
            'value_amount' => 0,
        ]);
    }

    public function expired(): self
    {
        return $this->state(fn (): array => [
            'starts_at' => now()->subDays(30),
            'ends_at' => now()->subDays(1),
        ]);
    }

    public function exhausted(): self
    {
        return $this->state(fn (): array => [
            'usage_limit' => 1,
            'usage_count' => 1,
        ]);
    }
}
