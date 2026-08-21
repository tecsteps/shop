<?php

namespace Database\Seeders;

use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Database\Seeder;

class ShopSeeder extends Seeder
{
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

        $this->seedLegacyCommerceFixtures();
    }

    private function seedLegacyCommerceFixtures(): void
    {
        $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $classic = Product::withoutGlobalScopes()->where('store_id', $store->getKey())->where('handle', 'classic-cotton-t-shirt')->firstOrFail();
        $classicVariant = $classic->variants()->orderBy('position')->firstOrFail();

        InventoryItem::withoutGlobalScopes()->updateOrCreate(
            ['variant_id' => $classicVariant->getKey()],
            ['store_id' => $store->getKey(), 'quantity_on_hand' => 80, 'quantity_reserved' => 0, 'policy' => InventoryPolicy::Deny],
        );

        StoreDomain::query()->updateOrCreate(
            ['hostname' => 'shop.test'],
            ['store_id' => $store->getKey(), 'type' => 'storefront', 'is_primary' => false, 'tls_mode' => 'managed'],
        );

        $legacyProduct = Product::withoutGlobalScopes()->where('store_id', $store->getKey())->where('handle', 'limited-edition-sneakers')->firstOrFail();
        $legacyProduct->update(['handle' => 'sold-out-limited-tee', 'title' => 'Sold Out Limited Tee', 'description' => '<p>A sold-out limited edition tee.</p>', 'description_html' => '<p>A sold-out limited edition tee.</p>', 'vendor' => 'Acme Sport', 'product_type' => 'T-Shirts', 'tags' => ['limited'], 'status' => ProductStatus::Active, 'published_at' => now(), 'sales_count' => 0, 'metadata' => ['compatibility_alias_for' => 'limited-edition-sneakers']]);
        $variant = $legacyProduct->variants()->updateOrCreate(
            ['position' => 0],
            ['title' => 'Default', 'sku' => 'ACME-LEGACY-SOLD-OUT', 'price_amount' => 3999, 'compare_at_amount' => null, 'currency' => 'EUR', 'weight_grams' => 250, 'weight_g' => 250, 'requires_shipping' => true, 'is_default' => true, 'status' => VariantStatus::Active, 'metadata' => []],
        );

        InventoryItem::withoutGlobalScopes()->updateOrCreate(
            ['variant_id' => $variant->getKey()],
            ['store_id' => $store->getKey(), 'quantity_on_hand' => 0, 'quantity_reserved' => 0, 'policy' => InventoryPolicy::Deny],
        );
    }
}
