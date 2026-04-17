<?php

namespace Database\Factories;

use App\Enums\AppInstallationStatus;
use App\Models\App;
use App\Models\AppInstallation;
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
            'app_id' => App::factory(),
            'scopes_json' => ['read_products', 'write_orders'],
            'status' => AppInstallationStatus::Active,
            'installed_at' => now(),
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppInstallationStatus::Suspended,
        ]);
    }

    public function uninstalled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppInstallationStatus::Uninstalled,
        ]);
    }
}
