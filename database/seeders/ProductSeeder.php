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
            $fashion = Store::query()->where('handle', 'acme-fashion')->sole();
            $electronics = Store::query()->where('handle', 'acme-electronics')->sole();

            foreach ($this->fashionProducts() as $definition) {
                $this->seedProduct($fashion, $definition);
            }

            foreach ($this->electronicsProducts() as $definition) {
                $this->seedProduct($electronics, $definition);
            }
        });
    }

    /** @param array<string, mixed> $definition */
    private function seedProduct(Store $store, array $definition): void
    {
        $product = Product::withoutGlobalScopes()->updateOrCreate(
            ['store_id' => $store->id, 'handle' => $definition['handle']],
            [
                'title' => $definition['title'],
                'status' => $definition['status'] ?? 'active',
                'description_html' => '<p>'.$definition['description'].'</p>',
                'vendor' => $definition['vendor'],
                'product_type' => $definition['type'],
                'tags' => $definition['tags'],
                'published_at' => ($definition['status'] ?? 'active') === 'draft' ? null : ($definition['published_at'] ?? now()),
            ],
        );

        $optionValueIds = [];
        foreach ($definition['options'] as $optionPosition => $optionDefinition) {
            $option = ProductOption::query()->updateOrCreate(
                ['product_id' => $product->id, 'position' => $optionPosition],
                ['name' => $optionDefinition[0]],
            );
            foreach ($optionDefinition[1] as $valuePosition => $value) {
                $optionValue = ProductOptionValue::query()->updateOrCreate(
                    ['product_option_id' => $option->id, 'position' => $valuePosition],
                    ['value' => $value],
                );
                $optionValueIds[$optionPosition][$valuePosition] = $optionValue->id;
            }
        }

        $combinations = $this->combinations(array_map(fn (array $option): array => $option[1], $definition['options']));
        if ($combinations === []) {
            $combinations = [[]];
        }

        foreach ($combinations as $position => $combination) {
            $price = is_array($definition['price']) ? $definition['price'][$position] : $definition['price'];
            $sku = $definition['skus'][$position] ?? $this->sku($definition['handle'], $combination, $position);
            $variant = ProductVariant::query()->updateOrCreate(
                ['product_id' => $product->id, 'position' => $position],
                [
                    'sku' => $sku,
                    'barcode' => null,
                    'price_amount' => $price,
                    'compare_at_amount' => $definition['compare_at'] ?? null,
                    'currency' => 'EUR',
                    'weight_g' => $definition['weight'],
                    'requires_shipping' => $definition['shipping'] ?? true,
                    'is_default' => $position === 0,
                    'status' => 'active',
                ],
            );

            $variant->optionValues()->sync(collect($combination)->keys()->map(
                fn (int $optionPosition): int => $optionValueIds[$optionPosition][array_search($combination[$optionPosition], $definition['options'][$optionPosition][1], true)],
            )->all());

            InventoryItem::withoutGlobalScopes()->updateOrCreate(
                ['variant_id' => $variant->id],
                ['store_id' => $store->id, 'quantity_on_hand' => $definition['inventory'], 'quantity_reserved' => 0, 'policy' => $definition['policy'] ?? 'deny'],
            );
        }

        $collections = Collection::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereIn('handle', $definition['collections'])
            ->get();
        foreach ($collections as $collection) {
            $assignments = $this->collectionAssignments($store, $collection->handle);
            $position = array_search($product->handle, $assignments, true);
            if ($position !== false) {
                $collection->products()->syncWithoutDetaching([$product->id => ['position' => $position]]);
            }
        }
    }

    /** @param list<array{0: string, 1: list<string>}> $options
     * @return list<list<string>>
     */
    private function combinations(array $options): array
    {
        $combinations = [[]];
        foreach ($options as $option) {
            $next = [];
            foreach ($combinations as $combination) {
                foreach ($option as $value) {
                    $next[] = [...$combination, $value];
                }
            }
            $combinations = $next;
        }

        return $options === [] ? [] : $combinations;
    }

    /** @param list<string> $combination */
    private function sku(string $handle, array $combination, int $position): string
    {
        $suffix = $combination === [] ? 'DEFAULT' : collect($combination)->map(
            fn (string $value): string => Str::upper(Str::of($value)->replaceMatches('/[^A-Za-z0-9]/', '')->substr(0, 5)->toString()),
        )->implode('-');

        return 'ACME-'.Str::upper(Str::of($handle)->replace('-', '')->substr(0, 8)->toString()).'-'.$suffix.'-'.($position + 1);
    }

    /** @return list<string> */
    private function collectionAssignments(Store $store, string $handle): array
    {
        $fashion = [
            'new-arrivals' => ['classic-cotton-t-shirt', 'premium-slim-fit-jeans', 'organic-hoodie', 'running-sneakers', 'chino-shorts', 'bucket-hat', 'cashmere-overcoat'],
            't-shirts' => ['classic-cotton-t-shirt', 'graphic-print-tee', 'v-neck-linen-tee', 'striped-polo-shirt'],
            'pants-jeans' => ['premium-slim-fit-jeans', 'cargo-pants', 'chino-shorts', 'wide-leg-trousers'],
            'sale' => ['premium-slim-fit-jeans', 'striped-polo-shirt', 'wide-leg-trousers'],
        ];
        $electronics = [
            'featured' => ['pro-laptop-15', 'wireless-headphones', 'mechanical-keyboard'],
            'accessories' => ['usb-c-cable-2m', 'monitor-stand'],
        ];

        return ($store->handle === 'acme-fashion' ? $fashion : $electronics)[$handle] ?? [];
    }

    /** @return list<array<string, mixed>> */
    private function fashionProducts(): array
    {
        return [
            $this->product('Classic Cotton T-Shirt', 'classic-cotton-t-shirt', 'Acme Basics', 'T-Shirts', ['new', 'popular'], 'A timeless classic cotton t-shirt. Comfortable, breathable, and perfect for everyday wear.', [['Size', ['S', 'M', 'L', 'XL']], ['Color', ['White', 'Black', 'Navy']]], 2499, 200, 15, ['new-arrivals', 't-shirts']),
            $this->product('Premium Slim Fit Jeans', 'premium-slim-fit-jeans', 'Acme Denim', 'Pants', ['new', 'sale'], 'Slim fit jeans crafted from premium stretch denim. Comfortable all-day wear with a modern silhouette.', [['Size', ['28', '30', '32', '34', '36']], ['Color', ['Blue', 'Black']]], 7999, 800, 8, ['new-arrivals', 'pants-jeans', 'sale'], ['compare_at' => 9999]),
            $this->product('Organic Hoodie', 'organic-hoodie', 'Acme Basics', 'Hoodies', ['new', 'trending'], 'Made from 100% organic cotton. Warm, soft, and sustainably produced.', [['Size', ['S', 'M', 'L', 'XL']]], 5999, 500, 20, ['new-arrivals']),
            $this->product('Leather Belt', 'leather-belt', 'Acme Accessories', 'Accessories', ['popular'], 'Genuine leather belt with brushed metal buckle. A wardrobe essential.', [['Size', ['S/M', 'L/XL']], ['Color', ['Brown', 'Black']]], 3499, 150, 25),
            $this->product('Running Sneakers', 'running-sneakers', 'Acme Sport', 'Shoes', ['trending'], 'Lightweight running sneakers with responsive cushioning and breathable mesh upper.', [['Size', ['EU 38', 'EU 39', 'EU 40', 'EU 41', 'EU 42', 'EU 43', 'EU 44']], ['Color', ['White', 'Black']]], 11999, 600, 5, ['new-arrivals']),
            $this->product('Graphic Print Tee', 'graphic-print-tee', 'Acme Basics', 'T-Shirts', ['new'], 'Bold graphic print on soft cotton. Express yourself with this statement piece.', [['Size', ['S', 'M', 'L', 'XL']]], 2999, 210, 18, ['t-shirts']),
            $this->product('V-Neck Linen Tee', 'v-neck-linen-tee', 'Acme Basics', 'T-Shirts', ['popular'], 'Lightweight linen blend v-neck. Perfect for warm summer days.', [['Size', ['S', 'M', 'L']], ['Color', ['Beige', 'Olive', 'Sky Blue']]], 3499, 180, 12, ['t-shirts']),
            $this->product('Striped Polo Shirt', 'striped-polo-shirt', 'Acme Basics', 'T-Shirts', ['sale'], 'Classic striped polo with a modern relaxed fit. Knitted collar and two-button placket.', [['Size', ['S', 'M', 'L', 'XL']]], 2799, 250, 10, ['t-shirts', 'sale'], ['compare_at' => 3999]),
            $this->product('Cargo Pants', 'cargo-pants', 'Acme Workwear', 'Pants', ['popular'], 'Utility cargo pants with multiple pockets. Durable cotton twill construction.', [['Size', ['30', '32', '34', '36']], ['Color', ['Khaki', 'Olive', 'Black']]], 5499, 700, 14, ['pants-jeans']),
            $this->product('Chino Shorts', 'chino-shorts', 'Acme Basics', 'Pants', ['new', 'trending'], 'Tailored chino shorts. Comfortable stretch fabric with a clean silhouette.', [['Size', ['30', '32', '34', '36']], ['Color', ['Navy', 'Sand']]], 3999, 350, 16, ['pants-jeans', 'new-arrivals']),
            $this->product('Wide Leg Trousers', 'wide-leg-trousers', 'Acme Denim', 'Pants', ['sale'], 'Relaxed wide leg trousers with a high waist. Flowing drape in premium woven fabric.', [['Size', ['S', 'M', 'L']]], 4999, 550, 7, ['pants-jeans', 'sale'], ['compare_at' => 6999]),
            $this->product('Wool Scarf', 'wool-scarf', 'Acme Accessories', 'Accessories', ['popular'], 'Warm merino wool scarf. Soft hand feel, naturally breathable and temperature regulating.', [['Color', ['Grey', 'Burgundy', 'Navy']]], 2999, 120, 30),
            $this->product('Canvas Tote Bag', 'canvas-tote-bag', 'Acme Accessories', 'Accessories', ['trending'], 'Heavy-duty canvas tote bag with reinforced handles. Spacious enough for daily essentials.', [['Color', ['Natural', 'Black']]], 1999, 300, 40),
            $this->product('Bucket Hat', 'bucket-hat', 'Acme Accessories', 'Accessories', ['new', 'trending'], 'Lightweight bucket hat for sun protection. Packable design, washed cotton twill.', [['Size', ['S/M', 'L/XL']], ['Color', ['Beige', 'Black', 'Olive']]], 2499, 80, 22, ['new-arrivals']),
            $this->product('Unreleased Winter Jacket', 'unreleased-winter-jacket', 'Acme Outerwear', 'Jackets', ['limited'], 'Upcoming winter collection piece. Insulated puffer jacket with water-resistant shell.', [['Size', ['S', 'M', 'L', 'XL']]], 14999, 900, 0, [], ['status' => 'draft']),
            $this->product('Discontinued Raincoat', 'discontinued-raincoat', 'Acme Outerwear', 'Jackets', [], 'Lightweight waterproof raincoat. This product has been discontinued.', [['Size', ['M', 'L']]], 8999, 400, 3, [], ['status' => 'archived', 'published_at' => now()->subMonths(6)]),
            $this->product('Limited Edition Sneakers', 'limited-edition-sneakers', 'Acme Sport', 'Shoes', ['limited'], 'Limited edition collaboration sneakers. Once they are gone, they are gone.', [['Size', ['EU 40', 'EU 42', 'EU 44']]], 15999, 650, 0),
            $this->product('Backorder Denim Jacket', 'backorder-denim-jacket', 'Acme Denim', 'Jackets', ['popular'], 'Classic denim jacket. Currently on backorder - ships within 2-3 weeks.', [['Size', ['S', 'M', 'L', 'XL']]], 9999, 750, 0, [], ['policy' => 'continue']),
            $this->product('Gift Card', 'gift-card', 'Acme Fashion', 'Gift Cards', ['popular'], 'Digital gift card delivered via email. The perfect gift when you are not sure what to choose.', [['Amount', ['25 EUR', '50 EUR', '100 EUR']]], [2500, 5000, 10000], 0, 9999, [], ['shipping' => false, 'skus' => ['ACME-GIFT-25', 'ACME-GIFT-50', 'ACME-GIFT-100']]),
            $this->product('Cashmere Overcoat', 'cashmere-overcoat', 'Acme Premium', 'Jackets', ['limited', 'new'], 'Luxurious cashmere-blend overcoat. Impeccable tailoring with silk lining.', [['Size', ['S', 'M', 'L']], ['Color', ['Camel', 'Charcoal']]], 49999, 1200, 3, ['new-arrivals']),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function electronicsProducts(): array
    {
        return [
            $this->product('Pro Laptop 15', 'pro-laptop-15', 'TechCorp', 'Laptops', ['featured'], 'A professional laptop built for demanding workloads.', [['Storage', ['256GB', '512GB', '1TB']]], [99999, 119999, 149999], 1800, 10, ['featured']),
            $this->product('Wireless Headphones', 'wireless-headphones', 'AudioMax', 'Audio', ['featured'], 'Premium wireless headphones with active noise cancellation.', [['Color', ['Black', 'Silver']]], 14999, 250, 25, ['featured']),
            $this->product('USB-C Cable 2m', 'usb-c-cable-2m', 'CablePro', 'Cables', ['accessory'], 'Durable two metre USB-C charging and data cable.', [], 1299, 50, 200, ['accessories']),
            $this->product('Mechanical Keyboard', 'mechanical-keyboard', 'KeyTech', 'Peripherals', ['featured'], 'Full-size mechanical keyboard for work and play.', [['Switch Type', ['Red', 'Blue', 'Brown']]], 12999, 1100, 15, ['featured']),
            $this->product('Monitor Stand', 'monitor-stand', 'DeskGear', 'Accessories', ['accessory'], 'Ergonomic monitor stand with storage space.', [], 4999, 2500, 30, ['accessories']),
        ];
    }

    /** @param list<string> $tags
     * @param  list<array{0: string, 1: list<string>}>  $options
     * @param  int|list<int>  $price
     * @param  list<string>  $collections
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function product(string $title, string $handle, string $vendor, string $type, array $tags, string $description, array $options, int|array $price, int $weight, int $inventory, array $collections = [], array $extra = []): array
    {
        return [...compact('title', 'handle', 'vendor', 'type', 'tags', 'description', 'options', 'price', 'weight', 'inventory', 'collections'), ...$extra];
    }
}
