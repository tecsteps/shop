<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            OrganizationSeeder::class,
            StoreSeeder::class,
            UserSeeder::class,
            ShippingSeeder::class,
            CollectionSeeder::class,
            ProductSeeder::class,
            DiscountSeeder::class,
            CustomerSeeder::class,
            OrderSeeder::class,
            ThemeSeeder::class,
            PageSeeder::class,
            NavigationSeeder::class,
            AnalyticsSeeder::class,
            SearchSettingsSeeder::class,
        ]);
    }
}
