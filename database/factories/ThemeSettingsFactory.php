<?php

namespace Database\Factories;

use App\Models\Theme;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ThemeSettings>
 */
class ThemeSettingsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'theme_id' => Theme::factory(),
            'settings_json' => [
                'announcement' => [
                    'enabled' => true,
                    'text' => 'Free shipping on orders over 75 EUR',
                    'link' => '/collections/summer-essentials',
                ],
                'home' => [
                    'hero_heading' => 'Acme Fashion',
                    'hero_subheading' => 'Everyday essentials with sharp fits and breathable fabrics.',
                    'hero_cta_label' => 'Shop new arrivals',
                    'hero_cta_url' => '/collections/summer-essentials',
                ],
                'footer' => [
                    'contact_email' => 'support@acme.test',
                ],
            ],
        ];
    }
}
