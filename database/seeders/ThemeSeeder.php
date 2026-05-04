<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\Theme;
use Illuminate\Database\Seeder;

class ThemeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Store::query()->orderBy('id')->get()->each(function (Store $store): void {
            Theme::withoutGlobalScopes()->updateOrCreate(
                [
                    'store_id' => $store->getKey(),
                    'name' => "{$store->name} Default",
                ],
                [
                    'version' => '1.0.0',
                    'status' => 'published',
                    'published_at' => now(),
                ],
            );
        });
    }
}
