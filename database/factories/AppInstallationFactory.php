<?php

namespace Database\Factories;

use App\Enums\AppInstallationStatus;
use App\Models\App;
use App\Models\AppInstallation;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppInstallation>
 */
class AppInstallationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'app_id' => App::factory(),
            'scopes_json' => ['read_products', 'write_orders'],
            'status' => AppInstallationStatus::Active,
            'installed_at' => now(),
        ];
    }
}
