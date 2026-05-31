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
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'mode' => TaxMode::Manual->value,
            'provider' => 'none',
            'prices_include_tax' => false,
            'config_json' => ['default_rate' => 1900, 'name' => 'VAT'],
        ];
    }

    public function rate(int $basisPoints, string $name = 'VAT'): static
    {
        return $this->state(fn (array $attributes) => [
            'config_json' => array_merge($attributes['config_json'] ?? [], [
                'default_rate' => $basisPoints,
                'name' => $name,
            ]),
        ]);
    }

    public function inclusive(): static
    {
        return $this->state(fn (array $attributes) => ['prices_include_tax' => true]);
    }
}
