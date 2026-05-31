<?php

namespace Database\Factories;

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
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
            'starts_at' => Carbon::now()->subDay(),
            'ends_at' => Carbon::now()->addMonth(),
            'usage_limit' => null,
            'usage_count' => 0,
            'rules_json' => [],
            'status' => DiscountStatus::Active->value,
        ];
    }

    public function percent(int $percent, ?string $code = null): static
    {
        return $this->state(fn (array $attributes) => [
            'value_type' => DiscountValueType::Percent->value,
            'value_amount' => $percent,
            'code' => $code ?? $attributes['code'],
        ]);
    }

    public function fixed(int $amount, ?string $code = null): static
    {
        return $this->state(fn (array $attributes) => [
            'value_type' => DiscountValueType::Fixed->value,
            'value_amount' => $amount,
            'code' => $code ?? $attributes['code'],
        ]);
    }

    public function freeShipping(?string $code = null): static
    {
        return $this->state(fn (array $attributes) => [
            'value_type' => DiscountValueType::FreeShipping->value,
            'value_amount' => 0,
            'code' => $code ?? $attributes['code'],
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => Carbon::now()->subMonth(),
            'ends_at' => Carbon::now()->subDay(),
        ]);
    }

    public function notYetActive(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => Carbon::now()->addDay(),
            'ends_at' => Carbon::now()->addMonth(),
        ]);
    }
}
