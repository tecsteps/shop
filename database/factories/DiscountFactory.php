<?php

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiscountFactory extends Factory
{
    protected $model = \App\Models\Discount::class;

    public function definition(): array
    {
        return ['store_id' => Store::factory(), 'code' => strtoupper(fake()->unique()->bothify('????##')), 'type' => DiscountType::Code, 'value_type' => DiscountValueType::Percent, 'value_amount' => 10, 'status' => 'active', 'usage_limit' => null, 'usage_count' => 0, 'starts_at' => now()->subMonth(), 'ends_at' => now()->addYear(), 'rules_json' => []];
    }

    public function fixed(int $amountCents): static
    {
        return $this->state(['value_type' => DiscountValueType::Fixed, 'value_amount' => $amountCents]);
    }

    public function freeShipping(): static
    {
        return $this->state(['value_type' => DiscountValueType::FreeShipping, 'value_amount' => 0]);
    }

    public function expired(): static
    {
        return $this->state(['starts_at' => now()->subYear(), 'ends_at' => now()->subDay(), 'status' => 'expired']);
    }

    public function maxedOut(): static
    {
        return $this->state(['usage_limit' => 5, 'usage_count' => 5]);
    }

    public function automatic(): static
    {
        return $this->state(['type' => DiscountType::Automatic, 'code' => null]);
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft']);
    }

    public function disabled(): static
    {
        return $this->state(['status' => 'disabled', 'starts_at' => now()->subDay()]);
    }
}
