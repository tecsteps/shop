<?php

namespace Database\Factories;

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
            'name' => 'Dawn',
            'version' => '1.0.0',
            'status' => 'draft',
            'published_at' => null,
        ];
    }
}
