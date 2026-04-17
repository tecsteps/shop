<?php

namespace Database\Factories;

use App\Enums\ThemeStatus;
use App\Models\Store;
use App\Models\Theme;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Theme>
 */
class ThemeFactory extends Factory
{
    protected $model = Theme::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'name' => fake()->randomElement(['Minimal', 'Classic', 'Modern', 'Vintage']).' Theme',
            'version' => '1.0.'.fake()->numberBetween(0, 9),
            'status' => ThemeStatus::Draft->value,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ThemeStatus::Published->value,
            'published_at' => now(),
        ]);
    }
}
