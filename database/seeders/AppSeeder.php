<?php

namespace Database\Seeders;

use App\Models\App;
use Illuminate\Database\Seeder;

class AppSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Product Reviews', 'handle' => 'reviews'],
            ['name' => 'Email Automation', 'handle' => 'email-automation'],
            ['name' => 'Warehouse Sync', 'handle' => 'warehouse-sync'],
        ] as $app) {
            App::query()->updateOrCreate(
                ['handle' => $app['handle']],
                [
                    'name' => $app['name'],
                    'status' => 'active',
                ],
            );
        }
    }
}
