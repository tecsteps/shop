<?php

namespace Database\Seeders;

use App\Enums\CollectionStatus;
use App\Enums\InventoryPolicy;
use App\Enums\MediaStatus;
use App\Enums\NavigationItemType;
use App\Enums\PageStatus;
use App\Enums\ProductStatus;
use App\Enums\ThemeStatus;
use App\Enums\VariantStatus;
use App\Models\Collection;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Organization;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreSettings;
use App\Models\Theme;
use App\Models\ThemeSettings;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::create([
            'name' => 'Acme Corp',
            'billing_email' => 'billing@acme.test',
        ]);

        $store = Store::create([
            'organization_id' => $organization->id,
            'name' => 'Acme Fashion',
            'handle' => 'acme-fashion',
            'status' => 'active',
            'default_currency' => 'EUR',
            'default_locale' => 'en',
            'timezone' => 'UTC',
        ]);

        StoreDomain::create([
            'store_id' => $store->id,
            'hostname' => 'acme-fashion.test',
            'type' => 'storefront',
            'is_primary' => true,
        ]);

        StoreSettings::create([
            'store_id' => $store->id,
            'settings_json' => [],
        ]);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@acme.test',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);

        $admin->stores()->attach($store->id, ['role' => 'owner']);

        Customer::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'email' => 'customer@acme.test',
            'password_hash' => Hash::make('password'),
            'name' => 'John Doe',
            'marketing_opt_in' => false,
        ]);

        $this->seedCatalog($store);
        $this->seedThemeAndNavigation($store);
    }

    private function seedCatalog(Store $store): void
    {
        // Collections
        $tShirts = $this->createCollection($store, 'T-Shirts', 't-shirts');
        $newArrivals = $this->createCollection($store, 'New Arrivals', 'new-arrivals');
        $jeans = $this->createCollection($store, 'Jeans', 'jeans');
        $dresses = $this->createCollection($store, 'Dresses', 'dresses');
        $accessories = $this->createCollection($store, 'Accessories', 'accessories');

        // Product #1: Classic Cotton T-Shirt
        $p1 = $this->createProduct($store, 'Classic Cotton T-Shirt', 'classic-cotton-t-shirt', ProductStatus::Active, 'Acme Basics', 'T-Shirts');
        $this->addSizeColorOptions($p1, ['S', 'M', 'L', 'XL'], ['Black', 'White', 'Navy'], 2499, $store);
        $this->addMedia($p1, 'classic-cotton-t-shirt');
        $tShirts->products()->attach($p1->id, ['position' => 0]);
        $newArrivals->products()->attach($p1->id, ['position' => 0]);

        // Product #2: Premium Slim Fit Jeans (with compare_at_price/sale)
        $p2 = $this->createProduct($store, 'Premium Slim Fit Jeans', 'premium-slim-fit-jeans', ProductStatus::Active, 'Acme Denim', 'Jeans');
        $this->addSizeColorOptions($p2, ['28', '30', '32', '34'], ['Indigo', 'Black'], 5999, $store, 7999);
        $this->addMedia($p2, 'premium-slim-fit-jeans');
        $jeans->products()->attach($p2->id, ['position' => 0]);
        $newArrivals->products()->attach($p2->id, ['position' => 1]);

        // Products #3 - #14: General active products
        $products = [
            ['Linen Summer Dress', 'linen-summer-dress', 'Dresses', 4499, $dresses],
            ['Wool Blend Cardigan', 'wool-blend-cardigan', 'Knitwear', 6999, $newArrivals],
            ['Organic Cotton Hoodie', 'organic-cotton-hoodie', 'T-Shirts', 3999, $tShirts],
            ['Stretch Chino Pants', 'stretch-chino-pants', 'Pants', 4499, $jeans],
            ['Silk Evening Blouse', 'silk-evening-blouse', 'Blouses', 7999, $dresses],
            ['Denim Jacket Classic', 'denim-jacket-classic', 'Jackets', 8999, $jeans],
            ['Cashmere V-Neck Sweater', 'cashmere-v-neck-sweater', 'Knitwear', 12999, $newArrivals],
            ['Relaxed Fit T-Shirt', 'relaxed-fit-t-shirt', 'T-Shirts', 1999, $tShirts],
            ['High-Waist Wide Leg Jeans', 'high-waist-wide-leg-jeans', 'Jeans', 6499, $jeans],
            ['Cotton Polo Shirt', 'cotton-polo-shirt', 'T-Shirts', 2999, $tShirts],
        ];

        foreach ($products as $i => [$title, $handle, $type, $price, $collection]) {
            $p = $this->createProduct($store, $title, $handle, ProductStatus::Active, 'Acme Fashion', $type);
            $this->addSimpleVariants($p, $price, $store);
            $this->addMedia($p, $handle);
            $collection->products()->attach($p->id, ['position' => $i + 1]);
        }

        // Product #13: Leather Belt
        $p13 = $this->createProduct($store, 'Leather Belt', 'leather-belt', ProductStatus::Active, 'Acme Accessories', 'Accessories');
        $this->addSimpleVariants($p13, 2499, $store);
        $accessories->products()->attach($p13->id, ['position' => 0]);

        // Product #14: Wool Scarf
        $p14 = $this->createProduct($store, 'Wool Scarf', 'wool-scarf', ProductStatus::Active, 'Acme Accessories', 'Accessories');
        $this->addSimpleVariants($p14, 3499, $store);
        $accessories->products()->attach($p14->id, ['position' => 1]);

        // Product #15: Draft product (must NOT appear on storefront)
        $p15 = $this->createProduct($store, 'Unreleased Summer Collection Piece', 'unreleased-summer-piece', ProductStatus::Draft, 'Acme Fashion', 'T-Shirts');
        $this->addSimpleVariants($p15, 2999, $store);

        // Product #16: Archived product
        $p16 = $this->createProduct($store, 'Discontinued Winter Coat', 'discontinued-winter-coat', ProductStatus::Archived, 'Acme Fashion', 'Jackets');
        $this->addSimpleVariants($p16, 14999, $store);

        // Product #17: Active, inventory 0, policy deny (Sold out)
        $p17 = $this->createProduct($store, 'Limited Edition Sneakers', 'limited-edition-sneakers', ProductStatus::Active, 'Acme Footwear', 'Shoes');
        $this->addSimpleVariants($p17, 9999, $store, 0, InventoryPolicy::Deny);
        $this->addMedia($p17, 'limited-edition-sneakers');
        $newArrivals->products()->attach($p17->id, ['position' => 5]);

        // Product #18: Active, inventory 0, policy continue (Available on backorder)
        $p18 = $this->createProduct($store, 'Handmade Tote Bag', 'handmade-tote-bag', ProductStatus::Active, 'Acme Accessories', 'Accessories');
        $this->addSimpleVariants($p18, 4999, $store, 0, InventoryPolicy::Continue);
        $this->addMedia($p18, 'handmade-tote-bag');
        $accessories->products()->attach($p18->id, ['position' => 2]);

        // Products #19-20: Filler active products
        $p19 = $this->createProduct($store, 'Bamboo Fiber Socks 3-Pack', 'bamboo-fiber-socks', ProductStatus::Active, 'Acme Basics', 'Accessories');
        $this->addSimpleVariants($p19, 1299, $store);
        $accessories->products()->attach($p19->id, ['position' => 3]);

        $p20 = $this->createProduct($store, 'UV Protection Sunglasses', 'uv-protection-sunglasses', ProductStatus::Active, 'Acme Accessories', 'Accessories');
        $this->addSimpleVariants($p20, 3999, $store);
        $accessories->products()->attach($p20->id, ['position' => 4]);
    }

    private function createProduct(Store $store, string $title, string $handle, ProductStatus $status, string $vendor, string $type): Product
    {
        return Product::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'title' => $title,
            'handle' => $handle,
            'status' => $status,
            'description_html' => "<p>$title - high quality fashion item.</p>",
            'vendor' => $vendor,
            'product_type' => $type,
            'tags' => [],
            'published_at' => $status === ProductStatus::Active ? now() : null,
        ]);
    }

    private function createCollection(Store $store, string $title, string $handle): Collection
    {
        return Collection::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'title' => $title,
            'handle' => $handle,
            'status' => CollectionStatus::Active,
            'type' => 'manual',
        ]);
    }

    private function addSizeColorOptions(Product $product, array $sizes, array $colors, int $price, Store $store, ?int $compareAt = null): void
    {
        $sizeOption = ProductOption::create(['product_id' => $product->id, 'name' => 'Size', 'position' => 0]);
        $colorOption = ProductOption::create(['product_id' => $product->id, 'name' => 'Color', 'position' => 1]);

        $sizeValues = [];
        foreach ($sizes as $i => $size) {
            $sizeValues[] = ProductOptionValue::create(['product_option_id' => $sizeOption->id, 'value' => $size, 'position' => $i]);
        }

        $colorValues = [];
        foreach ($colors as $i => $color) {
            $colorValues[] = ProductOptionValue::create(['product_option_id' => $colorOption->id, 'value' => $color, 'position' => $i]);
        }

        $position = 0;
        $isFirst = true;
        foreach ($sizeValues as $sizeVal) {
            foreach ($colorValues as $colorVal) {
                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => strtoupper(substr($product->handle, 0, 3)).'-'.strtoupper($sizeVal->value).'-'.strtoupper(substr($colorVal->value, 0, 3)),
                    'price_amount' => $price,
                    'compare_at_amount' => $compareAt,
                    'currency' => 'EUR',
                    'is_default' => $isFirst,
                    'position' => $position,
                    'status' => VariantStatus::Active,
                ]);

                $variant->optionValues()->attach([$sizeVal->id, $colorVal->id]);

                InventoryItem::withoutGlobalScopes()->create([
                    'store_id' => $store->id,
                    'variant_id' => $variant->id,
                    'quantity_on_hand' => rand(5, 50),
                    'quantity_reserved' => 0,
                    'policy' => InventoryPolicy::Deny,
                ]);

                $isFirst = false;
                $position++;
            }
        }
    }

    private function addSimpleVariants(Product $product, int $price, Store $store, int $quantity = 25, InventoryPolicy $policy = InventoryPolicy::Deny): void
    {
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => strtoupper(substr(str_replace('-', '', $product->handle), 0, 8)),
            'price_amount' => $price,
            'currency' => 'EUR',
            'is_default' => true,
            'position' => 0,
            'status' => VariantStatus::Active,
        ]);

        InventoryItem::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'variant_id' => $variant->id,
            'quantity_on_hand' => $quantity,
            'quantity_reserved' => 0,
            'policy' => $policy,
        ]);
    }

    private function addMedia(Product $product, string $handle): void
    {
        ProductMedia::create([
            'product_id' => $product->id,
            'type' => 'image',
            'storage_key' => "products/{$handle}.jpg",
            'alt_text' => $product->title,
            'width' => 1200,
            'height' => 1200,
            'mime_type' => 'image/jpeg',
            'byte_size' => 150000,
            'position' => 0,
            'status' => MediaStatus::Ready,
        ]);
    }

    private function seedThemeAndNavigation(Store $store): void
    {
        // Default theme
        $theme = Theme::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'name' => 'Default Theme',
            'version' => '1.0.0',
            'status' => ThemeStatus::Published,
            'published_at' => now(),
        ]);

        ThemeSettings::create([
            'theme_id' => $theme->id,
            'settings_json' => [
                'announcement_bar_enabled' => true,
                'announcement_bar_text' => 'Free shipping on orders over 50 EUR',
                'announcement_bar_link' => '/collections',
                'announcement_bar_bg_color' => '#1f2937',
                'sticky_header' => true,
                'hero_heading' => 'Welcome to Acme Fashion',
                'hero_subheading' => 'Discover our latest collection of premium clothing',
                'hero_cta_text' => 'Shop New Arrivals',
                'hero_cta_link' => '/collections/new-arrivals',
                'featured_collections_count' => 4,
                'featured_products_count' => 8,
            ],
        ]);

        // About page
        Page::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'title' => 'About',
            'handle' => 'about',
            'body_html' => '<h2>About Acme Fashion</h2><p>Acme Fashion is a modern fashion brand committed to quality, sustainability, and style. Founded in 2020, we design clothing that looks good and feels great.</p><p>Our mission is to make premium fashion accessible to everyone.</p>',
            'status' => PageStatus::Published,
            'published_at' => now(),
        ]);

        // Main menu
        $mainMenu = NavigationMenu::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'handle' => 'main-menu',
            'title' => 'Main Menu',
        ]);

        // Get collections for navigation
        $collections = Collection::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->get();

        $position = 0;
        foreach ($collections as $collection) {
            NavigationItem::create([
                'menu_id' => $mainMenu->id,
                'type' => NavigationItemType::Collection,
                'label' => $collection->title,
                'resource_id' => $collection->id,
                'position' => $position++,
            ]);
        }

        // About page link
        $aboutPage = Page::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('handle', 'about')
            ->first();

        NavigationItem::create([
            'menu_id' => $mainMenu->id,
            'type' => NavigationItemType::Page,
            'label' => 'About',
            'resource_id' => $aboutPage->id,
            'position' => $position,
        ]);

        // Footer menu
        $footerMenu = NavigationMenu::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'handle' => 'footer-menu',
            'title' => 'Footer Menu',
        ]);

        NavigationItem::create([
            'menu_id' => $footerMenu->id,
            'type' => NavigationItemType::Page,
            'label' => 'About',
            'resource_id' => $aboutPage->id,
            'position' => 0,
        ]);

        NavigationItem::create([
            'menu_id' => $footerMenu->id,
            'type' => NavigationItemType::Link,
            'label' => 'Contact',
            'url' => '/pages/contact',
            'position' => 1,
        ]);
    }
}
