<?php

namespace Database\Factories;

use App\Enums\TaxMode;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\TaxSettings>
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
     * Indicate that displayed prices include tax.
     */
    public function inclusive(): static
    {
        return $this->state(fn (array $attributes) => [
            'prices_include_tax' => true,
        ]);
    }

    /**
     * Set the default manual rate in basis points.
     */
    public function withRate(int $rateBps): static
    {
        return $this->state(fn (array $attributes) => [
            'config_json' => array_merge($attributes['config_json'] ?? [], ['default_rate_bps' => $rateBps]),
        ]);
    }
}
