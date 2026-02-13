<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Seeders are called in dependency order: parent records are created
     * before their children, ensuring foreign key constraints are satisfied.
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
