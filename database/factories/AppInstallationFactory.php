<?php

namespace Database\Factories;

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
            'scopes_json' => ['read_products', 'read_orders'],
            'status' => 'active',
            'installed_at' => now(),
        ];
    }
}
