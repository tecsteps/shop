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
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'name' => 'Default Theme',
            'version' => '1.0.0',
            'status' => ThemeStatus::Published,
            'published_at' => now(),
        ];
    }

    /**
     * Indicate that the theme is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ThemeStatus::Draft,
            'published_at' => null,
        ]);
    }
}
