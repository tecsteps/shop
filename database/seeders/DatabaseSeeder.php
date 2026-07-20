<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeds the complete demo scenario (spec 07 §3) in dependency order.
 * All seeders are idempotent (updateOrCreate/firstOrCreate), so
 * `php artisan db:seed` can be run repeatedly without duplicating data.
 *
 * ============================================
 *   ADMIN PANEL CREDENTIALS
 * ============================================
 *
 * Admin Login:
 *   Email:    admin@acme.test
 *   Password: password
 *   Store:    Acme Fashion (acme-fashion.test)
 *   Role:     owner
 *
 * Staff Login:
 *   Email:    staff@acme.test
 *   Password: password
 *   Store:    Acme Fashion
 *   Role:     staff
 *
 * Support Login:
 *   Email:    support@acme.test
 *   Password: password
 *   Store:    Acme Fashion
 *   Role:     support
 *
 * Manager Login:
 *   Email:    manager@acme.test
 *   Password: password
 *   Store:    Acme Fashion
 *   Role:     admin
 *
 * Admin Two Login:
 *   Email:    admin2@acme.test
 *   Password: password
 *   Store:    Acme Electronics (acme-electronics.test)
 *   Role:     owner
 *
 * ============================================
 *   STOREFRONT CUSTOMER CREDENTIALS
 * ============================================
 *
 * Primary Test Customer:
 *   Email:    customer@acme.test
 *   Password: password
 *   Store:    Acme Fashion
 *   Addresses: Home (default), Work
 *
 * Secondary Test Customer:
 *   Email:    jane@example.com
 *   Password: password
 *   Store:    Acme Fashion
 *   Addresses: Home (default)
 *
 * ============================================
 *   DISCOUNT CODES (Acme Fashion)
 * ============================================
 *
 * WELCOME10  - 10% off, min order 20.00 EUR (active, usable)
 * FLAT5      - 5.00 EUR off (active, usable)
 * FREESHIP   - Free shipping (active, usable)
 * EXPIRED20  - 20% off (expired, must be rejected)
 * MAXED      - 10% off (usage limit reached, must be rejected)
 *
 * ============================================
 *   SPECIAL PRODUCTS FOR TESTING
 * ============================================
 *
 * Draft:      "Unreleased Winter Jacket" (not visible on storefront)
 * Archived:   "Discontinued Raincoat" (not visible on storefront)
 * Sold Out:   "Limited Edition Sneakers" (deny policy, qty 0)
 * Backorder:  "Backorder Denim Jacket" (continue policy, qty 0)
 * Digital:    "Gift Card" (no shipping required)
 * Expensive:  "Cashmere Overcoat" (499.99 EUR)
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database (spec 07 §1 execution order).
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
