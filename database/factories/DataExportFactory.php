<?php

namespace Database\Factories;

use App\Enums\ExportStatus;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DataExport>
 */
class DataExportFactory extends Factory
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
            'type' => 'orders',
            'format' => 'csv',
            'status' => ExportStatus::Completed,
            'filters_json' => [],
            'row_count' => fake()->numberBetween(1, 25),
            'storage_key' => 'exports/orders/'.fake()->uuid().'.csv',
            'download_expires_at' => now()->addHour(),
            'completed_at' => now(),
        ];
    }
}
