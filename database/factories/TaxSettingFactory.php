<?php

namespace Database\Factories;

use App\Enums\TaxMode;
use App\Enums\TaxProvider;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'mode' => fake()->randomElement(TaxMode::cases()),
            'provider' => fake()->randomElement(TaxProvider::cases()),
            'prices_include_tax' => false,
            'config_json' => [],
        ];
    }
}
