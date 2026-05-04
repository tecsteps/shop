<?php

namespace Database\Factories;

use App\Enums\TaxMode;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaxSettings>
 */
class TaxSettingsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'mode' => TaxMode::Manual,
            'provider' => 'none',
            'prices_include_tax' => false,
            'config_json' => [
                'name' => 'VAT',
                'default_rate_bps' => 1900,
                'shipping_taxable' => true,
                'rates' => [
                    ['country' => 'DE', 'rate_bps' => 1900, 'name' => 'VAT'],
                ],
            ],
        ];
    }

    public function inclusive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'prices_include_tax' => true,
        ]);
    }
}
