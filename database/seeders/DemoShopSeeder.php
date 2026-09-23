<?php

namespace Database\Seeders;

use App\Models\AnalyticsEvent;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Organization;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreSettings;
use App\Models\TaxSetting;
use App\Models\Theme;
use App\Services\ProductService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoShopSeeder extends Seeder
{
    /** @var array<string, Product> */
    private array $products = [];

    /** @var array<string, Customer> */
    private array $customers = [];

    public function run(): void
    {
        $organization = Organization::query()->updateOrCreate(
            ['billing_email' => 'billing@acme.test'],
            ['name' => 'Acme Corp'],
        );
        $fashion = Store::query()->updateOrCreate(['handle' => 'acme-fashion'], [
            'organization_id' => $organization->id,
            'name' => 'Acme Fashion',
            'status' => 'active',
            'default_currency' => 'EUR',
            'default_locale' => 'en',
            'timezone' => 'Europe/Berlin',
        ]);
        $electronics = Store::query()->updateOrCreate(['handle' => 'acme-electronics'], [
            'organization_id' => $organization->id,
            'name' => 'Acme Electronics',
            'status' => 'active',
            'default_currency' => 'EUR',
            'default_locale' => 'en',
            'timezone' => 'Europe/Berlin',
        ]);

        $this->seedDomains($fashion, $electronics);
        $this->seedUsers($fashion, $electronics);
        $this->seedSettings($fashion, $electronics);
        $this->seedCatalog($fashion, $electronics);
        $this->seedDiscounts($fashion);
        $this->seedCustomers($fashion, $electronics);
        $this->seedContent($fashion, $electronics);
        $this->seedOrders($fashion, $electronics);
        $this->seedAnalytics($fashion, $electronics);
    }

    private function seedDomains(Store $fashion, Store $electronics): void
    {
        foreach ([
            [$fashion, 'acme-fashion.test', 'storefront', true],
            [$fashion, 'shop.test', 'storefront', false],
            [$fashion, 'admin.acme-fashion.test', 'admin', false],
            [$electronics, 'acme-electronics.test', 'storefront', true],
        ] as [$store, $hostname, $type, $primary]) {
            StoreDomain::query()->updateOrCreate(['hostname' => $hostname], [
                'store_id' => $store->id,
                'type' => $type,
                'is_primary' => $primary,
                'tls_mode' => 'managed',
                'created_at' => now(),
            ]);
        }
    }

    private function seedUsers(Store $fashion, Store $electronics): void
    {
        foreach ([
            ['admin@acme.test', 'Admin User', $fashion, 'owner', now()],
            ['staff@acme.test', 'Staff User', $fashion, 'staff', now()->subDays(2)],
            ['support@acme.test', 'Support User', $fashion, 'support', now()->subDay()],
            ['manager@acme.test', 'Store Manager', $fashion, 'admin', now()->subDay()],
            ['admin2@acme.test', 'Admin Two', $electronics, 'owner', now()->subDay()],
        ] as [$email, $name, $store, $role, $lastLogin]) {
            $user = \App\Models\User::query()->updateOrCreate(['email' => $email], [
                'name' => $name,
                'password' => 'password',
                'status' => 'active',
                'last_login_at' => $lastLogin,
            ]);
            $store->users()->syncWithoutDetaching([$user->id => ['role' => $role, 'created_at' => now()]]);
        }
    }

    private function seedSettings(Store $fashion, Store $electronics): void
    {
        foreach ([[$fashion, 1001], [$electronics, 5001]] as [$store, $orderStart]) {
            StoreSettings::query()->updateOrCreate(['store_id' => $store->id], ['settings_json' => [
                'store_name' => $store->name,
                'contact_email' => $store->handle === 'acme-fashion' ? 'hello@acme-fashion.test' : 'hello@acme-electronics.test',
                'order_number_prefix' => '#',
                'order_number_start' => $orderStart,
            ]]);
            TaxSetting::query()->updateOrCreate(['store_id' => $store->id], [
                'prices_include_tax' => true,
                'default_rate' => 1900,
                'rates_json' => ['DE' => 1900, 'AT' => 2000, 'FR' => 2000, 'US' => 0],
            ]);
        }

        $this->seedShipping($fashion, [
            ['Domestic', ['DE'], [['Standard Shipping', 499], ['Express Shipping', 999]]],
            ['EU', ['AT', 'FR', 'IT', 'ES', 'NL', 'BE', 'PL'], [['EU Standard', 899]]],
            ['Rest of World', ['US', 'GB', 'CA', 'AU'], [['International', 1499]]],
        ]);
        $this->seedShipping($electronics, [['Germany', ['DE'], [['Standard', 0]]]]);
    }

    /** @param list<array{0: string, 1: list<string>, 2: list<array{0: string, 1: int}>}> $zones */
    private function seedShipping(Store $store, array $zones): void
    {
        foreach ($zones as [$name, $countries, $rates]) {
            $zone = ShippingZone::query()->updateOrCreate(
                ['store_id' => $store->id, 'name' => $name],
                ['countries' => $countries, 'is_active' => true],
            );

            foreach ($rates as [$rateName, $amount]) {
                $zone->rates()->updateOrCreate(['name' => $rateName], [
                    'type' => 'flat',
                    'price_amount' => $amount,
                    'config_json' => ['amount' => $amount],
                    'is_active' => true,
                ]);
            }
        }
    }

    private function seedCatalog(Store $fashion, Store $electronics): void
    {
        $collections = [];
        foreach ([
            [$fashion, 'New Arrivals', 'new-arrivals', 'Discover the latest additions to our store.'],
            [$fashion, 'T-Shirts', 't-shirts', 'Premium cotton tees for every occasion.'],
            [$fashion, 'Pants & Jeans', 'pants-jeans', 'Find the perfect fit from our denim and trouser range.'],
            [$fashion, 'Sale', 'sale', 'Great deals on selected items.'],
            [$electronics, 'Featured', 'featured', 'Professional technology we recommend.'],
            [$electronics, 'Accessories', 'accessories', 'Accessories for your everyday setup.'],
        ] as [$store, $title, $handle, $description]) {
            $collections[$store->handle.':'.$handle] = \App\Models\Collection::query()->updateOrCreate(
                ['store_id' => $store->id, 'handle' => $handle],
                ['title' => $title, 'description_html' => '<p>'.$description.'</p>', 'type' => 'manual', 'status' => 'active'],
            );
        }

        $fashionRows = $this->fashionProducts();
        $fashionAssignments = [
            'new-arrivals' => ['classic-cotton-t-shirt', 'premium-slim-fit-jeans', 'organic-hoodie', 'running-sneakers', 'chino-shorts', 'bucket-hat', 'cashmere-overcoat'],
            't-shirts' => ['classic-cotton-t-shirt', 'graphic-print-tee', 'v-neck-linen-tee', 'striped-polo-shirt'],
            'pants-jeans' => ['premium-slim-fit-jeans', 'cargo-pants', 'chino-shorts', 'wide-leg-trousers'],
            'sale' => ['premium-slim-fit-jeans', 'striped-polo-shirt', 'wide-leg-trousers'],
        ];

        foreach ($fashionRows as $row) {
            $this->products[$row['handle']] = $this->createProduct($fashion, $row);
        }

        foreach ($this->electronicsProducts() as $row) {
            $this->products[$row['handle']] = $this->createProduct($electronics, $row);
        }

        foreach ($fashionAssignments as $handle => $productHandles) {
            $collection = $collections['acme-fashion:'.$handle];
            $collection->products()->sync(collect($productHandles)->mapWithKeys(fn (string $productHandle, int $position): array => [$this->products[$productHandle]->id => ['position' => $position]])->all());
        }

        foreach ([
            ['featured', ['pro-laptop-15', 'wireless-headphones', 'mechanical-keyboard']],
            ['accessories', ['usb-c-cable-2m', 'monitor-stand']],
        ] as [$handle, $productHandles]) {
            $collections['acme-electronics:'.$handle]->products()->sync(collect($productHandles)->mapWithKeys(fn (string $productHandle, int $position): array => [$this->products[$productHandle]->id => ['position' => $position]])->all());
        }
    }

    /** @param array<string, mixed> $row */
    private function createProduct(Store $store, array $row): Product
    {
        $optionSets = $row['options'];
        $combinations = $this->combinations(array_column($optionSets, 'values'));
        $variants = [];

        foreach ($combinations as $position => $combination) {
            $skuParts = array_map(static fn (string $value): string => strtoupper(Str::slug($value, '-')), $combination);
            $sku = $row['sku_prefix'] ?? strtoupper(Str::slug($row['handle'], '-'));
            if ($skuParts !== []) {
                $sku .= '-'.implode('-', $skuParts);
            }
            if ($row['handle'] === 'classic-cotton-t-shirt') {
                $sku = 'ACME-CTSH-'.strtoupper($combination[0]).'-'.match ($combination[1]) {
                    'White' => 'WHT', 'Black' => 'BLK', 'Navy' => 'NVY', default => strtoupper($combination[1]),
                };
            }

            $variants[] = [
                'sku' => $sku,
                'price_amount' => $row['prices'][$position] ?? $row['price'],
                'compare_at_amount' => $row['compare'] ?? null,
                'currency' => 'EUR',
                'weight_g' => $row['weight'],
                'requires_shipping' => $row['requires_shipping'] ?? true,
                'is_default' => $position === 0,
                'quantity_on_hand' => $row['inventory'],
                'inventory_policy' => $row['policy'] ?? 'deny',
            ];
        }

        $product = app(ProductService::class)->create($store, [
            'title' => $row['title'],
            'handle' => $row['handle'],
            'status' => $row['status'] ?? 'active',
            'description_html' => '<p>'.$row['description'].'</p>',
            'vendor' => $row['vendor'],
            'product_type' => $row['type'],
            'tags' => $row['tags'],
            'options' => $optionSets,
            'variants' => $variants,
        ]);

        $optionValueIds = $product->options->map(static fn ($option) => $option->values->pluck('id')->all())->all();
        foreach ($product->variants as $position => $variant) {
            $variant->optionValues()->sync($this->combinations($optionValueIds)[$position] ?? []);
        }

        if (($row['status'] ?? 'active') === 'archived') {
            $product->forceFill(['published_at' => now()->subMonths(6)])->save();
        }

        return $product->refresh()->load(['options.values', 'variants.inventoryItem', 'variants.optionValues.option', 'media']);
    }

    /** @param list<list<mixed>> $sets
     * @return list<list<mixed>>
     */
    private function combinations(array $sets): array
    {
        $combinations = [[]];
        foreach ($sets as $set) {
            $next = [];
            foreach ($combinations as $prefix) {
                foreach ($set as $value) {
                    $next[] = [...$prefix, $value];
                }
            }
            $combinations = $next;
        }

        return $combinations;
    }

    /** @return list<array<string, mixed>> */
    private function fashionProducts(): array
    {
        return [
            ['title' => 'Classic Cotton T-Shirt', 'handle' => 'classic-cotton-t-shirt', 'vendor' => 'Acme Basics', 'type' => 'T-Shirts', 'tags' => ['new', 'popular'], 'description' => 'A timeless classic cotton t-shirt. Comfortable, breathable, and perfect for everyday wear.', 'options' => [['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']], ['name' => 'Color', 'values' => ['White', 'Black', 'Navy']]], 'price' => 2499, 'weight' => 200, 'inventory' => 15],
            ['title' => 'Premium Slim Fit Jeans', 'handle' => 'premium-slim-fit-jeans', 'vendor' => 'Acme Denim', 'type' => 'Pants', 'tags' => ['new', 'sale'], 'description' => 'Slim fit jeans crafted from premium stretch denim. Comfortable all-day wear with a modern silhouette.', 'options' => [['name' => 'Size', 'values' => ['28', '30', '32', '34', '36']], ['name' => 'Color', 'values' => ['Blue', 'Black']]], 'price' => 7999, 'compare' => 9999, 'weight' => 800, 'inventory' => 8],
            ['title' => 'Organic Hoodie', 'handle' => 'organic-hoodie', 'vendor' => 'Acme Basics', 'type' => 'Hoodies', 'tags' => ['new', 'trending'], 'description' => 'Made from 100% organic cotton. Warm, soft, and sustainably produced.', 'options' => [['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']]], 'price' => 5999, 'weight' => 500, 'inventory' => 20],
            ['title' => 'Leather Belt', 'handle' => 'leather-belt', 'vendor' => 'Acme Accessories', 'type' => 'Accessories', 'tags' => ['popular'], 'description' => 'Genuine leather belt with brushed metal buckle. A wardrobe essential.', 'options' => [['name' => 'Size', 'values' => ['S/M', 'L/XL']], ['name' => 'Color', 'values' => ['Brown', 'Black']]], 'price' => 3499, 'weight' => 150, 'inventory' => 25],
            ['title' => 'Running Sneakers', 'handle' => 'running-sneakers', 'vendor' => 'Acme Sport', 'type' => 'Shoes', 'tags' => ['trending'], 'description' => 'Lightweight running sneakers with responsive cushioning and breathable mesh upper.', 'options' => [['name' => 'Size', 'values' => ['EU 38', 'EU 39', 'EU 40', 'EU 41', 'EU 42', 'EU 43', 'EU 44']], ['name' => 'Color', 'values' => ['White', 'Black']]], 'price' => 11999, 'weight' => 600, 'inventory' => 5],
            ['title' => 'Graphic Print Tee', 'handle' => 'graphic-print-tee', 'vendor' => 'Acme Basics', 'type' => 'T-Shirts', 'tags' => ['new'], 'description' => 'Bold graphic print on soft cotton. Express yourself with this statement piece.', 'options' => [['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']]], 'price' => 2999, 'weight' => 210, 'inventory' => 18],
            ['title' => 'V-Neck Linen Tee', 'handle' => 'v-neck-linen-tee', 'vendor' => 'Acme Basics', 'type' => 'T-Shirts', 'tags' => ['popular'], 'description' => 'Lightweight linen blend v-neck. Perfect for warm summer days.', 'options' => [['name' => 'Size', 'values' => ['S', 'M', 'L']], ['name' => 'Color', 'values' => ['Beige', 'Olive', 'Sky Blue']]], 'price' => 3499, 'weight' => 180, 'inventory' => 12],
            ['title' => 'Striped Polo Shirt', 'handle' => 'striped-polo-shirt', 'vendor' => 'Acme Basics', 'type' => 'T-Shirts', 'tags' => ['sale'], 'description' => 'Classic striped polo with a modern relaxed fit. Knitted collar and two-button placket.', 'options' => [['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']]], 'price' => 2799, 'compare' => 3999, 'weight' => 250, 'inventory' => 10],
            ['title' => 'Cargo Pants', 'handle' => 'cargo-pants', 'vendor' => 'Acme Workwear', 'type' => 'Pants', 'tags' => ['popular'], 'description' => 'Utility cargo pants with multiple pockets. Durable cotton twill construction.', 'options' => [['name' => 'Size', 'values' => ['30', '32', '34', '36']], ['name' => 'Color', 'values' => ['Khaki', 'Olive', 'Black']]], 'price' => 5499, 'weight' => 700, 'inventory' => 14],
            ['title' => 'Chino Shorts', 'handle' => 'chino-shorts', 'vendor' => 'Acme Basics', 'type' => 'Pants', 'tags' => ['new', 'trending'], 'description' => 'Tailored chino shorts. Comfortable stretch fabric with a clean silhouette.', 'options' => [['name' => 'Size', 'values' => ['30', '32', '34', '36']], ['name' => 'Color', 'values' => ['Navy', 'Sand']]], 'price' => 3999, 'weight' => 350, 'inventory' => 16],
            ['title' => 'Wide Leg Trousers', 'handle' => 'wide-leg-trousers', 'vendor' => 'Acme Denim', 'type' => 'Pants', 'tags' => ['sale'], 'description' => 'Relaxed wide leg trousers with a high waist. Flowing drape in premium woven fabric.', 'options' => [['name' => 'Size', 'values' => ['S', 'M', 'L']]], 'price' => 4999, 'compare' => 6999, 'weight' => 550, 'inventory' => 7],
            ['title' => 'Wool Scarf', 'handle' => 'wool-scarf', 'vendor' => 'Acme Accessories', 'type' => 'Accessories', 'tags' => ['popular'], 'description' => 'Warm merino wool scarf. Soft hand feel, naturally breathable and temperature regulating.', 'options' => [['name' => 'Color', 'values' => ['Grey', 'Burgundy', 'Navy']]], 'price' => 2999, 'weight' => 120, 'inventory' => 30],
            ['title' => 'Canvas Tote Bag', 'handle' => 'canvas-tote-bag', 'vendor' => 'Acme Accessories', 'type' => 'Accessories', 'tags' => ['trending'], 'description' => 'Heavy-duty canvas tote bag with reinforced handles. Spacious enough for daily essentials.', 'options' => [['name' => 'Color', 'values' => ['Natural', 'Black']]], 'price' => 1999, 'weight' => 300, 'inventory' => 40],
            ['title' => 'Bucket Hat', 'handle' => 'bucket-hat', 'vendor' => 'Acme Accessories', 'type' => 'Accessories', 'tags' => ['new', 'trending'], 'description' => 'Lightweight bucket hat for sun protection. Packable design, washed cotton twill.', 'options' => [['name' => 'Size', 'values' => ['S/M', 'L/XL']], ['name' => 'Color', 'values' => ['Beige', 'Black', 'Olive']]], 'price' => 2499, 'weight' => 80, 'inventory' => 22],
            ['title' => 'Unreleased Winter Jacket', 'handle' => 'unreleased-winter-jacket', 'vendor' => 'Acme Outerwear', 'type' => 'Jackets', 'tags' => ['limited'], 'description' => 'Upcoming winter collection piece. Insulated puffer jacket with water-resistant shell.', 'options' => [['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']]], 'price' => 14999, 'weight' => 900, 'inventory' => 0, 'status' => 'draft'],
            ['title' => 'Discontinued Raincoat', 'handle' => 'discontinued-raincoat', 'vendor' => 'Acme Outerwear', 'type' => 'Jackets', 'tags' => [], 'description' => 'Lightweight waterproof raincoat. This product has been discontinued.', 'options' => [['name' => 'Size', 'values' => ['M', 'L']]], 'price' => 8999, 'weight' => 400, 'inventory' => 3, 'status' => 'archived'],
            ['title' => 'Limited Edition Sneakers', 'handle' => 'limited-edition-sneakers', 'vendor' => 'Acme Sport', 'type' => 'Shoes', 'tags' => ['limited'], 'description' => 'Limited edition collaboration sneakers. Once they are gone, they are gone.', 'options' => [['name' => 'Size', 'values' => ['EU 40', 'EU 42', 'EU 44']]], 'price' => 15999, 'weight' => 650, 'inventory' => 0],
            ['title' => 'Backorder Denim Jacket', 'handle' => 'backorder-denim-jacket', 'vendor' => 'Acme Denim', 'type' => 'Jackets', 'tags' => ['popular'], 'description' => 'Classic denim jacket. Currently on backorder - ships within 2-3 weeks.', 'options' => [['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']]], 'price' => 9999, 'weight' => 750, 'inventory' => 0, 'policy' => 'continue'],
            ['title' => 'Gift Card', 'handle' => 'gift-card', 'vendor' => 'Acme Fashion', 'type' => 'Gift Cards', 'tags' => ['popular'], 'description' => 'Digital gift card delivered via email. The perfect gift when you are not sure what to choose.', 'options' => [['name' => 'Amount', 'values' => ['25 EUR', '50 EUR', '100 EUR']]], 'prices' => [2500, 5000, 10000], 'weight' => 0, 'inventory' => 9999, 'requires_shipping' => false],
            ['title' => 'Cashmere Overcoat', 'handle' => 'cashmere-overcoat', 'vendor' => 'Acme Premium', 'type' => 'Jackets', 'tags' => ['limited', 'new'], 'description' => 'Luxurious cashmere-blend overcoat. Impeccable tailoring with silk lining.', 'options' => [['name' => 'Size', 'values' => ['S', 'M', 'L']], ['name' => 'Color', 'values' => ['Camel', 'Charcoal']]], 'price' => 49999, 'weight' => 1200, 'inventory' => 3],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function electronicsProducts(): array
    {
        return [
            ['title' => 'Pro Laptop 15', 'handle' => 'pro-laptop-15', 'vendor' => 'TechCorp', 'type' => 'Laptops', 'tags' => ['professional', 'featured'], 'description' => 'A powerful professional laptop with a vivid 15-inch display.', 'options' => [['name' => 'Storage', 'values' => ['256GB', '512GB', '1TB']]], 'prices' => [99999, 119999, 149999], 'weight' => 1800, 'inventory' => 10],
            ['title' => 'Wireless Headphones', 'handle' => 'wireless-headphones', 'vendor' => 'AudioMax', 'type' => 'Audio', 'tags' => ['featured'], 'description' => 'Comfortable wireless headphones with clear, balanced sound.', 'options' => [['name' => 'Color', 'values' => ['Black', 'Silver']]], 'price' => 14999, 'weight' => 250, 'inventory' => 25],
            ['title' => 'USB-C Cable 2m', 'handle' => 'usb-c-cable-2m', 'vendor' => 'CablePro', 'type' => 'Cables', 'tags' => ['accessory'], 'description' => 'A durable two metre USB-C cable for charging and data.', 'options' => [], 'price' => 1299, 'weight' => 50, 'inventory' => 200],
            ['title' => 'Mechanical Keyboard', 'handle' => 'mechanical-keyboard', 'vendor' => 'KeyTech', 'type' => 'Peripherals', 'tags' => ['featured'], 'description' => 'A sturdy mechanical keyboard available with three switch styles.', 'options' => [['name' => 'Switch Type', 'values' => ['Red', 'Blue', 'Brown']]], 'price' => 12999, 'weight' => 1100, 'inventory' => 15],
            ['title' => 'Monitor Stand', 'handle' => 'monitor-stand', 'vendor' => 'DeskGear', 'type' => 'Accessories', 'tags' => ['accessory'], 'description' => 'A stable monitor stand that frees up desk space.', 'options' => [], 'price' => 4999, 'weight' => 2500, 'inventory' => 30],
        ];
    }

    private function seedDiscounts(Store $fashion): void
    {
        foreach ([
            ['WELCOME10', 'Welcome 10% off', 'percentage', 10, 2000, null, 3, now()->subYear(), now()->addYear(), ['min_purchase_amount' => 2000]],
            ['FLAT5', 'Five euros off', 'fixed_amount', 500, null, null, 0, now()->subYear(), now()->addYear(), []],
            ['FREESHIP', 'Free shipping', 'free_shipping', 0, null, null, 1, now()->subYear(), now()->addYear(), []],
            ['EXPIRED20', 'Expired 20% off', 'percentage', 20, null, null, 0, now()->subYears(2), now()->subYears(1), []],
            ['MAXED', 'Maximum uses reached', 'percentage', 10, null, 5, 5, now()->subYear(), now()->addYear(), []],
        ] as [$code, $title, $type, $value, $minimum, $limit, $count, $starts, $ends, $rules]) {
            Discount::query()->updateOrCreate(['store_id' => $fashion->id, 'code' => $code], [
                'title' => $title,
                'type' => $type,
                'value' => $value,
                'minimum_subtotal_amount' => $minimum,
                'usage_limit' => $limit,
                'usage_count' => $count,
                'starts_at' => $starts,
                'ends_at' => $ends,
                'is_active' => true,
                'rules_json' => $rules,
            ]);
        }
    }

    private function seedCustomers(Store $fashion, Store $electronics): void
    {
        $fashionRows = [
            ['customer@acme.test', 'John Doe', true], ['jane@example.com', 'Jane Smith', false],
            ['michael@example.com', 'Michael Brown', true], ['sarah@example.com', 'Sarah Wilson', false],
            ['david@example.com', 'David Lee', true], ['emma@example.com', 'Emma Garcia', false],
            ['james@example.com', 'James Taylor', false], ['lisa@example.com', 'Lisa Anderson', true],
            ['robert@example.com', 'Robert Martinez', false], ['anna@example.com', 'Anna Thomas', true],
        ];
        foreach ($fashionRows as $index => [$email, $name, $optIn]) {
            $this->customers[$email] = $this->createCustomer($fashion, $email, $name, $optIn);
            $addresses = match ($index) {
                0 => [
                    ['Home', true, 'John', 'Doe', 'Hauptstrasse 1', '', 'Berlin', '', '10115', 'DE', '+49 30 12345678'],
                    ['Work', false, 'John', 'Doe', 'Friedrichstrasse 100', '3rd Floor', 'Berlin', '', '10117', 'DE', '+49 30 87654321'],
                ],
                1 => [['Home', true, 'Jane', 'Smith', 'Schillerstrasse 45', '', 'Munich', 'Bavaria', '80336', 'DE', '']],
                default => [['Home', true, Str::before($name, ' '), Str::after($name, ' '), 'Hauptstrasse '.(10 + $index), '', ['Berlin', 'Hamburg', 'Munich', 'Cologne'][$index % 4], '', '10115', 'DE', '+49 30 5550000']],
            };
            $this->createAddresses($this->customers[$email], $addresses);
        }

        foreach ([['techfan@example.com', 'Tech Fan'], ['gadgetlover@example.com', 'Gadget Lover']] as [$email, $name]) {
            $this->customers[$email] = $this->createCustomer($electronics, $email, $name, false);
            $this->createAddresses($this->customers[$email], [['Home', true, Str::before($name, ' '), Str::after($name, ' '), 'Hauptstrasse 20', '', 'Berlin', '', '10115', 'DE', '']]);
        }
    }

    private function createCustomer(Store $store, string $email, string $name, bool $marketingOptIn): Customer
    {
        return Customer::query()->updateOrCreate(['store_id' => $store->id, 'email' => $email], [
            'name' => $name,
            'password' => Hash::make('password'),
            'marketing_opt_in' => $marketingOptIn,
        ]);
    }

    /** @param list<array{0: string, 1: bool, 2: string, 3: string, 4: string, 5: string, 6: string, 7: string, 8: string, 9: string, 10: string}> $addresses */
    private function createAddresses(Customer $customer, array $addresses): void
    {
        $customer->addresses()->delete();
        foreach ($addresses as [$label, $default, $first, $last, $line1, $line2, $city, $state, $zip, $country, $phone]) {
            CustomerAddress::query()->create([
                'customer_id' => $customer->id,
                'label' => $label,
                'is_default' => $default,
                'address_json' => [
                    'first_name' => $first, 'last_name' => $last,
                    'address_line_1' => $line1, 'address_line_2' => $line2,
                    'address1' => $line1, 'address2' => $line2,
                    'city' => $city, 'state' => $state, 'province' => $state,
                    'postal_code' => $zip, 'zip' => $zip, 'country' => $country, 'country_code' => $country, 'phone' => $phone,
                ],
            ]);
        }
    }

    private function seedContent(Store $fashion, Store $electronics): void
    {
        $fashionPages = [
            ['About Us', 'about', '<h2>Our Story</h2><p>We are a Berlin-based team creating modern essentials for everyday life. We believe the best pieces are useful, thoughtfully made, and designed to last.</p><h2>Our Values</h2><p>We work with responsible suppliers, choose materials with care, and value ethical sourcing, sustainability, and fair labor.</p><h2>Our Team</h2><p>Our designers and makers bring a practical, curious point of view to every collection.</p>'],
            ['FAQ', 'faq', '<h2>Frequently Asked Questions</h2><h3>When will my order arrive?</h3><p>Orders in Germany usually arrive in 2–4 days with standard shipping and 1–2 days with express shipping. EU orders typically arrive in 5–7 days.</p><h3>What is your return policy?</h3><p>You may return unworn items in their original packaging within 30 days.</p><h3>Where do you ship?</h3><p>We ship throughout the EU and to the US, UK, Canada, and Australia.</p><h3>How do I track my order?</h3><p>We email a tracking number as soon as your order ships.</p>'],
            ['Shipping & Returns', 'shipping-returns', '<h2>Shipping</h2><h3>Germany</h3><ul><li>Standard shipping: €4.99</li><li>Express shipping: €9.99</li></ul><h3>European Union</h3><ul><li>EU standard shipping: €8.99</li></ul><h3>International</h3><ul><li>International shipping: €14.99</li></ul><h2>Returns</h2><p>Return unworn items in their original packaging within 30 days. Return shipping is paid by the customer unless an item is defective.</p>'],
            ['Privacy Policy', 'privacy-policy', '<h2>Information We Collect</h2><p>We collect the information needed to fulfill orders and provide customer support.</p><h2>How We Use Your Information</h2><p>We use order details to process purchases, communicate about delivery, and improve our service.</p><h2>Cookies</h2><p>Our site uses essential cookies to keep your cart and account working.</p><h2>Contact</h2><p>For privacy questions, contact privacy@acme-fashion.test.</p>'],
            ['Terms of Service', 'terms', '<h2>Orders and Payments</h2><p>Prices are shown in EUR and include applicable tax. Orders are subject to availability and payment confirmation.</p><h2>Product Descriptions</h2><p>We work to show products accurately; screen settings may create slight color differences.</p><h2>Limitation of Liability</h2><p>Nothing in these terms limits rights that cannot be limited under applicable law.</p><h2>Governing Law</h2><p>These terms are governed by the laws of the Federal Republic of Germany.</p>'],
        ];
        foreach ($fashionPages as [$title, $handle, $html]) {
            Page::query()->updateOrCreate(['store_id' => $fashion->id, 'handle' => $handle], ['title' => $title, 'body_html' => $html, 'status' => 'published', 'published_at' => now()->subMonths(3)]);
        }

        $this->seedNavigation($fashion, $electronics);
        $this->seedTheme($fashion, $electronics);
        DB::table('search_settings')->updateOrInsert(['store_id' => $fashion->id], [
            'synonyms_json' => json_encode([['tee', 't-shirt', 'tshirt'], ['pants', 'trousers', 'jeans'], ['sneakers', 'trainers', 'shoes'], ['hoodie', 'sweatshirt']], JSON_THROW_ON_ERROR),
            'stopwords_json' => json_encode(['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'is'], JSON_THROW_ON_ERROR),
            'updated_at' => now(),
        ]);
        DB::table('search_settings')->updateOrInsert(['store_id' => $electronics->id], [
            'synonyms_json' => json_encode([['laptop', 'notebook', 'computer'], ['headphones', 'earphones', 'earbuds'], ['cable', 'cord', 'wire']], JSON_THROW_ON_ERROR),
            'stopwords_json' => json_encode(['the', 'a', 'an', 'and', 'or'], JSON_THROW_ON_ERROR),
            'updated_at' => now(),
        ]);
    }

    private function seedNavigation(Store $fashion, Store $electronics): void
    {
        $menus = [
            [$fashion, 'main-menu', 'Main Menu', [['Home', 'link', '/', null], ['New Arrivals', 'collection', null, 'new-arrivals'], ['T-Shirts', 'collection', null, 't-shirts'], ['Pants & Jeans', 'collection', null, 'pants-jeans'], ['Sale', 'collection', null, 'sale']]],
            [$fashion, 'footer-menu', 'Footer Menu', [['About Us', 'page', null, 'about'], ['FAQ', 'page', null, 'faq'], ['Shipping & Returns', 'page', null, 'shipping-returns'], ['Privacy Policy', 'page', null, 'privacy-policy'], ['Terms of Service', 'page', null, 'terms']]],
            [$electronics, 'main-menu', 'Main Menu', [['Home', 'link', '/', null], ['Featured', 'collection', null, 'featured'], ['Accessories', 'collection', null, 'accessories']]],
        ];

        foreach ($menus as [$store, $handle, $title, $items]) {
            $menu = \App\Models\NavigationMenu::query()->updateOrCreate(['store_id' => $store->id, 'handle' => $handle], ['title' => $title]);
            $menu->items()->delete();
            foreach ($items as $position => [$label, $type, $url, $resourceHandle]) {
                $resourceId = null;
                if ($resourceHandle !== null) {
                    $resourceId = $type === 'collection'
                        ? \App\Models\Collection::query()->where('store_id', $store->id)->where('handle', $resourceHandle)->value('id')
                        : Page::query()->where('store_id', $store->id)->where('handle', $resourceHandle)->value('id');
                    $url = $type === 'collection' ? '/collections/'.$resourceHandle : '/pages/'.$resourceHandle;
                }
                $menu->items()->create(['label' => $label, 'type' => $type, 'url' => $url, 'resource_id' => $resourceId, 'position' => $position]);
            }
        }
    }

    private function seedTheme(Store $fashion, Store $electronics): void
    {
        foreach ([
            [$fashion, '#1a1a2e', '#e94560', 'Welcome to Acme Fashion', 'Discover our curated collection of modern essentials', '/collections/new-arrivals', ['new-arrivals', 't-shirts', 'sale']],
            [$electronics, '#0f172a', '#3b82f6', 'Acme Electronics', 'Premium tech for professionals', '/collections/featured', ['featured']],
        ] as [$store, $primary, $secondary, $heading, $subheading, $ctaUrl, $featuredHandles]) {
            $theme = Theme::query()->updateOrCreate(['store_id' => $store->id, 'name' => 'Default Theme'], ['status' => 'published', 'is_active' => true]);
            $mainMenu = \App\Models\NavigationMenu::query()->where('store_id', $store->id)->where('handle', 'main-menu')->first();
            $footerItems = \App\Models\NavigationMenu::query()->where('store_id', $store->id)->where('handle', 'footer-menu')->first()?->items ?? collect();
            $footerMenu = $footerItems->chunk(3)->map(fn ($items, int $index): array => [
                'label' => $index === 0 ? 'Explore' : 'More',
                'children' => $items->map(fn ($item): array => ['label' => $item->label, 'url' => $item->url])->all(),
            ])->all();
            $mainLinks = $mainMenu?->items->map(fn ($item): array => ['label' => $item->label, 'url' => $item->url])->all() ?? [];
            $theme->settings()->updateOrCreate(['theme_id' => $theme->id], ['settings_json' => [
                'colors' => ['primary' => $primary, 'secondary' => $secondary, 'accent' => $secondary],
                'font_family' => 'Inter, sans-serif',
                'home' => ['hero' => ['enabled' => true, 'heading' => $heading, 'subheading' => $subheading, 'cta_text' => $store->id === $fashion->id ? 'Shop New Arrivals' : 'Shop Featured', 'cta_url' => $ctaUrl], 'featured_collections' => ['enabled' => true, 'count' => 4], 'featured_products' => ['enabled' => true, 'count' => 8], 'newsletter' => ['enabled' => true]],
                'featured_collection_handles' => $featuredHandles,
                'navigation' => ['main-menu' => $mainLinks, 'footer-menu' => $footerMenu],
                'footer_text' => now()->year.' '.$store->name.'. All rights reserved.',
                'announcement' => ['enabled' => $store->id === $fashion->id, 'text' => 'Free shipping on orders over 50 EUR - Use code FREESHIP', 'url' => null],
                'sticky_header' => true,
                'dark_mode' => 'system',
                'products_per_page' => 12,
                'show_vendor' => true,
                'show_quantity_selector' => true,
                'home_sections' => ['hero', 'featured_collections', 'featured_products', 'newsletter'],
                'seo' => ['home_description' => $subheading],
                'payment_methods' => ['Visa', 'Mastercard', 'PayPal'],
            ]]);
        }
    }

    private function seedOrders(Store $fashion, Store $electronics): void
    {
        $orders = [
            [1001, 'customer@acme.test', 'paid', 'paid', 'unfulfilled', 'credit_card', 4998, 0, 499, 798, 5497, 2, [['classic-cotton-t-shirt', 'S / White', 2]], null],
            [1002, 'customer@acme.test', 'fulfilled', 'paid', 'fulfilled', 'credit_card', 8498, 0, 499, 1357, 8997, 10, [['organic-hoodie', 'M', 1], ['classic-cotton-t-shirt', 'L / Black', 1]], ['DHL', 'DHL1234567890', 8, 'delivered', null]],
            [1003, 'jane@example.com', 'paid', 'paid', 'partial', 'credit_card', 11498, 0, 499, 1836, 11997, 5, [['premium-slim-fit-jeans', '32 / Blue', 1], ['leather-belt', 'L/XL / Brown', 1]], ['DHL', 'DHL9876543210', 3, 'shipped', [0]]],
            [1004, 'customer@acme.test', 'cancelled', 'refunded', 'unfulfilled', 'credit_card', 2499, 0, 499, 399, 2998, 15, [['classic-cotton-t-shirt', 'M / Navy', 1]], null],
            [1005, 'jane@example.com', 'pending', 'pending', 'unfulfilled', 'bank_transfer', 3499, 0, 499, 559, 3998, 0, [['leather-belt', 'S/M / Black', 1]], null],
            [1006, 'michael@example.com', 'paid', 'paid', 'unfulfilled', 'credit_card', 11999, 0, 499, 1916, 12498, 1, [['running-sneakers', 'EU 42 / Black', 1]], null],
            [1007, 'sarah@example.com', 'fulfilled', 'paid', 'fulfilled', 'paypal', 9997, 0, 499, 1596, 10496, 20, [['v-neck-linen-tee', 'M / Beige', 2], ['wool-scarf', 'Grey', 1]], ['DHL', 'DHL1112223334', 18, 'delivered', null]],
            [1008, 'david@example.com', 'paid', 'partially_refunded', 'fulfilled', 'credit_card', 8498, 0, 499, 1357, 8997, 12, [['cargo-pants', '32 / Khaki', 1], ['graphic-print-tee', 'L', 1]], ['UPS', 'UPS5556667778', 10, 'delivered', null]],
            [1009, 'emma@example.com', 'paid', 'paid', 'unfulfilled', 'credit_card', 4498, 0, 499, 718, 4997, 3, [['canvas-tote-bag', 'Natural', 1], ['bucket-hat', 'S/M / Black', 1]], null],
            [1010, 'customer@acme.test', 'paid', 'paid', 'unfulfilled', 'paypal', 49999, 0, 499, 7983, 50498, 1, [['cashmere-overcoat', 'M / Camel', 1]], null],
            [1011, 'james@example.com', 'fulfilled', 'paid', 'fulfilled', 'credit_card', 2799, 0, 499, 447, 3298, 25, [['striped-polo-shirt', 'XL', 1]], ['FedEx', 'FX9998887776', 23, 'delivered', null]],
            [1012, 'lisa@example.com', 'paid', 'paid', 'unfulfilled', 'credit_card', 7998, 0, 499, 1277, 8497, 4, [['chino-shorts', '34 / Navy', 2]], null],
            [1013, 'robert@example.com', 'paid', 'paid', 'unfulfilled', 'credit_card', 7998, 0, 499, 1277, 8497, 1, [['wide-leg-trousers', 'M', 1], ['wool-scarf', 'Burgundy', 1]], null],
            [1014, 'anna@example.com', 'fulfilled', 'paid', 'fulfilled', 'credit_card', 5000, 0, 0, 798, 5000, 14, [['gift-card', '50 EUR', 1]], ['', '', 14, 'delivered', null]],
            [1015, 'customer@acme.test', 'paid', 'paid', 'unfulfilled', 'bank_transfer', 5498, 550, 499, 790, 5447, 0, [['classic-cotton-t-shirt', 'M / White', 1, 250], ['graphic-print-tee', 'M', 1, 300]], null],
        ];

        foreach ($orders as $orderData) {
            $this->createOrder($fashion, $orderData);
        }

        $this->createOrder($electronics, [5001, 'techfan@example.com', 'fulfilled', 'paid', 'fulfilled', 'credit_card', 121298, 0, 0, 0, 121298, 3, [['pro-laptop-15', '512GB', 1], ['usb-c-cable-2m', 'Default Title', 1]], ['DHL', 'ACME5001', 2, 'delivered', null]]);
        $this->createOrder($electronics, [5002, 'gadgetlover@example.com', 'paid', 'paid', 'unfulfilled', 'credit_card', 14999, 0, 0, 0, 14999, 2, [['wireless-headphones', 'Black', 1]], null]);
        $this->createOrder($electronics, [5003, 'techfan@example.com', 'pending', 'pending', 'unfulfilled', 'bank_transfer', 4999, 0, 0, 0, 4999, 1, [['monitor-stand', 'Default Title', 1]], null]);
    }

    /** @param array<int, mixed> $data */
    private function createOrder(Store $store, array $data): Order
    {
        [$number, $email, $status, $financial, $fulfillmentStatus, $method, $subtotal, $discount, $shipping, $tax, $total, $daysAgo, $lineData, $fulfillmentData] = $data;
        $customer = $this->customers[$email];
        $address = $customer->addresses()->where('is_default', true)->first()?->address_json;
        $placedAt = now()->subDays($daysAgo);
        $orderNumber = '#'.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
        $order = Order::query()->updateOrCreate(['store_id' => $store->id, 'order_number' => $orderNumber], [
            'customer_id' => $customer->id,
            'payment_method' => $method,
            'status' => $status,
            'financial_status' => $financial,
            'fulfillment_status' => $fulfillmentStatus,
            'currency' => 'EUR',
            'subtotal_amount' => $subtotal,
            'discount_amount' => $discount,
            'shipping_amount' => $shipping,
            'tax_amount' => $tax,
            'total_amount' => $total,
            'email' => $email,
            'billing_address_json' => $address,
            'shipping_address_json' => $address,
            'placed_at' => $placedAt,
        ]);
        $order->refunds()->delete();
        $order->fulfillments()->delete();
        $order->payments()->delete();
        $order->lines()->delete();
        $lineModels = [];

        foreach ($lineData as $lineEntry) {
            [$handle, $variantTitle, $quantity, $lineDiscount] = array_pad($lineEntry, 4, 0);
            $product = $this->products[$handle];
            $variant = $product->variants->first(static fn (ProductVariant $candidate): bool => $candidate->title === $variantTitle)
                ?? throw new \RuntimeException("Missing seeded variant {$handle}: {$variantTitle}.");
            $lineTotal = ($variant->price_amount * $quantity) - $lineDiscount;
            $lineModels[] = $order->lines()->create([
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'title_snapshot' => $product->title,
                'variant_title_snapshot' => $variant->title,
                'sku_snapshot' => $variant->sku,
                'quantity' => $quantity,
                'unit_price_amount' => $variant->price_amount,
                'total_amount' => $lineTotal,
                'tax_lines_json' => [],
                'discount_allocations_json' => $lineDiscount > 0 ? [['code' => 'WELCOME10', 'amount' => $lineDiscount]] : [],
            ]);
        }

        $payment = $order->payments()->create([
            'provider' => 'mock',
            'method' => $method,
            'provider_payment_id' => 'mock_test_order'.$number,
            'status' => $financial === 'pending' ? 'pending' : ($financial === 'refunded' ? 'refunded' : ($financial === 'partially_refunded' ? 'partially_refunded' : 'captured')),
            'amount' => $total,
            'currency' => 'EUR',
            'raw_json_encrypted' => Crypt::encryptString(json_encode(['status' => $financial === 'pending' ? 'pending' : 'captured'], JSON_THROW_ON_ERROR)),
            'created_at' => $placedAt,
        ]);

        if ($fulfillmentData !== null) {
            [$company, $tracking, $shippedDaysAgo, $fulfillmentState, $linePositions] = $fulfillmentData;
            $shippedAt = now()->subDays($shippedDaysAgo);
            $fulfillment = $order->fulfillments()->create([
                'status' => $fulfillmentState,
                'tracking_company' => $company ?: null,
                'tracking_number' => $tracking ?: null,
                'shipped_at' => $shippedAt,
                'delivered_at' => $fulfillmentState === 'delivered' ? $shippedAt : null,
                'created_at' => $shippedAt,
            ]);
            $linePositions ??= array_keys($lineModels);
            foreach ($linePositions as $position) {
                $fulfillment->lines()->create(['order_line_id' => $lineModels[$position]->id, 'quantity' => $lineModels[$position]->quantity]);
            }
        }

        if ((int) $number === 1004 || (int) $number === 1008) {
            $refundAmount = (int) $number === 1004 ? $total : 2999;
            $refund = $order->refunds()->create([
                'payment_id' => $payment->id,
                'amount' => $refundAmount,
                'reason' => (int) $number === 1004 ? 'Customer requested cancellation' : 'Item returned',
                'status' => 'processed',
                'provider_refund_id' => 'mock_re_test_order'.$number,
                'created_at' => $placedAt,
            ]);
            $refund->lines()->create([
                'order_line_id' => (int) $number === 1004 ? $lineModels[0]->id : $lineModels[1]->id,
                'quantity' => 1,
                'amount' => (int) $number === 1004 ? $lineModels[0]->total_amount : 2999,
            ]);
        }

        $order->forceFill(['created_at' => $placedAt, 'updated_at' => $placedAt])->save();

        return $order->refresh();
    }

    private function seedAnalytics(Store $fashion, Store $electronics): void
    {
        foreach ([$fashion, $electronics] as $store) {
            for ($daysAgo = 30; $daysAgo >= 0; $daysAgo--) {
                $growth = 1 + (30 - $daysAgo) * 0.03;
                $visits = (int) round((50 + (($daysAgo * 17) % 51)) * $growth);
                $addToCart = (int) round($visits * (18 + (($daysAgo * 3) % 8)) / 100);
                $checkouts = (int) round($addToCart * (40 + (($daysAgo * 5) % 16)) / 100);
                $orders = max(2, (int) round($checkouts * (35 + (($daysAgo * 7) % 21)) / 100));
                $aov = 4000 + (($daysAgo * 379) % 5000);
                DB::table('analytics_daily')->updateOrInsert(['store_id' => $store->id, 'date' => now()->subDays($daysAgo)->toDateString()], [
                    'orders_count' => $orders,
                    'revenue_amount' => $orders * $aov,
                    'aov_amount' => $aov,
                    'visits_count' => $visits,
                    'add_to_cart_count' => $addToCart,
                    'checkout_started_count' => $checkouts,
                ]);
            }
        }

        $eventTypes = ['page_view', 'page_view', 'product_view', 'product_view', 'add_to_cart', 'checkout_started', 'checkout_completed', 'search'];
        $handles = array_values(array_filter(array_keys($this->products), static fn (string $handle): bool => ! in_array($handle, ['pro-laptop-15', 'wireless-headphones', 'usb-c-cable-2m', 'mechanical-keyboard', 'monitor-stand'], true)));
        for ($index = 0; $index < 220; $index++) {
            $type = $eventTypes[$index % count($eventTypes)];
            $product = $this->products[$handles[$index % count($handles)]];
            $sessionId = 'demo-session-'.str_pad((string) ($index % 35), 3, '0', STR_PAD_LEFT);
            $payload = match ($type) {
                'page_view' => ['url' => $index % 2 === 0 ? '/' : '/collections/new-arrivals', 'referrer' => 'https://www.google.com'],
                'product_view' => ['product_id' => $product->id, 'product_title' => $product->title, 'url' => '/products/'.$product->handle],
                'add_to_cart' => ['product_id' => $product->id, 'variant_id' => $product->variants->first()->id, 'quantity' => 1 + ($index % 3), 'price_amount' => $product->variants->first()->price_amount],
                'checkout_started' => ['cart_id' => null, 'item_count' => 1 + ($index % 4), 'cart_total' => 5000 + (($index * 137) % 15000)],
                'checkout_completed' => ['order_id' => null, 'order_number' => '#'.(1001 + ($index % 15)), 'total_amount' => 5000 + (($index * 137) % 25000)],
                default => ['query' => ['cotton t-shirt', 'jeans', 'gift card'][$index % 3], 'results_count' => 1 + ($index % 8)],
            };
            AnalyticsEvent::query()->updateOrCreate(
                ['store_id' => $fashion->id, 'client_event_id' => 'demo-event-'.$index],
                [
                    'customer_id' => $index % 10 < 3 ? $this->customers[array_keys($this->customers)[$index % 10]]->id : null,
                    'type' => $type,
                    'session_id' => $sessionId,
                    'payload' => $payload,
                    'created_at' => now()->subDays($index % 7)->subMinutes($index * 3),
                ],
            );
        }
    }
}
