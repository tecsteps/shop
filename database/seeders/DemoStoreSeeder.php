<?php

namespace Database\Seeders;

use App\Enums\CartStatus;
use App\Enums\CollectionStatus;
use App\Enums\CollectionType;
use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\InventoryPolicy;
use App\Enums\OrderStatus;
use App\Enums\PageStatus;
use App\Enums\PaymentMethod;
use App\Enums\ProductStatus;
use App\Enums\ShippingRateType;
use App\Enums\StoreStatus;
use App\Enums\StoreUserRole;
use App\Enums\TaxMode;
use App\Enums\ThemeStatus;
use App\Enums\VariantStatus;
use App\Models\Cart;
use App\Models\Collection;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Discount;
use App\Models\InventoryItem;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Organization;
use App\Models\Page;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreSettings;
use App\Models\TaxSettings;
use App\Models\Theme;
use App\Models\ThemeSettings;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoStoreSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::create([
            'name' => 'Demo Commerce Inc.',
            'billing_email' => 'billing@demo.test',
        ]);

        $store = Store::create([
            'organization_id' => $organization->id,
            'name' => 'Demo Store',
            'handle' => 'demo',
            'status' => StoreStatus::Active->value,
            'default_currency' => 'EUR',
            'default_locale' => 'en',
            'timezone' => 'Europe/Berlin',
        ]);

        StoreDomain::create([
            'store_id' => $store->id,
            'hostname' => 'shop.test',
            'type' => 'storefront',
            'is_primary' => true,
            'tls_mode' => 'managed',
            'created_at' => now(),
        ]);

        StoreSettings::create([
            'store_id' => $store->id,
            'settings_json' => ['theme' => 'default'],
        ]);

        $owner = User::create([
            'name' => 'Owner User',
            'email' => 'owner@demo.test',
            'password' => Hash::make('password'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $staff = User::create([
            'name' => 'Staff User',
            'email' => 'staff@demo.test',
            'password' => Hash::make('password'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $store->users()->attach($owner->id, ['role' => StoreUserRole::Owner->value]);
        $store->users()->attach($staff->id, ['role' => StoreUserRole::Staff->value]);

        app()->instance('current_store', $store);

        $theme = Theme::create([
            'store_id' => $store->id,
            'name' => 'Default',
            'version' => '1.0.0',
            'status' => ThemeStatus::Published->value,
            'published_at' => now(),
        ]);
        ThemeSettings::create([
            'theme_id' => $theme->id,
            'settings_json' => ['primary_color' => '#111827'],
        ]);

        TaxSettings::create([
            'store_id' => $store->id,
            'mode' => TaxMode::Manual->value,
            'prices_include_tax' => false,
            'config_json' => ['default_rate' => 0.19],
        ]);

        $zone = ShippingZone::create([
            'store_id' => $store->id,
            'name' => 'Domestic EU',
            'countries_json' => ['DE', 'AT', 'CH', 'FR', 'NL', 'BE', 'IT', 'ES'],
            'regions_json' => [],
        ]);

        ShippingRate::create([
            'zone_id' => $zone->id,
            'name' => 'Standard (3-5 days)',
            'type' => ShippingRateType::Flat->value,
            'config_json' => ['amount' => 499],
            'is_active' => true,
        ]);

        ShippingRate::create([
            'zone_id' => $zone->id,
            'name' => 'Express (1-2 days)',
            'type' => ShippingRateType::Flat->value,
            'config_json' => ['amount' => 1299],
            'is_active' => true,
        ]);

        $worldZone = ShippingZone::create([
            'store_id' => $store->id,
            'name' => 'Worldwide',
            'countries_json' => ['US', 'GB', 'CA', 'AU', 'JP'],
            'regions_json' => [],
        ]);
        ShippingRate::create([
            'zone_id' => $worldZone->id,
            'name' => 'International',
            'type' => ShippingRateType::Flat->value,
            'config_json' => ['amount' => 2499],
            'is_active' => true,
        ]);

        $aboutPage = Page::create([
            'store_id' => $store->id,
            'title' => 'About us',
            'handle' => 'about',
            'body_html' => '<p>Welcome to Demo Store. We love crafting beautiful products.</p>',
            'status' => PageStatus::Published->value,
            'published_at' => now(),
        ]);

        Page::create([
            'store_id' => $store->id,
            'title' => 'Shipping & Returns',
            'handle' => 'shipping',
            'body_html' => '<p>We ship worldwide within 1-5 business days. Free returns within 30 days.</p>',
            'status' => PageStatus::Published->value,
            'published_at' => now(),
        ]);

        $menu = NavigationMenu::create([
            'store_id' => $store->id,
            'handle' => 'main',
            'title' => 'Main menu',
        ]);

        NavigationItem::create([
            'menu_id' => $menu->id, 'type' => 'link', 'label' => 'Home', 'url' => '/', 'position' => 0,
        ]);
        NavigationItem::create([
            'menu_id' => $menu->id, 'type' => 'link', 'label' => 'Shop', 'url' => '/collections', 'position' => 1,
        ]);
        NavigationItem::create([
            'menu_id' => $menu->id, 'type' => 'link', 'label' => 'About', 'url' => '/pages/'.$aboutPage->handle, 'position' => 2,
        ]);

        Discount::create([
            'store_id' => $store->id,
            'type' => DiscountType::Code->value,
            'code' => 'WELCOME10',
            'value_type' => DiscountValueType::Percent->value,
            'value_amount' => 10,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonths(6),
            'usage_limit' => null,
            'usage_count' => 0,
            'rules_json' => [],
            'status' => DiscountStatus::Active->value,
        ]);

        Discount::create([
            'store_id' => $store->id,
            'type' => DiscountType::Code->value,
            'code' => 'FREESHIP',
            'value_type' => DiscountValueType::FreeShipping->value,
            'value_amount' => 0,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonths(6),
            'usage_limit' => null,
            'usage_count' => 0,
            'rules_json' => [],
            'status' => DiscountStatus::Active->value,
        ]);

        $apparelCollection = Collection::create([
            'store_id' => $store->id,
            'title' => 'Apparel',
            'handle' => 'apparel',
            'description_html' => '<p>Quality apparel for everyday adventures.</p>',
            'type' => CollectionType::Manual->value,
            'status' => CollectionStatus::Active->value,
        ]);

        $accessoriesCollection = Collection::create([
            'store_id' => $store->id,
            'title' => 'Accessories',
            'handle' => 'accessories',
            'description_html' => '<p>Finishing touches for your look.</p>',
            'type' => CollectionType::Manual->value,
            'status' => CollectionStatus::Active->value,
        ]);

        $homeCollection = Collection::create([
            'store_id' => $store->id,
            'title' => 'Home & Living',
            'handle' => 'home',
            'description_html' => '<p>Make your space feel like home.</p>',
            'type' => CollectionType::Manual->value,
            'status' => CollectionStatus::Active->value,
        ]);

        $catalog = [
            ['Classic Crew Tee', 'Apparel', 'DemoBrand', 2499, $apparelCollection, [['Size', ['S', 'M', 'L', 'XL']]], 'A soft, breathable cotton tee that goes with everything.'],
            ['Hooded Sweatshirt', 'Apparel', 'DemoBrand', 5999, $apparelCollection, [['Size', ['S', 'M', 'L', 'XL']], ['Color', ['Black', 'Grey', 'Navy']]], 'Premium cotton hoodie with a relaxed fit.'],
            ['Everyday Chino', 'Apparel', 'DemoBrand', 6999, $apparelCollection, [['Size', ['30', '32', '34', '36']]], 'Versatile chinos with stretch for comfort.'],
            ['Minimal Leather Wallet', 'Accessories', 'Workshop Goods', 4999, $accessoriesCollection, [], 'Slim wallet crafted from full-grain leather.'],
            ['Canvas Tote Bag', 'Accessories', 'Workshop Goods', 2999, $accessoriesCollection, [['Color', ['Natural', 'Black']]], 'Durable canvas tote with reinforced straps.'],
            ['Steel Water Bottle', 'Accessories', 'Hydrate Co.', 1999, $accessoriesCollection, [], 'Keeps drinks cold for 24 hours and hot for 12.'],
            ['Ceramic Coffee Mug', 'Home', 'Hearth Studio', 1499, $homeCollection, [['Color', ['White', 'Terracotta']]], 'Handmade ceramic mug, 350ml.'],
            ['Linen Throw Blanket', 'Home', 'Hearth Studio', 7999, $homeCollection, [], 'Stonewashed linen throw for cozy evenings.'],
            ['Scented Candle', 'Home', 'Hearth Studio', 2499, $homeCollection, [['Scent', ['Cedar', 'Vanilla', 'Citrus']]], 'Hand-poured soy candle with a 45 hour burn time.'],
            ['Wool Beanie', 'Accessories', 'DemoBrand', 1999, $accessoriesCollection, [['Color', ['Charcoal', 'Burgundy']]], 'Warm merino wool beanie.'],
        ];

        $products = [];
        foreach ($catalog as [$title, $type, $vendor, $price, $collection, $options, $desc]) {
            $product = Product::create([
                'store_id' => $store->id,
                'title' => $title,
                'handle' => Str::slug($title),
                'status' => ProductStatus::Active->value,
                'description_html' => '<p>'.$desc.'</p>',
                'vendor' => $vendor,
                'product_type' => $type,
                'tags' => json_encode(['new', 'featured']),
                'published_at' => now(),
            ]);

            $products[] = $product;
            $collection->products()->attach($product->id, ['position' => count($products)]);

            if (empty($options)) {
                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => 'SKU-'.strtoupper(Str::random(6)),
                    'price_amount' => $price,
                    'currency' => 'EUR',
                    'requires_shipping' => true,
                    'is_default' => true,
                    'position' => 0,
                    'status' => VariantStatus::Active->value,
                ]);
                InventoryItem::create([
                    'store_id' => $store->id,
                    'variant_id' => $variant->id,
                    'quantity_on_hand' => fake()->numberBetween(25, 200),
                    'quantity_reserved' => 0,
                    'policy' => InventoryPolicy::Deny->value,
                ]);
            } else {
                $optionValues = [];
                foreach ($options as $idx => [$optionName, $values]) {
                    $option = ProductOption::create([
                        'product_id' => $product->id,
                        'name' => $optionName,
                        'position' => $idx,
                    ]);
                    foreach ($values as $vIdx => $value) {
                        $optionValues[$optionName][] = ProductOptionValue::create([
                            'product_option_id' => $option->id,
                            'value' => $value,
                            'position' => $vIdx,
                        ]);
                    }
                }

                $combos = $this->cartesian($optionValues);
                foreach ($combos as $index => $combo) {
                    $variant = ProductVariant::create([
                        'product_id' => $product->id,
                        'sku' => 'SKU-'.strtoupper(Str::random(6)),
                        'price_amount' => $price,
                        'currency' => 'EUR',
                        'requires_shipping' => true,
                        'is_default' => $index === 0,
                        'position' => $index,
                        'status' => VariantStatus::Active->value,
                    ]);
                    foreach ($combo as $optionValue) {
                        $variant->optionValues()->attach($optionValue->id);
                    }
                    InventoryItem::create([
                        'store_id' => $store->id,
                        'variant_id' => $variant->id,
                        'quantity_on_hand' => fake()->numberBetween(10, 80),
                        'quantity_reserved' => 0,
                        'policy' => InventoryPolicy::Deny->value,
                    ]);
                }
            }
        }

        $customers = [];
        for ($i = 1; $i <= 5; $i++) {
            $c = Customer::create([
                'store_id' => $store->id,
                'email' => 'customer'.$i.'@demo.test',
                'password_hash' => Hash::make('password'),
                'name' => fake()->name(),
                'marketing_opt_in' => (bool) random_int(0, 1),
                'email_verified_at' => now(),
            ]);
            CustomerAddress::create([
                'customer_id' => $c->id,
                'label' => 'Home',
                'address_json' => [
                    'first_name' => explode(' ', $c->name)[0] ?? 'Test',
                    'last_name' => explode(' ', $c->name)[1] ?? 'User',
                    'address1' => fake()->streetAddress(),
                    'city' => fake()->city(),
                    'zip' => fake()->postcode(),
                    'country' => 'DE',
                ],
                'is_default' => true,
            ]);
            $customers[] = $c;
        }

        foreach ($customers as $idx => $customer) {
            $variant = $products[$idx % count($products)]->variants()->first();
            if (! $variant) {
                continue;
            }
            $subtotal = $variant->price_amount * 2;
            $shipping = 499;
            $tax = (int) round($subtotal * 0.19);
            $total = $subtotal + $shipping + $tax;

            $order = Order::create([
                'store_id' => $store->id,
                'customer_id' => $customer->id,
                'order_number' => '#'.str_pad((string) (1000 + $idx + 1), 4, '0', STR_PAD_LEFT),
                'payment_method' => PaymentMethod::CreditCard->value,
                'status' => OrderStatus::Open->value,
                'financial_status' => FinancialStatus::Paid->value,
                'fulfillment_status' => FulfillmentStatus::Unfulfilled->value,
                'currency' => 'EUR',
                'subtotal_amount' => $subtotal,
                'discount_amount' => 0,
                'shipping_amount' => $shipping,
                'tax_amount' => $tax,
                'total_amount' => $total,
                'email' => $customer->email,
                'shipping_address_json' => $customer->addresses()->first()?->address_json,
                'billing_address_json' => $customer->addresses()->first()?->address_json,
                'placed_at' => now()->subDays($idx),
            ]);

            OrderLine::create([
                'order_id' => $order->id,
                'product_id' => $variant->product_id,
                'variant_id' => $variant->id,
                'title_snapshot' => $variant->product->title,
                'sku_snapshot' => $variant->sku,
                'quantity' => 2,
                'unit_price_amount' => $variant->price_amount,
                'total_amount' => $subtotal,
            ]);

            Payment::create([
                'order_id' => $order->id,
                'provider' => 'mock',
                'method' => PaymentMethod::CreditCard->value,
                'provider_payment_id' => 'seed_'.Str::random(10),
                'status' => 'captured',
                'amount' => $total,
                'currency' => 'EUR',
                'raw_json_encrypted' => ['source' => 'seed'],
                'created_at' => now()->subDays($idx),
            ]);
        }

        Cart::create([
            'store_id' => $store->id,
            'customer_id' => null,
            'currency' => 'EUR',
            'cart_version' => 1,
            'status' => CartStatus::Active->value,
        ]);
    }

    private function cartesian(array $optionValues): array
    {
        $keys = array_keys($optionValues);
        if (empty($keys)) {
            return [[]];
        }

        $result = [[]];
        foreach ($keys as $key) {
            $append = [];
            foreach ($result as $product) {
                foreach ($optionValues[$key] as $value) {
                    $append[] = array_merge($product, [$key => $value]);
                }
            }
            $result = $append;
        }

        return $result;
    }
}
