<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->call([
                OrganizationSeeder::class,
                StoreSeeder::class,
                StoreDomainSeeder::class,
                UserSeeder::class,
                StoreUserSeeder::class,
                StoreSettingsSeeder::class,
                TaxSettingsSeeder::class,
                ShippingSeeder::class,
                CollectionSeeder::class,
                ProductSeeder::class,
                ProductMediaSeeder::class,
                DiscountSeeder::class,
                CustomerSeeder::class,
                OrderSeeder::class,
                ThemeSeeder::class,
                PageSeeder::class,
                NavigationSeeder::class,
                AnalyticsSeeder::class,
                SearchSettingsSeeder::class,
            ]);
        });

        $search = app(\App\Services\SearchService::class);

        foreach (\App\Models\Store::query()->get() as $store) {
            $search->reindex($store);
        }
    }
}
