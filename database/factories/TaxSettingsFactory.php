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
    protected $model = TaxSettings::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'mode' => TaxMode::Manual,
            'rate_percent' => 1900,
            'prices_include_tax' => false,
            'tax_shipping' => false,
            'provider_name' => null,
            'provider_config' => null,
        ];
    }

    public function inclusive(): static
    {
        return $this->state(fn (array $attributes) => [
            'prices_include_tax' => true,
        ]);
    }

    public function withShippingTax(): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_shipping' => true,
        ]);
    }

    public function zeroRate(): static
    {
        return $this->state(fn (array $attributes) => [
            'rate_percent' => 0,
        ]);
    }
}
