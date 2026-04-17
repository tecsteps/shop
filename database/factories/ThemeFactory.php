<?php

namespace Database\Factories;

use App\Enums\ThemeStatus;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Theme>
 */
class ThemeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'name' => fake()->unique()->words(2, true),
            'version' => '1.0.0',
            'status' => ThemeStatus::Draft->value,
            'published_at' => null,
        ];
    }

    public function published(): self
    {
        return $this->state(fn (): array => [
            'status' => ThemeStatus::Published->value,
            'published_at' => now(),
        ]);
    }
}
