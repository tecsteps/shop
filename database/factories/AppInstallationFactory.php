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
    protected $model = AppInstallation::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'app_id' => App::factory(),
            'scopes_json' => ['read:orders'],
            'status' => AppInstallationStatus::Active,
            'installed_at' => now(),
        ];
    }
}
