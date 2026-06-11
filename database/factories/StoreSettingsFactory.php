<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StoreSettings>
 */
class StoreSettingsFactory extends Factory
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
            'settings_json' => [
                'store_name' => fake()->company(),
                'contact_email' => fake()->companyEmail(),
                'order_number_prefix' => '#',
                'order_number_start' => 1001,
            ],
        ];
    }
}
