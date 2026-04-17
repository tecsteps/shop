<?php

namespace Database\Factories;

use App\Models\App;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AppInstallation>
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
            'scopes_json' => [],
            'status' => 'active',
            'installed_at' => now(),
            'uninstalled_at' => null,
        ];
    }
}
