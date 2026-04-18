<?php

namespace Database\Seeders;

use App\Enums\CollectionStatus;
use App\Enums\CollectionType;
use App\Enums\InventoryPolicy;
use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Enums\NavigationItemType;
use App\Enums\PageStatus;
use App\Enums\ProductStatus;
use App\Enums\StoreDomainType;
use App\Enums\StoreStatus;
use App\Enums\StoreUserRole;
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

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->create([
            'name' => 'Acme Holdings',
            'billing_email' => 'billing@acme.test',
        ]);

        $store = Store::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Acme Fashion',
            'handle' => 'acme-fashion',
            'status' => StoreStatus::Active,
            'default_currency' => 'EUR',
            'default_locale' => 'en',
            'timezone' => 'Europe/Berlin',
        ]);

        StoreDomain::query()->create([
            'store_id' => $store->id,
            'hostname' => 'shop.test',
            'type' => StoreDomainType::Storefront,
            'is_primary' => true,
            'tls_mode' => 'managed',
            'created_at' => now(),
        ]);

        StoreSettings::query()->create([
            'store_id' => $store->id,
            'settings_json' => [
                'announcement_bar' => 'Free shipping on orders over 50',
                'support_email' => 'support@acme.test',
            ],
            'updated_at' => now(),
        ]);

        $owner = User::query()->create([
            'name' => 'Ada Owner',
            'email' => 'owner@acme.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        \DB::table('store_users')->insert([
            'store_id' => $store->id,
            'user_id' => $owner->id,
            'role' => StoreUserRole::Owner->value,
            'created_at' => now(),
        ]);

        app()->instance('current_store', $store);

        Customer::query()->create([
            'store_id' => $store->id,
            'email' => 'buyer@example.com',
            'password' => Hash::make('password'),
            'first_name' => 'Billy',
            'last_name' => 'Buyer',
            'state' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->seedTheming($store);
        $this->seedCatalog($store);

        app()->forgetInstance('current_store');
    }

    protected function seedTheming(Store $store): void
    {
        $theme = Theme::query()->create([
            'store_id' => $store->id,
            'name' => 'Default',
            'version' => '1.0.0',
            'status' => ThemeStatus::Published,
            'published_at' => now(),
        ]);

        ThemeSettings::query()->create([
            'theme_id' => $theme->id,
            'settings_json' => [
                'hero' => [
                    'heading' => 'Elevated essentials',
                    'subheading' => 'Timeless pieces for modern wardrobes.',
                    'cta_label' => 'Shop the collection',
                    'cta_url' => '/collections',
                ],
                'featured_collection_handles' => ['featured'],
                'featured_product_handles' => [],
                'colors' => [
                    'primary' => '#111827',
                    'accent' => '#4f46e5',
                ],
                'dark_mode' => 'system',
            ],
            'updated_at' => now(),
        ]);

        Page::query()->create([
            'store_id' => $store->id,
            'title' => 'About Us',
            'handle' => 'about',
            'body_html' => '<p>Acme Fashion is a demo store created to showcase the platform.</p>',
            'status' => PageStatus::Published,
            'published_at' => now(),
        ]);

        $menu = NavigationMenu::query()->create([
            'store_id' => $store->id,
            'handle' => 'main-menu',
            'title' => 'Main menu',
        ]);

        $mainItems = [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'Collections', 'url' => '/collections'],
            ['label' => 'About', 'url' => '/pages/about'],
        ];

        foreach ($mainItems as $position => $item) {
            NavigationItem::query()->create([
                'menu_id' => $menu->id,
                'type' => NavigationItemType::Link,
                'label' => $item['label'],
                'url' => $item['url'],
                'position' => $position,
            ]);
        }
    }

    protected function seedCatalog(Store $store): void
    {
        $tshirt = Product::query()->create([
            'store_id' => $store->id,
            'title' => 'Organic Cotton T-Shirt',
            'handle' => 'organic-cotton-t-shirt',
            'status' => ProductStatus::Active,
            'description_html' => '<p>Soft, breathable, sustainably sourced.</p>',
            'vendor' => 'Acme Apparel',
            'product_type' => 'Apparel',
            'tags' => ['summer', 'bestseller'],
            'published_at' => now(),
        ]);

        $sizeOption = ProductOption::query()->create([
            'product_id' => $tshirt->id,
            'name' => 'Size',
            'position' => 0,
        ]);

        $colorOption = ProductOption::query()->create([
            'product_id' => $tshirt->id,
            'name' => 'Color',
            'position' => 1,
        ]);

        $sizeValues = [];
        foreach (['S', 'M', 'L'] as $idx => $size) {
            $sizeValues[] = ProductOptionValue::query()->create([
                'product_option_id' => $sizeOption->id,
                'value' => $size,
                'position' => $idx,
            ]);
        }

        $colorValues = [];
        foreach (['Black', 'White'] as $idx => $color) {
            $colorValues[] = ProductOptionValue::query()->create([
                'product_option_id' => $colorOption->id,
                'value' => $color,
                'position' => $idx,
            ]);
        }

        $position = 0;
        foreach ($sizeValues as $sizeIdx => $sizeValue) {
            foreach ($colorValues as $colorIdx => $colorValue) {
                $variant = ProductVariant::query()->create([
                    'product_id' => $tshirt->id,
                    'sku' => 'TSH-'.$sizeValue->value.'-'.strtoupper(substr($colorValue->value, 0, 3)),
                    'price_amount' => 2500,
                    'currency' => 'EUR',
                    'is_default' => $sizeIdx === 0 && $colorIdx === 0,
                    'position' => $position++,
                    'status' => VariantStatus::Active,
                ]);

                $variant->optionValues()->sync([$sizeValue->id, $colorValue->id]);

                InventoryItem::query()->create([
                    'store_id' => $store->id,
                    'variant_id' => $variant->id,
                    'quantity_on_hand' => 50,
                    'quantity_reserved' => 0,
                    'policy' => InventoryPolicy::Deny,
                ]);
            }
        }

        ProductMedia::query()->create([
            'product_id' => $tshirt->id,
            'type' => MediaType::Image,
            'storage_key' => 'products/tshirt-front.jpg',
            'alt_text' => 'Organic Cotton T-Shirt front',
            'width' => 1200,
            'height' => 1200,
            'mime_type' => 'image/jpeg',
            'byte_size' => 180_000,
            'position' => 0,
            'status' => MediaStatus::Ready,
            'created_at' => now(),
        ]);

        $hoodie = Product::query()->create([
            'store_id' => $store->id,
            'title' => 'Classic Pullover Hoodie',
            'handle' => 'classic-pullover-hoodie',
            'status' => ProductStatus::Active,
            'description_html' => '<p>Midweight fleece, relaxed fit.</p>',
            'vendor' => 'Acme Apparel',
            'product_type' => 'Apparel',
            'tags' => ['fall'],
            'published_at' => now(),
        ]);

        $hoodieVariant = ProductVariant::query()->create([
            'product_id' => $hoodie->id,
            'sku' => 'HD-DEFAULT',
            'price_amount' => 6000,
            'currency' => 'EUR',
            'is_default' => true,
            'position' => 0,
            'status' => VariantStatus::Active,
        ]);

        InventoryItem::query()->create([
            'store_id' => $store->id,
            'variant_id' => $hoodieVariant->id,
            'quantity_on_hand' => 30,
            'quantity_reserved' => 0,
            'policy' => InventoryPolicy::Deny,
        ]);

        $featured = Collection::query()->create([
            'store_id' => $store->id,
            'title' => 'Featured',
            'handle' => 'featured',
            'description_html' => '<p>Our picks for the season.</p>',
            'type' => CollectionType::Manual,
            'status' => CollectionStatus::Active,
        ]);

        $featured->products()->attach([
            $tshirt->id => ['position' => 0],
            $hoodie->id => ['position' => 1],
        ]);
    }
}
