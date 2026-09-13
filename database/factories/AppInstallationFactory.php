<?php

namespace Database\Factories;

use App\Enums\AppInstallationStatus;
use App\Models\App;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppInstallationFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'app_id' => App::factory(),
            'scopes_json' => ['read_products', 'read_orders'],
            'status' => fake()->randomElement(AppInstallationStatus::cases()),
            'installed_at' => fake()->dateTime(),
        ];
    }
}
