<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\Theme;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Theme> */
class ThemeFactory extends Factory
{
    protected $model = Theme::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'name' => 'Default Theme',
            'version' => '1.0.0',
            'status' => 'published',
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }
}
