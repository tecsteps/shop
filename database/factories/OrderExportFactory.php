<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderExport>
 */
class OrderExportFactory extends Factory
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
            'requested_by_user_id' => User::factory(),
            'format' => 'csv',
            'filters_json' => [],
            'status' => 'queued',
            'row_count' => null,
            'storage_key' => null,
            'error_message' => null,
            'completed_at' => null,
        ];
    }
}
