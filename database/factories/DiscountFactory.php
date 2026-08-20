<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiscountFactory extends Factory
{
    protected $model = \App\Models\Discount::class;

    public function definition(): array
    {
        return ['store_id' => Store::factory(), 'code' => strtoupper(fake()->unique()->lexify('CODE??')), 'type' => 'code', 'value_type' => 'percent', 'value_amount' => 10, 'status' => 'active', 'usage_limit' => null, 'usage_count' => 0, 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth(), 'rules_json' => []];
    }
}
