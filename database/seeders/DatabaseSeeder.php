<?php

namespace Database\Seeders;

use App\Models\AnalyticsEvent;
use App\Models\Collection;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Discount;
use App\Models\InventoryItem;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Order;
use App\Models\Organization;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductMedia;
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
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->updateOrCreate(
            ['billing_email' => 'billing@acme.test'],
            ['name' => 'Acme Commerce Group'],
        );

        $fashion = Store::query()->updateOrCreate(
            ['handle' => 'acme-fashion'],
            [
                'organization_id' => $organization->id,
                'name' => 'Acme Fashion',
                'status' => 'active',
                'default_currency' => 'EUR',
                'default_locale' => 'en',
                'timezone' => 'Europe/Berlin',
            ],
        );

        $electronics = Store::query()->updateOrCreate(
            ['handle' => 'acme-electronics'],
            [
                'organization_id' => $organization->id,
                'name' => 'Acme Electronics',
                'status' => 'active',
                'default_currency' => 'EUR',
                'default_locale' => 'en',
                'timezone' => 'Europe/Berlin',
            ],
        );

        foreach ([['shop.test', $fashion], ['acme-fashion.test', $fashion], ['admin.acme-fashion.test', $fashion], ['acme-electronics.test', $electronics]] as [$host, $store]) {
            StoreDomain::query()->updateOrCreate(
                ['hostname' => $host],
                ['store_id' => $store->id, 'type' => str_starts_with($host, 'admin.') ? 'admin' : 'storefront', 'is_primary' => $host === 'acme-fashion.test'],
            );
        }

        foreach ([$fashion, $electronics] as $store) {
            StoreSettings::query()->updateOrCreate(
                ['store_id' => $store->id],
                ['settings_json' => [
                    'contact_email' => 'hello@'.$store->handle.'.test',
                    'announcement' => 'Free shipping over 75 EUR',
                    'hero_heading' => $store->name,
                    'hero_subheading' => 'Independent commerce, ready to buy.',
                ]],
            );

            TaxSettings::query()->updateOrCreate(
                ['store_id' => $store->id],
                ['prices_include_tax' => false, 'default_rate_bps' => 1900],
            );
        }

        $admin = $this->user('admin@acme.test', 'Admin User');
        $staff = $this->user('staff@acme.test', 'Staff User');
        $support = $this->user('support@acme.test', 'Support User');
        $manager = $this->user('manager@acme.test', 'Store Manager');
        $adminTwo = $this->user('admin2@acme.test', 'Admin Two');

        $fashion->users()->syncWithoutDetaching([
            $admin->id => ['role' => 'owner'],
            $staff->id => ['role' => 'staff'],
            $support->id => ['role' => 'support'],
            $manager->id => ['role' => 'admin'],
        ]);
        $electronics->users()->syncWithoutDetaching([$adminTwo->id => ['role' => 'owner']]);

        $this->shipping($fashion);
        $this->shipping($electronics);
        $this->discounts($fashion);

        $collections = $this->collections($fashion);
        $products = $this->fashionProducts($fashion, $collections);
        $this->electronicsProducts($electronics);

        $customer = $this->customer($fashion, 'customer@acme.test', 'John Doe');
        $this->customer($fashion, 'jane@example.com', 'Jane Smith');
        $this->customer($electronics, 'techfan@example.com', 'Tech Fan');

        $this->content($fashion);
        $this->content($electronics);
        $this->orders($fashion, $customer, $products);
        $this->analytics($fashion);
    }

    private function user(string $email, string $name): User
    {
        return User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password_hash' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
                'last_login_at' => now(),
            ],
        );
    }

    private function customer(Store $store, string $email, string $name): Customer
    {
        $customer = Customer::withoutGlobalScopes()->updateOrCreate(
            ['store_id' => $store->id, 'email' => $email],
            [
                'name' => $name,
                'password_hash' => Hash::make('password'),
                'accepts_marketing' => true,
            ],
        );

        CustomerAddress::withoutGlobalScopes()->updateOrCreate(
            ['store_id' => $store->id, 'customer_id' => $customer->id, 'address1' => 'Main Street 1'],
            [
                'name' => $name,
                'city' => 'Berlin',
                'postal_code' => '10115',
                'country_code' => 'DE',
                'is_default' => true,
            ],
        );

        return $customer;
    }

    private function shipping(Store $store): void
    {
        $germany = ShippingZone::withoutGlobalScopes()->updateOrCreate(
            ['store_id' => $store->id, 'name' => 'Germany'],
            ['countries' => ['DE']],
        );
        ShippingRate::query()->updateOrCreate(
            ['shipping_zone_id' => $germany->id, 'name' => 'Standard Shipping'],
            ['price_amount' => 499],
        );
        ShippingRate::query()->updateOrCreate(
            ['shipping_zone_id' => $germany->id, 'name' => 'Free Shipping'],
            ['price_amount' => 0, 'min_order_amount' => 7500],
        );

        $international = ShippingZone::withoutGlobalScopes()->updateOrCreate(
            ['store_id' => $store->id, 'name' => 'International'],
            ['countries' => ['*']],
        );
        ShippingRate::query()->updateOrCreate(
            ['shipping_zone_id' => $international->id, 'name' => 'International Shipping'],
            ['price_amount' => 1499],
        );
    }

    private function discounts(Store $store): void
    {
        $rows = [
            ['WELCOME10', 'percentage', 0, 1000, null, null, 0, true, now()->subDay(), now()->addYear()],
            ['FLAT5', 'fixed_amount', 500, 0, null, null, 0, true, now()->subDay(), now()->addYear()],
            ['FREESHIP', 'free_shipping', 0, 0, null, null, 0, true, now()->subDay(), now()->addYear()],
            ['EXPIRED', 'percentage', 0, 1500, null, null, 0, true, now()->subMonth(), now()->subDay()],
            ['MAXED', 'percentage', 0, 1500, null, 1, 1, true, now()->subDay(), now()->addYear()],
        ];

        foreach ($rows as [$code, $type, $amount, $bps, $minimum, $limit, $used, $active, $start, $end]) {
            Discount::withoutGlobalScopes()->updateOrCreate(
                ['store_id' => $store->id, 'code' => $code],
                [
                    'type' => $type,
                    'value_amount' => $amount,
                    'value_bps' => $bps,
                    'min_purchase_amount' => $minimum,
                    'usage_limit' => $limit,
                    'used_count' => $used,
                    'is_active' => $active,
                    'starts_at' => $start,
                    'ends_at' => $end,
                ],
            );
        }
    }

    /**
     * @return array<string, Collection>
     */
    private function collections(Store $store): array
    {
        $collections = [];

        foreach ([['t-shirts', 'T-Shirts'], ['new-arrivals', 'New Arrivals'], ['sale', 'Sale']] as [$handle, $title]) {
            $collections[$handle] = Collection::withoutGlobalScopes()->updateOrCreate(
                ['store_id' => $store->id, 'handle' => $handle],
                ['title' => $title, 'description_html' => '<p>'.$title.' from Acme Fashion.</p>', 'is_published' => true],
            );
        }

        return $collections;
    }

    /**
     * @param array<string, Collection> $collections
     * @return array<int, Product>
     */
    private function fashionProducts(Store $store, array $collections): array
    {
        $rows = [
            ['classic-cotton-t-shirt', 'Classic Cotton T-Shirt', 2499, 3499, 25, 'deny', 'T-Shirts', ['t-shirts', 'new-arrivals']],
            ['linen-summer-shirt', 'Linen Summer Shirt', 4999, null, 12, 'deny', 'Shirts', ['new-arrivals']],
            ['denim-jacket', 'Denim Jacket', 8999, null, 8, 'deny', 'Outerwear', ['new-arrivals']],
            ['black-skinny-jeans', 'Black Skinny Jeans', 6999, null, 9, 'deny', 'Pants', ['sale']],
            ['wool-beanie', 'Wool Beanie', 1999, null, 0, 'continue', 'Accessories', ['sale']],
            ['sold-out-sneakers', 'Sold Out Sneakers', 7999, null, 0, 'deny', 'Shoes', ['new-arrivals']],
            ['draft-rain-coat', 'Draft Rain Coat', 12999, null, 6, 'deny', 'Outerwear', []],
        ];

        $products = [];

        for ($i = count($rows) + 1; $i <= 20; $i++) {
            $rows[] = ["essential-item-{$i}", "Essential Item {$i}", 1500 + ($i * 250), null, 10 + $i, 'deny', 'Essentials', ['new-arrivals']];
        }

        foreach ($rows as [$handle, $title, $price, $compare, $quantity, $policy, $type, $collectionHandles]) {
            $status = str_starts_with($handle, 'draft-') ? 'draft' : 'active';
            $product = Product::withoutGlobalScopes()->updateOrCreate(
                ['store_id' => $store->id, 'handle' => $handle],
                [
                    'title' => $title,
                    'status' => $status,
                    'description_html' => '<p>'.$title.' designed for everyday commerce testing.</p>',
                    'vendor' => 'Acme',
                    'product_type' => $type,
                    'tags' => [$type, 'acme'],
                    'published_at' => $status === 'active' ? now() : null,
                ],
            );

            $variant = $this->variant($store, $product, $price, $compare, $quantity, $policy);

            ProductMedia::query()->updateOrCreate(
                ['product_id' => $product->id, 'position' => 0],
                ['url' => 'https://placehold.co/900x1100/e5e7eb/111827?text='.urlencode($title), 'alt_text' => $title],
            );

            foreach ($collectionHandles as $collectionHandle) {
                $collections[$collectionHandle]->products()->syncWithoutDetaching([$product->id => ['position' => $product->id]]);
            }

            $products[$product->id] = $product->setRelation('defaultVariant', $variant);
        }

        return $products;
    }

    private function variant(Store $store, Product $product, int $price, ?int $compare, int $quantity, string $policy): ProductVariant
    {
        $size = ProductOption::query()->firstOrCreate(['product_id' => $product->id, 'position' => 1], ['name' => 'Size']);
        $color = ProductOption::query()->firstOrCreate(['product_id' => $product->id, 'position' => 2], ['name' => 'Color']);
        $medium = ProductOptionValue::query()->firstOrCreate(['product_option_id' => $size->id, 'position' => 1], ['value' => 'Medium']);
        $black = ProductOptionValue::query()->firstOrCreate(['product_option_id' => $color->id, 'position' => 1], ['value' => 'Black']);

        $variant = ProductVariant::query()->updateOrCreate(
            ['product_id' => $product->id, 'position' => 0],
            [
                'sku' => strtoupper(str_replace('-', '-', $product->handle)).'-M-BLK',
                'price_amount' => $price,
                'compare_at_amount' => $compare,
                'currency' => $store->default_currency,
                'weight_g' => 400,
                'requires_shipping' => true,
                'is_default' => true,
                'status' => 'active',
            ],
        );
        $variant->optionValues()->syncWithoutDetaching([$medium->id, $black->id]);

        InventoryItem::withoutGlobalScopes()->updateOrCreate(
            ['store_id' => $store->id, 'variant_id' => $variant->id],
            ['quantity_available' => $quantity, 'quantity_reserved' => 0, 'policy' => $policy],
        );

        return $variant;
    }

    private function electronicsProducts(Store $store): void
    {
        foreach ([['wireless-headphones', 'Wireless Headphones', 12999], ['usb-c-hub', 'USB-C Hub', 3999], ['desk-lamp', 'Desk Lamp', 5999], ['portable-speaker', 'Portable Speaker', 8999], ['keyboard', 'Mechanical Keyboard', 11999]] as [$handle, $title, $price]) {
            $product = Product::withoutGlobalScopes()->updateOrCreate(
                ['store_id' => $store->id, 'handle' => $handle],
                ['title' => $title, 'status' => 'active', 'description_html' => '<p>'.$title.'</p>', 'vendor' => 'Acme Electronics', 'product_type' => 'Electronics', 'tags' => ['electronics'], 'published_at' => now()],
            );
            $this->variant($store, $product, $price, null, 15, 'deny');
        }
    }

    private function content(Store $store): void
    {
        Theme::withoutGlobalScopes()->updateOrCreate(
            ['store_id' => $store->id, 'name' => 'Default'],
            ['is_active' => true, 'settings_json' => ['sticky_header' => true]],
        );

        Page::withoutGlobalScopes()->updateOrCreate(
            ['store_id' => $store->id, 'handle' => 'about'],
            ['title' => 'About', 'body_html' => '<h2>About '.$store->name.'</h2><p>We run a complete demo commerce operation.</p>', 'is_published' => true],
        );

        $menu = NavigationMenu::withoutGlobalScopes()->updateOrCreate(
            ['store_id' => $store->id, 'handle' => 'main-menu'],
            ['title' => 'Main menu'],
        );

        foreach ([['Home', '/'], ['Collections', '/collections'], ['T-Shirts', '/collections/t-shirts'], ['Search', '/search'], ['About', '/pages/about']] as $position => [$label, $url]) {
            NavigationItem::query()->updateOrCreate(
                ['navigation_menu_id' => $menu->id, 'label' => $label],
                ['url' => $url, 'position' => $position],
            );
        }
    }

    /**
     * @param array<int, Product> $products
     */
    private function orders(Store $store, Customer $customer, array $products): void
    {
        $first = collect($products)->firstWhere('handle', 'classic-cotton-t-shirt') ?? collect($products)->first();

        foreach ([1001 => 'paid', 1002 => 'paid', 1004 => 'paid', 1005 => 'pending'] as $number => $financialStatus) {
            $order = Order::withoutGlobalScopes()->updateOrCreate(
                ['store_id' => $store->id, 'order_number' => (string) $number],
                [
                    'customer_id' => $customer->id,
                    'email' => $customer->email,
                    'status' => $financialStatus,
                    'financial_status' => $financialStatus,
                    'fulfillment_status' => 'unfulfilled',
                    'currency' => 'EUR',
                    'subtotal_amount' => 2499,
                    'discount_amount' => 0,
                    'shipping_amount' => 499,
                    'tax_amount' => 570,
                    'total_amount' => 3568,
                    'shipping_address_json' => ['name' => 'John Doe', 'address1' => 'Main Street 1', 'city' => 'Berlin', 'postal_code' => '10115', 'country_code' => 'DE'],
                    'timeline_json' => [
                        ['at' => now()->subDays(3)->toISOString(), 'message' => 'Order created'],
                        ['at' => now()->subDays(3)->toISOString(), 'message' => $financialStatus === 'paid' ? 'Payment captured' : 'Awaiting bank transfer'],
                    ],
                ],
            );

            $variant = $first->defaultVariant()->first();
            $order->lines()->updateOrCreate(
                ['product_variant_id' => $variant->id, 'title' => $first->title],
                ['sku' => $variant->sku, 'quantity' => 1, 'unit_price_amount' => 2499, 'total_amount' => 2499, 'snapshot_json' => ['product_title' => $first->title]],
            );

            $order->payments()->updateOrCreate(
                ['provider' => 'mock', 'method' => $financialStatus === 'pending' ? 'bank_transfer' : 'credit_card'],
                ['store_id' => $store->id, 'status' => $financialStatus, 'amount' => 3568, 'reference' => 'seed-'.$number, 'raw_payload_encrypted' => encrypt(['seed' => true])],
            );
        }
    }

    private function analytics(Store $store): void
    {
        foreach (['visit', 'add_to_cart', 'checkout_started', 'checkout_completed'] as $event) {
            AnalyticsEvent::withoutGlobalScopes()->create([
                'store_id' => $store->id,
                'event' => $event,
                'payload_json' => ['seed' => true],
            ]);
        }
    }
}

