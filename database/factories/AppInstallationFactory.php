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

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'app_id' => App::factory(),
            'status' => 'active',
            'config_json' => [],
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'inactive',
        ]);
    }

    public function uninstalled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'uninstalled',
        ]);
    }
}
