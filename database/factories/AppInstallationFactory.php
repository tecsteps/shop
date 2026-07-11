<?php

namespace Database\Factories;

use App\Enums\AppInstallationStatus;
use App\Models\App;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AppInstallation>
 */
class AppInstallationFactory extends Factory
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
            'app_id' => App::factory(),
            'scopes_json' => ['read-products'],
            'status' => AppInstallationStatus::Active,
            'installed_at' => now(),
        ];
    }
}
