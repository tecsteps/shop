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
            ThemeSeeder::class,
            ThemeFileSeeder::class,
            ThemeSettingsSeeder::class,
            PageSeeder::class,
            NavigationMenuSeeder::class,
            NavigationItemSeeder::class,
            TaxSettingsSeeder::class,
            ShippingZoneSeeder::class,
            ShippingRateSeeder::class,
            DiscountSeeder::class,
            CustomerSeeder::class,
        ]);
    }
}
