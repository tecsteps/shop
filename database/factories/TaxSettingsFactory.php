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
            'config_json' => ['default_rate_bps' => 1900],
        ];
    }

    /**
     * Use tax-inclusive pricing.
     */
    public function pricesIncludeTax(): static
    {
        return $this->state(fn (array $attributes) => [
            'prices_include_tax' => true,
        ]);
    }

    /**
     * Set an explicit manual rate in basis points.
     */
    public function rateBasisPoints(int $rateBasisPoints): static
    {
        return $this->state(fn (array $attributes) => [
            'config_json' => ['default_rate_bps' => $rateBasisPoints],
        ]);
    }
}
