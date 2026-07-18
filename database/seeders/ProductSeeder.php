<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedFashionProducts();
            $this->seedElectronicsProducts();
        });
    }

    private function seedFashionProducts(): void
    {
        $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();

        $products = [
            ['Classic Cotton T-Shirt', 'classic-cotton-t-shirt', 'Acme Basics', 'T-Shirts', ['new', 'popular'], 'A timeless classic cotton t-shirt. Comfortable, breathable, and perfect for everyday wear.', ['Size' => ['S', 'M', 'L', 'XL'], 'Color' => ['White', 'Black', 'Navy']], 2499, null, 200, 15, 'deny', ['new-arrivals', 't-shirts']],
            ['Premium Slim Fit Jeans', 'premium-slim-fit-jeans', 'Acme Denim', 'Pants', ['new', 'sale'], 'Slim fit jeans crafted from premium stretch denim. Comfortable all-day wear with a modern silhouette.', ['Size' => ['28', '30', '32', '34', '36'], 'Color' => ['Blue', 'Black']], 7999, 9999, 800, 8, 'deny', ['new-arrivals', 'pants-jeans', 'sale']],
            ['Organic Hoodie', 'organic-hoodie', 'Acme Basics', 'Hoodies', ['new', 'trending'], 'Made from 100% organic cotton. Warm, soft, and sustainably produced.', ['Size' => ['S', 'M', 'L', 'XL']], 5999, null, 500, 20, 'deny', ['new-arrivals']],
            ['Leather Belt', 'leather-belt', 'Acme Accessories', 'Accessories', ['popular'], 'Genuine leather belt with brushed metal buckle. A wardrobe essential.', ['Size' => ['S/M', 'L/XL'], 'Color' => ['Brown', 'Black']], 3499, null, 150, 25, 'deny', []],
            ['Running Sneakers', 'running-sneakers', 'Acme Sport', 'Shoes', ['trending'], 'Lightweight running sneakers with responsive cushioning and breathable mesh upper.', ['Size' => ['EU 38', 'EU 39', 'EU 40', 'EU 41', 'EU 42', 'EU 43', 'EU 44'], 'Color' => ['White', 'Black']], 11999, null, 600, 5, 'deny', ['new-arrivals']],
            ['Graphic Print Tee', 'graphic-print-tee', 'Acme Basics', 'T-Shirts', ['new'], 'Bold graphic print on soft cotton. Express yourself with this statement piece.', ['Size' => ['S', 'M', 'L', 'XL']], 2999, null, 210, 18, 'deny', ['t-shirts']],
            ['V-Neck Linen Tee', 'v-neck-linen-tee', 'Acme Basics', 'T-Shirts', ['popular'], 'Lightweight linen blend v-neck. Perfect for warm summer days.', ['Size' => ['S', 'M', 'L'], 'Color' => ['Beige', 'Olive', 'Sky Blue']], 3499, null, 180, 12, 'deny', ['t-shirts']],
            ['Striped Polo Shirt', 'striped-polo-shirt', 'Acme Basics', 'T-Shirts', ['sale'], 'Classic striped polo with a modern relaxed fit. Knitted collar and two-button placket.', ['Size' => ['S', 'M', 'L', 'XL']], 2799, 3999, 250, 10, 'deny', ['t-shirts', 'sale']],
            ['Cargo Pants', 'cargo-pants', 'Acme Workwear', 'Pants', ['popular'], 'Utility cargo pants with multiple pockets. Durable cotton twill construction.', ['Size' => ['30', '32', '34', '36'], 'Color' => ['Khaki', 'Olive', 'Black']], 5499, null, 700, 14, 'deny', ['pants-jeans']],
            ['Chino Shorts', 'chino-shorts', 'Acme Basics', 'Pants', ['new', 'trending'], 'Tailored chino shorts. Comfortable stretch fabric with a clean silhouette.', ['Size' => ['30', '32', '34', '36'], 'Color' => ['Navy', 'Sand']], 3999, null, 350, 16, 'deny', ['pants-jeans', 'new-arrivals']],
            ['Wide Leg Trousers', 'wide-leg-trousers', 'Acme Denim', 'Pants', ['sale'], 'Relaxed wide leg trousers with a high waist. Flowing drape in premium woven fabric.', ['Size' => ['S', 'M', 'L']], 4999, 6999, 550, 7, 'deny', ['pants-jeans', 'sale']],
            ['Wool Scarf', 'wool-scarf', 'Acme Accessories', 'Accessories', ['popular'], 'Warm merino wool scarf. Soft hand feel, naturally breathable and temperature regulating.', ['Color' => ['Grey', 'Burgundy', 'Navy']], 2999, null, 120, 30, 'deny', []],
            ['Canvas Tote Bag', 'canvas-tote-bag', 'Acme Accessories', 'Accessories', ['trending'], 'Heavy-duty canvas tote bag with reinforced handles. Spacious enough for daily essentials.', ['Color' => ['Natural', 'Black']], 1999, null, 300, 40, 'deny', []],
            ['Bucket Hat', 'bucket-hat', 'Acme Accessories', 'Accessories', ['new', 'trending'], 'Lightweight bucket hat for sun protection. Packable design, washed cotton twill.', ['Size' => ['S/M', 'L/XL'], 'Color' => ['Beige', 'Black', 'Olive']], 2499, null, 80, 22, 'deny', ['new-arrivals']],
            ['Unreleased Winter Jacket', 'unreleased-winter-jacket', 'Acme Outerwear', 'Jackets', ['limited'], 'Upcoming winter collection piece. Insulated puffer jacket with water-resistant shell.', ['Size' => ['S', 'M', 'L', 'XL']], 14999, null, 900, 0, 'deny', [], 'draft'],
            ['Discontinued Raincoat', 'discontinued-raincoat', 'Acme Outerwear', 'Jackets', [], 'Lightweight waterproof raincoat. This product has been discontinued.', ['Size' => ['M', 'L']], 8999, null, 400, 3, 'deny', [], 'archived'],
            ['Limited Edition Sneakers', 'limited-edition-sneakers', 'Acme Sport', 'Shoes', ['limited'], 'Limited edition collaboration sneakers. Once they are gone, they are gone.', ['Size' => ['EU 40', 'EU 42', 'EU 44']], 15999, null, 650, 0, 'deny', []],
            ['Backorder Denim Jacket', 'backorder-denim-jacket', 'Acme Denim', 'Jackets', ['popular'], 'Classic denim jacket. Currently on backorder - ships within 2-3 weeks.', ['Size' => ['S', 'M', 'L', 'XL']], 9999, null, 750, 0, 'continue', []],
            ['Gift Card', 'gift-card', 'Acme Fashion', 'Gift Cards', ['popular'], 'Digital gift card delivered via email. The perfect gift when you are not sure what to choose.', ['Amount' => ['25 EUR', '50 EUR', '100 EUR']], [2500, 5000, 10000], null, 0, 9999, 'deny', []],
            ['Cashmere Overcoat', 'cashmere-overcoat', 'Acme Premium', 'Jackets', ['limited', 'new'], 'Luxurious cashmere-blend overcoat. Impeccable tailoring with silk lining.', ['Size' => ['S', 'M', 'L'], 'Color' => ['Camel', 'Charcoal']], 49999, null, 1200, 3, 'deny', ['new-arrivals']],
        ];

        foreach ($products as $index => $data) {
            $this->seedProduct($store, $index + 1, $data);
        }

        $this->assignCollections($store, [
            'new-arrivals' => [1, 2, 3, 5, 10, 14, 20],
            't-shirts' => [1, 6, 7, 8],
            'pants-jeans' => [2, 9, 10, 11],
            'sale' => [2, 8, 11],
        ], $products);
    }

    private function seedElectronicsProducts(): void
    {
        $store = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

        $products = [
            ['Pro Laptop 15', 'pro-laptop-15', 'TechCorp', 'Laptops', ['featured'], 'A powerful professional laptop.', ['Storage' => ['256GB', '512GB', '1TB']], [99999, 119999, 149999], null, 1800, 10, 'deny', ['featured']],
            ['Wireless Headphones', 'wireless-headphones', 'AudioMax', 'Audio', ['popular'], 'Premium wireless headphones.', ['Color' => ['Black', 'Silver']], 14999, null, 250, 25, 'deny', ['featured']],
            ['USB-C Cable 2m', 'usb-c-cable-2m', 'CablePro', 'Cables', [], 'Durable two metre USB-C cable.', [], 1299, null, 50, 200, 'deny', ['accessories']],
            ['Mechanical Keyboard', 'mechanical-keyboard', 'KeyTech', 'Peripherals', ['featured'], 'Mechanical keyboard for professionals.', ['Switch Type' => ['Red', 'Blue', 'Brown']], 12999, null, 1100, 15, 'deny', ['featured']],
            ['Monitor Stand', 'monitor-stand', 'DeskGear', 'Accessories', [], 'Ergonomic monitor stand.', [], 4999, null, 2500, 30, 'deny', ['accessories']],
        ];

        foreach ($products as $index => $data) {
            $this->seedProduct($store, $index + 1, $data, 'ELEC');
        }

        $this->assignCollections($store, [
            'featured' => [1, 2, 4],
            'accessories' => [3, 5],
        ], $products);
    }

    /**
     * @param  array<int, mixed>  $data
     */
    private function seedProduct(Store $store, int $number, array $data, string $skuPrefix = 'ACME'): void
    {
        [$title, $handle, $vendor, $type, $tags, $description, $options, $prices, $compareAt, $weight, $quantity, $policy, $collectionHandles, $status] = array_pad($data, 14, 'active');

        $product = Product::query()->updateOrCreate(
            ['store_id' => $store->id, 'handle' => $handle],
            [
                'title' => $title,
                'status' => $status,
                'description_html' => '<p>'.$description.'</p>',
                'vendor' => $vendor,
                'product_type' => $type,
                'tags' => $tags,
                'published_at' => $status === 'draft' ? null : ($status === 'archived' ? now()->subMonths(6) : now()),
            ],
        );

        $optionValues = [];

        foreach ($options as $optionPosition => $option) {
            $optionModel = ProductOption::query()->updateOrCreate(
                ['product_id' => $product->id, 'name' => $optionPosition],
                ['position' => count($optionValues)],
            );

            $values = [];
            foreach ($option as $valuePosition => $value) {
                $values[] = ProductOptionValue::query()->updateOrCreate(
                    ['product_option_id' => $optionModel->id, 'value' => $value],
                    ['position' => $valuePosition],
                );
            }
            $optionValues[] = $values;
        }

        $combinations = $this->combinations($optionValues);
        foreach ($combinations as $position => $combination) {
            $price = is_array($prices) ? $prices[$position] : $prices;
            $sku = $this->sku($skuPrefix, $number, $handle, $combination, $position);
            $requiresShipping = $handle !== 'gift-card';

            $variant = ProductVariant::query()->updateOrCreate(
                ['product_id' => $product->id, 'sku' => $sku],
                [
                    'barcode' => null,
                    'price_amount' => $price,
                    'compare_at_amount' => $compareAt,
                    'currency' => 'EUR',
                    'weight_g' => $weight,
                    'requires_shipping' => $requiresShipping,
                    'is_default' => $position === 0,
                    'position' => $position,
                    'status' => 'active',
                ],
            );

            $variant->optionValues()->sync(collect($combination)->pluck('id')->all());

            InventoryItem::query()->updateOrCreate(
                ['variant_id' => $variant->id],
                [
                    'store_id' => $store->id,
                    'quantity_on_hand' => $quantity,
                    'quantity_reserved' => 0,
                    'policy' => $policy,
                ],
            );
        }

        $collections = Collection::query()
            ->where('store_id', $store->id)
            ->whereIn('handle', $collectionHandles)
            ->get()
            ->keyBy('handle');

        $product->collections()->sync(
            collect($collectionHandles)->mapWithKeys(
                fn (string $collectionHandle, int $position): array => [
                    $collections->get($collectionHandle)->id => ['position' => $position],
                ],
            )->all(),
        );
    }

    /**
     * @param  array<string, array<int, int>>  $assignments
     * @param  array<int, array<int, mixed>>  $products
     */
    private function assignCollections(Store $store, array $assignments, array $products): void
    {
        foreach ($assignments as $collectionHandle => $productNumbers) {
            $collection = Collection::query()
                ->where('store_id', $store->id)
                ->where('handle', $collectionHandle)
                ->firstOrFail();

            $collection->products()->sync(
                collect($productNumbers)->mapWithKeys(function (int $productNumber, int $position) use ($products, $store): array {
                    $product = Product::query()
                        ->where('store_id', $store->id)
                        ->where('handle', $products[$productNumber - 1][1])
                        ->firstOrFail();

                    return [$product->id => ['position' => $position]];
                })->all(),
            );
        }
    }

    /**
     * @param  array<int, array<int, ProductOptionValue>>  $optionValues
     * @return array<int, array<int, ProductOptionValue>>
     */
    private function combinations(array $optionValues): array
    {
        if ($optionValues === []) {
            return [[]];
        }

        $combinations = [[]];

        foreach ($optionValues as $values) {
            $next = [];
            foreach ($combinations as $combination) {
                foreach ($values as $value) {
                    $next[] = [...$combination, $value];
                }
            }
            $combinations = $next;
        }

        return $combinations;
    }

    /**
     * @param  array<int, ProductOptionValue>  $combination
     */
    private function sku(string $prefix, int $number, string $handle, array $combination, int $position): string
    {
        if ($handle === 'classic-cotton-t-shirt') {
            $codes = ['White' => 'WHT', 'Black' => 'BLK', 'Navy' => 'NVY'];

            return 'ACME-CTSH-'.$combination[0]->value.'-'.$codes[$combination[1]->value];
        }

        if ($handle === 'gift-card') {
            return 'ACME-GIFT-'.Str::before($combination[0]->value, ' ');
        }

        return sprintf('%s-P%02d-%02d', $prefix, $number, $position + 1);
    }
}
