<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AnalyticsExport>
 */
class AnalyticsExportFactory extends Factory
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
            'from_date' => now()->subDays(29)->toDateString(),
            'to_date' => now()->toDateString(),
            'channel' => 'all',
            'device' => 'all',
            'status' => 'queued',
        ];
    }
}
