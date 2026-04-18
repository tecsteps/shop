<?php

namespace Database\Factories;

use App\Models\Theme;
use App\Models\ThemeSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ThemeSettings>
 */
class ThemeSettingsFactory extends Factory
{
    protected $model = ThemeSettings::class;

    public function definition(): array
    {
        return [
            'theme_id' => Theme::factory(),
            'settings_json' => [
                'hero' => [
                    'heading' => 'Welcome to our shop',
                    'subheading' => 'Discover our latest collection',
                    'cta_label' => 'Shop now',
                    'cta_url' => '/collections',
                ],
                'featured_collection_handles' => [],
                'featured_product_handles' => [],
                'colors' => [
                    'primary' => '#111827',
                    'accent' => '#4f46e5',
                ],
                'dark_mode' => 'system',
            ],
            'updated_at' => now(),
        ];
    }
}
