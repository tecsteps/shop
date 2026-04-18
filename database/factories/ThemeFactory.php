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

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'name' => 'Default',
            'version' => '1.0.0',
            'status' => ThemeStatus::Draft,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state([
            'status' => ThemeStatus::Published,
            'published_at' => now(),
        ]);
    }
}
