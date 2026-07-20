<?php

namespace Database\Factories;

use App\Models\App;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\AppInstallation>
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
            'scopes_json' => ['read-products', 'read-orders'],
            'status' => 'active',
            'installed_at' => now(),
        ];
    }

    /**
     * Indicate that the installation is uninstalled.
     */
    public function uninstalled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'uninstalled',
        ]);
    }
}
