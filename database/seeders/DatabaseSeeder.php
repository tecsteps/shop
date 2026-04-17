<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            OrganizationSeeder::class,      // 1. Organizations
            StoreSeeder::class,             // 2. Stores
            StoreDomainSeeder::class,       // 3. Store domains
            UserSeeder::class,              // 4+5. Users + store-user role assignments
            StoreSettingsSeeder::class,     // 6. Store settings
            TaxSettingsSeeder::class,       // 7. Tax settings
            ShippingSeeder::class,          // 8. Shipping zones and rates
            CollectionSeeder::class,        // 9. Collections
            ProductSeeder::class,           // 10. Products, options, variants, inventory
            DiscountSeeder::class,          // 11. Discount codes
            CustomerSeeder::class,          // 12. Customers with addresses
            OrderSeeder::class,             // 13. Orders, lines, payments, fulfillments, refunds
            ThemeSeeder::class,             // 14. Themes with settings
            PageSeeder::class,              // 15. Content pages
            NavigationSeeder::class,        // 16. Navigation menus
            AnalyticsSeeder::class,         // 17. Analytics daily + events
            SearchSettingsSeeder::class,    // 18. Search settings
        ]);
    }
}
