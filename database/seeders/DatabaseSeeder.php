<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            OrganizationSeeder::class,
            StoreSeeder::class,
            StoreDomainSeeder::class,
            UserSeeder::class,
            StoreUserSeeder::class,
            StoreSettingsSeeder::class,
            CollectionSeeder::class,
            ProductSeeder::class,
            SearchSettingsSeeder::class,
            ShippingZoneSeeder::class,
            TaxSettingsSeeder::class,
            DiscountSeeder::class,
            CustomerSeeder::class,
            OrderSeeder::class,
            ThemeSeeder::class,
            PageSeeder::class,
            NavigationSeeder::class,
        ]);
    }
}
