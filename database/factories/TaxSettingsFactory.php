<?php

namespace Database\Factories;

use App\Enums\TaxMode;
use App\Models\Store;
use App\Models\TaxSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaxSettings>
 */
class TaxSettingsFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'mode' => TaxMode::Manual,
            'provider' => 'none',
            'prices_include_tax' => false,
            'config_json' => ['default_rate_bps' => 1900],
        ];
    }

    public function inclusive(): static
    {
        return $this->state(fn (array $attributes) => [
            'prices_include_tax' => true,
        ]);
    }
}
