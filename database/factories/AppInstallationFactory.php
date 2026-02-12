<?php

namespace Database\Factories;

use App\Models\App;
use App\Models\AppInstallation;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AppInstallation> */
class AppInstallationFactory extends Factory
{
    protected $model = AppInstallation::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'app_id' => App::factory(),
            'store_id' => Store::factory(),
            'status' => 'active',
            'installed_at' => now(),
        ];
    }
}
