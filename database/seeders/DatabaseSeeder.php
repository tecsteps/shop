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
            TaxSettingsSeeder::class,
            ShippingSeeder::class,
            CollectionSeeder::class,
            ProductSeeder::class,
            SearchSeeder::class,
            DiscountSeeder::class,
            CustomerSeeder::class,
            AnalyticsSeeder::class,
            AppsWebhookSeeder::class,
            ThemeSeeder::class,
            PageSeeder::class,
            NavigationSeeder::class,
        ]);
    }
}
