<?php

namespace Database\Factories;

use App\Models\AppInstallation;
use App\Models\AppModel;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AppInstallation> */
class AppInstallationFactory extends Factory
{
    protected $model = AppInstallation::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'app_id' => AppModel::factory(),
            'settings_json' => null,
            'installed_at' => now(),
        ];
    }
}
