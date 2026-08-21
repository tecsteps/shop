<?php

namespace Database\Seeders;

use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Models\Collection;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stores = Store::query()->whereIn('handle', ['acme-fashion', 'acme-electronics'])->get()->keyBy('handle');
        $collections = Collection::withoutGlobalScopes()->whereIn('store_id', $stores->pluck('id'))->get()->keyBy(fn (Collection $collection): string => $collection->store_id.':'.$collection->handle);

        foreach ($this->fashionProducts() as $productData) {
            $product = $this->seedProduct($stores['acme-fashion'], $productData);
            $this->assignCollections($product, $productData['collections'], $collections);
        }

        foreach ($this->electronicsProducts() as $productData) {
            $product = $this->seedProduct($stores['acme-electronics'], $productData);
            $this->assignCollections($product, $productData['collections'], $collections);
        }
    }

    private function seedProduct(Store $store, array $productData): Product
    {
        $product = Product::withoutGlobalScopes()->updateOrCreate(
            ['store_id' => $store->getKey(), 'handle' => $productData['handle']],
            ['title' => $productData['title'], 'description' => strip_tags($productData['description_html']), 'description_html' => $productData['description_html'], 'vendor' => $productData['vendor'], 'product_type' => $productData['product_type'], 'tags' => $productData['tags'], 'status' => $productData['status'], 'published_at' => $productData['published_at'], 'sales_count' => 0, 'metadata' => []],
        );

        $optionValues = [];
        foreach ($productData['options'] as $position => $optionData) {
            $option = $product->options()->updateOrCreate(['position' => $position], ['name' => $optionData['name']]);
            $optionValues[$optionData['name']] = [];

            foreach ($optionData['values'] as $valuePosition => $value) {
                $optionValue = $option->values()->updateOrCreate(['position' => $valuePosition], ['value' => $value]);
                $optionValues[$optionData['name']][$value] = $optionValue->getKey();
            }
        }

        foreach ($this->combinations($productData['options']) as $position => $combination) {
            $variantTitle = $combination === [] ? 'Default' : implode(' / ', array_values($combination));
            $price = is_array($productData['price']) ? $productData['price'][$position] : $productData['price'];
            $sku = $productData['sku_prefix'].'-'.str_pad((string) ($position + 1), 3, '0', STR_PAD_LEFT);
            $variant = ProductVariant::query()->updateOrCreate(
                ['product_id' => $product->getKey(), 'position' => $position],
                ['title' => $variantTitle, 'sku' => $sku, 'barcode' => null, 'price_amount' => $price, 'compare_at_amount' => $productData['compare_at'], 'currency' => 'EUR', 'cost_amount' => null, 'weight_grams' => $productData['weight_g'], 'weight_g' => $productData['weight_g'], 'requires_shipping' => $productData['requires_shipping'], 'is_default' => $position === 0, 'status' => $productData['variant_status'], 'metadata' => []],
            );

            $variantOptionIds = [];
            foreach ($combination as $optionName => $value) {
                $variantOptionIds[] = $optionValues[$optionName][$value];
            }
            $variant->optionValues()->sync($variantOptionIds);

            InventoryItem::withoutGlobalScopes()->updateOrCreate(
                ['variant_id' => $variant->getKey()],
                ['store_id' => $store->getKey(), 'quantity_on_hand' => $productData['inventory'], 'quantity_reserved' => 0, 'policy' => $productData['policy']],
            );
        }

        return $product->refresh();
    }

    private function assignCollections(Product $product, array $collectionHandles, $collections): void
    {
        $assignments = [];

        foreach ($collectionHandles as $position => $handle) {
            $collection = $collections->get($product->store_id.':'.$handle);
            if ($collection !== null) {
                $assignments[$collection->getKey()] = ['position' => $position];
            }
        }

        foreach ($collections->filter(fn (Collection $collection): bool => $collection->store_id === $product->store_id) as $collection) {
            if (array_key_exists($collection->getKey(), $assignments)) {
                $collection->products()->syncWithoutDetaching([$product->getKey() => $assignments[$collection->getKey()]]);
            } else {
                $collection->products()->detach($product->getKey());
            }
        }
    }

    private function combinations(array $options): array
    {
        if ($options === []) {
            return [[]];
        }

        $combinations = [[]];
        foreach ($options as $option) {
            $next = [];
            foreach ($combinations as $combination) {
                foreach ($option['values'] as $value) {
                    $next[] = array_merge($combination, [$option['name'] => $value]);
                }
            }
            $combinations = $next;
        }

        return $combinations;
    }

    private function fashionProducts(): array
    {
        return [
            $this->product('Classic Cotton T-Shirt', 'classic-cotton-t-shirt', 'ACME-CTSH', 'Acme Basics', 'T-Shirts', ['new', 'popular'], 2499, null, 200, 15, ['Size' => ['S', 'M', 'L', 'XL'], 'Color' => ['White', 'Black', 'Navy']], ['new-arrivals', 't-shirts']),
            $this->product('Premium Slim Fit Jeans', 'premium-slim-fit-jeans', 'ACME-JEANS', 'Acme Denim', 'Pants', ['new', 'sale'], 7999, 9999, 800, 8, ['Size' => ['28', '30', '32', '34', '36'], 'Color' => ['Blue', 'Black']], ['new-arrivals', 'pants-jeans', 'sale']),
            $this->product('Organic Hoodie', 'organic-hoodie', 'ACME-HOOD', 'Acme Basics', 'Hoodies', ['new', 'trending'], 5999, null, 500, 20, ['Size' => ['S', 'M', 'L', 'XL']], ['new-arrivals']),
            $this->product('Leather Belt', 'leather-belt', 'ACME-BELT', 'Acme Accessories', 'Accessories', ['popular'], 3499, null, 150, 25, ['Size' => ['S/M', 'L/XL'], 'Color' => ['Brown', 'Black']], []),
            $this->product('Running Sneakers', 'running-sneakers', 'ACME-RUN', 'Acme Sport', 'Shoes', ['trending'], 11999, null, 600, 5, ['Size' => ['EU 38', 'EU 39', 'EU 40', 'EU 41', 'EU 42', 'EU 43', 'EU 44'], 'Color' => ['White', 'Black']], ['new-arrivals']),
            $this->product('Graphic Print Tee', 'graphic-print-tee', 'ACME-GTEE', 'Acme Basics', 'T-Shirts', ['new'], 2999, null, 210, 18, ['Size' => ['S', 'M', 'L', 'XL']], ['t-shirts']),
            $this->product('V-Neck Linen Tee', 'v-neck-linen-tee', 'ACME-LTEE', 'Acme Basics', 'T-Shirts', ['popular'], 3499, null, 180, 12, ['Size' => ['S', 'M', 'L'], 'Color' => ['Beige', 'Olive', 'Sky Blue']], ['t-shirts']),
            $this->product('Striped Polo Shirt', 'striped-polo-shirt', 'ACME-POLO', 'Acme Basics', 'T-Shirts', ['sale'], 2799, 3999, 250, 10, ['Size' => ['S', 'M', 'L', 'XL']], ['t-shirts', 'sale']),
            $this->product('Cargo Pants', 'cargo-pants', 'ACME-CARGO', 'Acme Workwear', 'Pants', ['popular'], 5499, null, 700, 14, ['Size' => ['30', '32', '34', '36'], 'Color' => ['Khaki', 'Olive', 'Black']], ['pants-jeans']),
            $this->product('Chino Shorts', 'chino-shorts', 'ACME-SHORT', 'Acme Basics', 'Pants', ['new', 'trending'], 3999, null, 350, 16, ['Size' => ['30', '32', '34', '36'], 'Color' => ['Navy', 'Sand']], ['pants-jeans', 'new-arrivals']),
            $this->product('Wide Leg Trousers', 'wide-leg-trousers', 'ACME-TROUSER', 'Acme Denim', 'Pants', ['sale'], 4999, 6999, 550, 7, ['Size' => ['S', 'M', 'L']], ['pants-jeans', 'sale']),
            $this->product('Wool Scarf', 'wool-scarf', 'ACME-SCARF', 'Acme Accessories', 'Accessories', ['popular'], 2999, null, 120, 30, ['Color' => ['Grey', 'Burgundy', 'Navy']], []),
            $this->product('Canvas Tote Bag', 'canvas-tote-bag', 'ACME-TOTE', 'Acme Accessories', 'Accessories', ['trending'], 1999, null, 300, 40, ['Color' => ['Natural', 'Black']], []),
            $this->product('Bucket Hat', 'bucket-hat', 'ACME-HAT', 'Acme Accessories', 'Accessories', ['new', 'trending'], 2499, null, 80, 22, ['Size' => ['S/M', 'L/XL'], 'Color' => ['Beige', 'Black', 'Olive']], ['new-arrivals']),
            $this->product('Unreleased Winter Jacket', 'unreleased-winter-jacket', 'ACME-WJACKET', 'Acme Outerwear', 'Jackets', ['limited'], 14999, null, 900, 0, ['Size' => ['S', 'M', 'L', 'XL']], [], ProductStatus::Draft, now()->subMonths(6)),
            $this->product('Discontinued Raincoat', 'discontinued-raincoat', 'ACME-RAIN', 'Acme Outerwear', 'Jackets', [], 8999, null, 400, 3, ['Size' => ['M', 'L']], [], ProductStatus::Archived, now()->subMonths(6)),
            $this->product('Limited Edition Sneakers', 'limited-edition-sneakers', 'ACME-LIMITED', 'Acme Sport', 'Shoes', ['limited'], 15999, null, 650, 0, ['Size' => ['EU 40', 'EU 42', 'EU 44']], []),
            $this->product('Backorder Denim Jacket', 'backorder-denim-jacket', 'ACME-BACKORDER', 'Acme Denim', 'Jackets', ['popular'], 9999, null, 750, 0, ['Size' => ['S', 'M', 'L', 'XL']], [], ProductStatus::Active, null, InventoryPolicy::Continue),
            $this->product('Gift Card', 'gift-card', 'ACME-GIFT', 'Acme Fashion', 'Gift Cards', ['popular'], [2500, 5000, 10000], null, 0, 9999, ['Amount' => ['25 EUR', '50 EUR', '100 EUR']], [], ProductStatus::Active, null, InventoryPolicy::Deny, false),
            $this->product('Cashmere Overcoat', 'cashmere-overcoat', 'ACME-CASHMERE', 'Acme Premium', 'Jackets', ['limited', 'new'], 49999, null, 1200, 3, ['Size' => ['S', 'M', 'L'], 'Color' => ['Camel', 'Charcoal']], ['new-arrivals']),
        ];
    }

    private function electronicsProducts(): array
    {
        return [
            $this->product('Pro Laptop 15', 'pro-laptop-15', 'TECH-LAPTOP', 'TechCorp', 'Laptops', ['featured'], [99999, 119999, 149999], null, 1800, 10, ['Storage' => ['256GB', '512GB', '1TB']], ['featured']),
            $this->product('Wireless Headphones', 'wireless-headphones', 'TECH-HEADPHONES', 'AudioMax', 'Audio', ['popular'], 14999, null, 250, 25, ['Color' => ['Black', 'Silver']], ['featured']),
            $this->product('USB-C Cable 2m', 'usb-c-cable-2m', 'TECH-CABLE', 'CablePro', 'Cables', ['popular'], 1299, null, 50, 200, [], ['accessories']),
            $this->product('Mechanical Keyboard', 'mechanical-keyboard', 'TECH-KEYBOARD', 'KeyTech', 'Peripherals', ['featured'], 12999, null, 1100, 15, ['Switch Type' => ['Red', 'Blue', 'Brown']], ['featured']),
            $this->product('Monitor Stand', 'monitor-stand', 'TECH-STAND', 'DeskGear', 'Accessories', ['popular'], 4999, null, 2500, 30, [], ['accessories']),
        ];
    }

    private function product(string $title, string $handle, string $skuPrefix, string $vendor, string $productType, array $tags, int|array $price, ?int $compareAt, int $weight, int $inventory, array $options, array $collections, ProductStatus $status = ProductStatus::Active, $publishedAt = null, InventoryPolicy $policy = InventoryPolicy::Deny, bool $requiresShipping = true): array
    {
        return ['title' => $title, 'handle' => $handle, 'sku_prefix' => $skuPrefix, 'vendor' => $vendor, 'product_type' => $productType, 'tags' => $tags, 'description_html' => '<p>'.str_replace('.', '. ', $title).' is made for comfortable everyday use with thoughtful details.</p>', 'status' => $status, 'published_at' => $publishedAt ?? now(), 'price' => $price, 'compare_at' => $compareAt, 'weight_g' => $weight, 'inventory' => $inventory, 'policy' => $policy, 'requires_shipping' => $requiresShipping, 'variant_status' => $status === ProductStatus::Archived ? VariantStatus::Archived : VariantStatus::Active, 'options' => collect($options)->map(fn (array $values, string $name): array => ['name' => $name, 'values' => $values])->values()->all(), 'collections' => $collections];
    }
}
