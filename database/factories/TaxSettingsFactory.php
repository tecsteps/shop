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
            'mode' => TaxMode::Manual->value,
            'provider' => null,
            'prices_include_tax' => false,
            'config_json' => [
                'name' => 'VAT',
                'rate_basis_points' => 1900,
            ],
        ];
    }

    public function pricesInclude(): static
    {
        return $this->state(fn (array $attributes): array => [
            'prices_include_tax' => true,
        ]);
    }
}
