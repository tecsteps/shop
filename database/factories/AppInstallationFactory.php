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
            'installed_at' => now(),
            'uninstalled_at' => null,
            'settings_json' => [],
        ];
    }

    /**
     * Indicate that the installation is uninstalled.
     */
    public function uninstalled(): static
    {
        return $this->state(fn (array $attributes) => [
            'uninstalled_at' => now(),
        ]);
    }
}
