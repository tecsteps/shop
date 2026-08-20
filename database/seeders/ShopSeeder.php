<?php

namespace Database\Seeders;

use App\Enums\DiscountValueType;
use App\Enums\InventoryPolicy;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\StoreDomainType;
use App\Enums\StoreUserRole;
use App\Models\Collection;
use App\Models\Discount;
use App\Models\InventoryItem;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Order;
use App\Models\Organization;
use App\Models\Page;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreSettings;
use App\Models\TaxSettings;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Database\Seeder;

class ShopSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::firstOrCreate(['slug' => 'acme-commerce'], ['name' => 'Acme Commerce', 'billing_email' => 'billing@acme.test', 'status' => 'active']);
        $store = Store::firstOrCreate(['handle' => 'acme-fashion'], ['organization_id' => $organization->getKey(), 'name' => 'Acme Fashion', 'default_currency' => 'EUR', 'default_locale' => 'en', 'timezone' => 'Europe/Berlin', 'status' => 'active']);
        StoreDomain::firstOrCreate(['hostname' => 'acme-fashion.test'], ['store_id' => $store->getKey(), 'type' => StoreDomainType::Storefront, 'is_primary' => true, 'tls_mode' => 'managed']);
        StoreDomain::firstOrCreate(['hostname' => 'shop.test'], ['store_id' => $store->getKey(), 'type' => StoreDomainType::Storefront, 'is_primary' => false, 'tls_mode' => 'managed']);
        StoreDomain::firstOrCreate(['hostname' => 'admin.acme-fashion.test'], ['store_id' => $store->getKey(), 'type' => StoreDomainType::Admin, 'is_primary' => true, 'tls_mode' => 'managed']);
        StoreSettings::updateOrCreate(['store_id' => $store->getKey()], ['settings_json' => ['announcement' => 'Free shipping on orders over €50', 'hero_heading' => 'Everyday pieces, thoughtfully made.'], 'general_json' => ['store_name' => 'Acme Fashion']]);
        $domestic = ShippingZone::updateOrCreate(['store_id' => $store->getKey(), 'name' => 'Domestic'], ['countries_json' => ['DE'], 'regions_json' => []]);
        ShippingRate::updateOrCreate(['shipping_zone_id' => $domestic->getKey(), 'name' => 'Standard Shipping'], ['type' => 'flat', 'price_amount' => 499, 'currency' => 'EUR', 'is_active' => true, 'estimated_days_min' => 3, 'estimated_days_max' => 5]);
        TaxSettings::updateOrCreate(['store_id' => $store->getKey()], ['mode' => 'exclusive', 'default_rate_basis_points' => 1900, 'rates_json' => ['DE' => 1900]]);
        Theme::firstOrCreate(['store_id' => $store->getKey(), 'name' => 'Acme Default'], ['status' => 'published', 'settings' => ['hero_heading' => 'Everyday pieces, thoughtfully made.']]);
        Page::updateOrCreate(['store_id' => $store->getKey(), 'handle' => 'about'], ['title' => 'About', 'content' => '<p>Acme Fashion makes thoughtful everyday pieces for modern wardrobes.</p>', 'status' => 'published', 'published_at' => now()]);
        $mainMenu = NavigationMenu::updateOrCreate(['store_id' => $store->getKey(), 'handle' => 'main'], ['name' => 'Main menu']);
        $mainMenu->items()->delete();
        NavigationItem::create(['navigation_menu_id' => $mainMenu->getKey(), 'label' => 'Collections', 'type' => 'link', 'url' => '/collections', 'position' => 1]);
        NavigationItem::create(['navigation_menu_id' => $mainMenu->getKey(), 'label' => 'About', 'type' => 'link', 'url' => '/pages/about', 'position' => 2]);

        $admin = User::firstOrCreate(['email' => 'admin@acme.test'], ['name' => 'Acme Admin', 'password' => 'password', 'email_verified_at' => now(), 'status' => 'active']);
        $store->users()->syncWithoutDetaching([$admin->getKey() => ['role' => StoreUserRole::Owner->value]]);
        $customer = \App\Models\Customer::firstOrCreate(['store_id' => $store->getKey(), 'email' => 'customer@acme.test'], ['first_name' => 'Jamie', 'last_name' => 'Customer', 'password_hash' => 'password', 'email_verified_at' => now(), 'status' => 'active']);

        $tShirts = Collection::firstOrCreate(['store_id' => $store->getKey(), 'handle' => 't-shirts'], ['title' => 'T-Shirts', 'description' => 'Soft, everyday essentials.', 'status' => 'active']);
        $newArrivals = Collection::firstOrCreate(['store_id' => $store->getKey(), 'handle' => 'new-arrivals'], ['title' => 'New Arrivals', 'description' => 'Fresh pieces for the season.', 'status' => 'active']);
        $classic = $this->product($store, 'Classic Cotton T-Shirt', 'classic-cotton-t-shirt', 2499, 80, InventoryPolicy::Deny, ['S', 'M', 'L', 'XL'], ['Black', 'White', 'Navy']);
        $jeans = $this->product($store, 'Premium Slim Fit Jeans', 'premium-slim-fit-jeans', 7999, 35, InventoryPolicy::Deny, ['28', '30', '32', '34'], ['Indigo']);
        $draft = $this->product($store, 'Coming Soon Jacket', 'coming-soon-jacket', 12999, 0, InventoryPolicy::Deny, ['M'], ['Black'], ProductStatus::Draft);
        $soldOut = $this->product($store, 'Sold Out Limited Tee', 'sold-out-limited-tee', 3999, 0, InventoryPolicy::Deny, ['M'], ['White']);
        $backorder = $this->product($store, 'Relaxed Backorder Hoodie', 'relaxed-backorder-hoodie', 6999, 0, InventoryPolicy::Continue, ['M', 'L'], ['Navy']);
        $tShirts->products()->syncWithoutDetaching([$classic->getKey() => ['position' => 1], $soldOut->getKey() => ['position' => 2]]);
        $newArrivals->products()->syncWithoutDetaching([$classic->getKey() => ['position' => 1], $jeans->getKey() => ['position' => 2], $backorder->getKey() => ['position' => 3]]);

        foreach ([
            ['code' => 'WELCOME10', 'value_type' => DiscountValueType::Percent, 'value_amount' => 10],
            ['code' => 'FLAT5', 'value_type' => DiscountValueType::Fixed, 'value_amount' => 500],
            ['code' => 'FREESHIP', 'value_type' => DiscountValueType::FreeShipping, 'value_amount' => 0],
            ['code' => 'EXPIRED20', 'value_type' => DiscountValueType::Percent, 'value_amount' => 20, 'ends_at' => now()->subDay()],
            ['code' => 'MAXED', 'value_type' => DiscountValueType::Percent, 'value_amount' => 15, 'usage_limit' => 1, 'usage_count' => 1],
        ] as $discount) {
            Discount::updateOrCreate(['store_id' => $store->getKey(), 'code' => $discount['code']], array_merge(['type' => 'code', 'status' => 'active', 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth(), 'usage_limit' => null, 'usage_count' => 0, 'rules_json' => []], $discount));
        }

        $order = Order::withoutGlobalScopes()->firstOrCreate(['store_id' => $store->getKey(), 'order_number' => '#1001'], ['customer_id' => $customer->getKey(), 'currency' => 'EUR', 'status' => 'paid', 'financial_status' => 'paid', 'fulfillment_status' => 'unfulfilled', 'payment_method' => PaymentMethod::CreditCard, 'email' => $customer->email, 'subtotal_amount' => 2499, 'shipping_amount' => 499, 'tax_amount' => 0, 'total_amount' => 2998, 'placed_at' => now()->subDay()]);
        if ($order->lines()->count() === 0) {
            $variant = $classic->variants()->first();
            $order->lines()->create(['product_id' => $classic->getKey(), 'variant_id' => $variant->getKey(), 'product_title' => $classic->title, 'variant_title' => $variant->title, 'sku' => $variant->sku, 'quantity' => 1, 'unit_price_amount' => $variant->price_amount, 'line_subtotal_amount' => $variant->price_amount, 'line_total_amount' => $variant->price_amount]);
            $order->payments()->create(['provider' => 'mock', 'provider_payment_id' => 'mock_seed_1001', 'method' => PaymentMethod::CreditCard, 'status' => PaymentStatus::Captured, 'amount' => $order->total_amount]);
        }
    }

    private function product(Store $store, string $title, string $handle, int $price, int $quantity, InventoryPolicy $policy, array $sizes, array $colors, ProductStatus $status = ProductStatus::Active): Product
    {
        $product = Product::withoutGlobalScopes()->firstOrCreate(['store_id' => $store->getKey(), 'handle' => $handle], ['title' => $title, 'description' => 'Designed for comfortable everyday wear.', 'vendor' => 'Acme', 'product_type' => 'Apparel', 'tags' => ['featured'], 'status' => $status, 'published_at' => $status === ProductStatus::Active ? now() : null]);

        if ($product->variants()->count() === 0) {
            foreach ($sizes as $sizeIndex => $size) {
                foreach ($colors as $colorIndex => $color) {
                    $variant = $product->variants()->create(['title' => $color.' / '.$size, 'sku' => strtoupper('ACME-'.substr($handle, 0, 5).'-'.$size.'-'.$colorIndex), 'price_amount' => $price, 'compare_at_amount' => $price + 500, 'weight_grams' => 250, 'requires_shipping' => true, 'is_default' => $sizeIndex === 0 && $colorIndex === 0, 'position' => ($sizeIndex * count($colors)) + $colorIndex]);
                    InventoryItem::withoutGlobalScopes()->create(['store_id' => $store->getKey(), 'variant_id' => $variant->getKey(), 'quantity_on_hand' => $quantity, 'quantity_reserved' => 0, 'policy' => $policy]);
                }
            }
        }

        if ($product->options()->count() === 0) {
            $sizeOption = $product->options()->create(['name' => 'Size', 'position' => 1]);
            $colorOption = $product->options()->create(['name' => 'Color', 'position' => 2]);

            foreach ($sizes as $position => $size) {
                $sizeOption->values()->create(['value' => $size, 'position' => $position + 1]);
            }

            foreach ($colors as $position => $color) {
                $colorOption->values()->create(['value' => $color, 'position' => $position + 1]);
            }
        }

        $optionValues = $product->options()->with('values')->get()->flatMap(fn ($option) => $option->values)->keyBy('value');
        foreach ($product->variants as $variant) {
            [$color, $size] = array_pad(array_map('trim', explode('/', $variant->title, 2)), 2, null);
            $variant->optionValues()->syncWithoutDetaching(array_values(array_filter([
                $optionValues->get($color)?->getKey(),
                $optionValues->get($size)?->getKey(),
            ])));
        }

        return $product->load('variants.inventory');
    }
}
