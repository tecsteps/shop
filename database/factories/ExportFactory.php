<?php

namespace Database\Factories;

use App\Models\Export;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Export>
 */
class ExportFactory extends Factory
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
            'user_id' => User::factory(),
            'type' => Export::TypeOrders,
            'format' => Export::FormatCsv,
            'status' => Export::StatusQueued,
            'filters_json' => [],
            'storage_key' => null,
            'row_count' => 0,
            'download_expires_at' => null,
            'completed_at' => null,
            'failed_at' => null,
            'failure_message' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Export::StatusCompleted,
            'storage_key' => 'exports/orders-'.fake()->uuid().'.csv',
            'row_count' => fake()->numberBetween(1, 50),
            'download_expires_at' => now()->addHour(),
            'completed_at' => now(),
        ]);
    }
}
