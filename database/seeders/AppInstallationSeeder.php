<?php

namespace Database\Seeders;

use App\Models\App;
use App\Models\AppInstallation;
use App\Models\Store;
use Illuminate\Database\Seeder;

class AppInstallationSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $app = App::query()->where('handle', 'reviews')->firstOrFail();

        AppInstallation::withoutGlobalScopes()->updateOrCreate(
            [
                'store_id' => $store->id,
                'app_id' => $app->id,
            ],
            [
                'scopes_json' => ['read-products', 'read-orders', 'read-analytics'],
                'status' => 'active',
                'installed_at' => now()->subMonths(3),
            ],
        );
    }
}
