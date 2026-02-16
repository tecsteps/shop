<?php

namespace Database\Factories;

use App\Enums\ThemeStatus;
use App\Models\Store;
use App\Models\Theme;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Theme> */
class ThemeFactory extends Factory
{
    protected $model = Theme::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'name' => fake()->words(2, true).' Theme',
            'is_active' => false,
            'status' => ThemeStatus::Draft,
        ];
    }

    public function active(): static
    {
        return $this->state([
            'is_active' => true,
            'status' => ThemeStatus::Published,
        ]);
    }
}
