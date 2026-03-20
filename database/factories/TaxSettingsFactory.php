<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\TaxSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaxSettings>
 */
class TaxSettingsFactory extends Factory
{
    protected $model = TaxSettings::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'mode' => 'manual',
            'provider' => 'none',
            'rate' => 1900,
            'prices_include_tax' => false,
            'tax_name' => 'VAT',
            'is_active' => true,
            'config_json' => [],
        ];
    }

    public function inclusive(): static
    {
        return $this->state(fn (array $attributes) => [
            'prices_include_tax' => true,
        ]);
    }

    public function zeroRate(): static
    {
        return $this->state(fn (array $attributes) => [
            'rate' => 0,
        ]);
    }
}
