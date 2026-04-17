<?php

namespace Database\Seeders;

use App\Enums\InventoryPolicy;
use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Models\Collection;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::where('handle', 'acme-fashion')->firstOrFail();

        $products = $this->getProductDefinitions();

        $createdProducts = [];
        foreach ($products as $index => $definition) {
            $product = Product::withoutGlobalScopes()->create([
                'store_id' => $store->id,
                'title' => $definition['title'],
                'handle' => $definition['handle'],
                'description_html' => $definition['description_html'],
                'status' => $definition['status'],
                'vendor' => $definition['vendor'] ?? 'Acme Fashion',
                'product_type' => $definition['product_type'] ?? 'Apparel',
                'tags' => $definition['tags'] ?? [],
                'published_at' => $definition['status'] === ProductStatus::Active ? now() : null,
            ]);

            ProductMedia::create([
                'product_id' => $product->id,
                'type' => MediaType::Image,
                'url' => 'https://placehold.co/800x600?text='.urlencode($product->title),
                'alt_text' => $product->title,
                'position' => 0,
                'width' => 800,
                'height' => 600,
                'status' => MediaStatus::Ready,
            ]);

            $optionValueMap = [];
            foreach ($definition['options'] as $optionPosition => $option) {
                $productOption = ProductOption::create([
                    'product_id' => $product->id,
                    'name' => $option['name'],
                    'position' => $optionPosition,
                ]);

                foreach ($option['values'] as $valuePosition => $value) {
                    $optionValue = ProductOptionValue::create([
                        'product_option_id' => $productOption->id,
                        'value' => $value,
                        'position' => $valuePosition,
                    ]);
                    $optionValueMap[$option['name']][$value] = $optionValue->id;
                }
            }

            $variantCombinations = $this->generateVariantCombinations($definition['options']);
            $isFirst = true;

            foreach ($variantCombinations as $variantPosition => $combination) {
                $variantTitle = implode(' / ', array_values($combination));

                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'title' => $variantTitle,
                    'sku' => strtoupper(substr($definition['handle'], 0, 8)).'-'.str_pad($variantPosition + 1, 3, '0', STR_PAD_LEFT),
                    'price_amount' => $definition['price'],
                    'compare_at_price_amount' => $definition['compare_at_price'] ?? null,
                    'cost_amount' => (int) ($definition['price'] * 0.4),
                    'requires_shipping' => true,
                    'is_default' => $isFirst,
                    'status' => VariantStatus::Active,
                    'position' => $variantPosition,
                ]);

                $optionValueIds = [];
                foreach ($combination as $optionName => $optionValue) {
                    $optionValueIds[] = $optionValueMap[$optionName][$optionValue];
                }
                $variant->optionValues()->attach($optionValueIds);

                $inventoryQuantity = $definition['inventory'] ?? 25;
                $inventoryPolicyValue = $definition['inventory_policy'] ?? InventoryPolicy::Deny;

                InventoryItem::withoutGlobalScopes()->create([
                    'store_id' => $store->id,
                    'variant_id' => $variant->id,
                    'quantity_on_hand' => $inventoryQuantity,
                    'quantity_reserved' => 0,
                    'policy' => $inventoryPolicyValue,
                ]);

                $isFirst = false;
            }

            $createdProducts[$index + 1] = $product;
        }

        $this->assignCollections($store, $createdProducts);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getProductDefinitions(): array
    {
        return [
            // Product #1
            [
                'title' => 'Classic Cotton T-Shirt',
                'handle' => 'classic-cotton-t-shirt',
                'description_html' => 'A timeless cotton t-shirt, perfect for everyday wear. Made from 100% organic cotton.',
                'status' => ProductStatus::Active,
                'product_type' => 'T-Shirt',
                'tags' => ['cotton', 't-shirt', 'basics'],
                'price' => 2499,
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                    ['name' => 'Color', 'values' => ['Black', 'White', 'Navy']],
                ],
            ],
            // Product #2
            [
                'title' => 'Premium Slim Fit Jeans',
                'handle' => 'premium-slim-fit-jeans',
                'description_html' => 'Premium slim fit jeans crafted from stretch denim for ultimate comfort.',
                'status' => ProductStatus::Active,
                'product_type' => 'Jeans',
                'tags' => ['jeans', 'denim', 'premium'],
                'price' => 7999,
                'compare_at_price' => 9999,
                'options' => [
                    ['name' => 'Size', 'values' => ['28', '30', '32', '34']],
                    ['name' => 'Color', 'values' => ['Blue', 'Black']],
                ],
            ],
            // Product #3
            [
                'title' => 'Graphic Print T-Shirt',
                'handle' => 'graphic-print-t-shirt',
                'description_html' => 'Bold graphic print t-shirt with a modern design.',
                'status' => ProductStatus::Active,
                'product_type' => 'T-Shirt',
                'tags' => ['t-shirt', 'graphic', 'trendy'],
                'price' => 2999,
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L']],
                    ['name' => 'Color', 'values' => ['White', 'Grey']],
                ],
            ],
            // Product #4
            [
                'title' => 'Linen Summer Shirt',
                'handle' => 'linen-summer-shirt',
                'description_html' => 'Lightweight linen shirt for warm summer days.',
                'status' => ProductStatus::Active,
                'product_type' => 'Shirt',
                'tags' => ['linen', 'summer', 'shirt'],
                'price' => 4999,
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                    ['name' => 'Color', 'values' => ['White', 'Sky Blue']],
                ],
            ],
            // Product #5
            [
                'title' => 'Wool Blend Sweater',
                'handle' => 'wool-blend-sweater',
                'description_html' => 'Cozy wool blend sweater for colder days.',
                'status' => ProductStatus::Active,
                'product_type' => 'Sweater',
                'tags' => ['wool', 'sweater', 'winter'],
                'price' => 5999,
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L']],
                    ['name' => 'Color', 'values' => ['Charcoal', 'Burgundy', 'Navy']],
                ],
            ],
            // Product #6
            [
                'title' => 'Chino Shorts',
                'handle' => 'chino-shorts',
                'description_html' => 'Classic chino shorts for a relaxed summer look.',
                'status' => ProductStatus::Active,
                'product_type' => 'Shorts',
                'tags' => ['shorts', 'chino', 'summer'],
                'price' => 3499,
                'options' => [
                    ['name' => 'Size', 'values' => ['28', '30', '32', '34']],
                    ['name' => 'Color', 'values' => ['Khaki', 'Navy']],
                ],
            ],
            // Product #7
            [
                'title' => 'V-Neck T-Shirt',
                'handle' => 'v-neck-t-shirt',
                'description_html' => 'Soft v-neck t-shirt in a relaxed fit.',
                'status' => ProductStatus::Active,
                'product_type' => 'T-Shirt',
                'tags' => ['t-shirt', 'v-neck', 'basics'],
                'price' => 2299,
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                    ['name' => 'Color', 'values' => ['Black', 'White']],
                ],
            ],
            // Product #8
            [
                'title' => 'Denim Jacket',
                'handle' => 'denim-jacket',
                'description_html' => 'Classic denim jacket with a modern cut.',
                'status' => ProductStatus::Active,
                'product_type' => 'Jacket',
                'tags' => ['jacket', 'denim', 'outerwear'],
                'price' => 8999,
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                    ['name' => 'Color', 'values' => ['Blue', 'Black']],
                ],
            ],
            // Product #9
            [
                'title' => 'Jogger Pants',
                'handle' => 'jogger-pants',
                'description_html' => 'Comfortable jogger pants for casual wear and light exercise.',
                'status' => ProductStatus::Active,
                'product_type' => 'Pants',
                'tags' => ['pants', 'jogger', 'casual'],
                'price' => 3999,
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L']],
                    ['name' => 'Color', 'values' => ['Black', 'Grey']],
                ],
            ],
            // Product #10
            [
                'title' => 'Polo Shirt',
                'handle' => 'polo-shirt',
                'description_html' => 'Classic polo shirt with embroidered logo.',
                'status' => ProductStatus::Active,
                'product_type' => 'Shirt',
                'tags' => ['polo', 'shirt', 'classic'],
                'price' => 3499,
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                    ['name' => 'Color', 'values' => ['White', 'Navy', 'Red']],
                ],
            ],
            // Product #11
            [
                'title' => 'Casual Button-Down Shirt',
                'handle' => 'casual-button-down-shirt',
                'description_html' => 'Versatile button-down shirt for casual and semi-formal occasions.',
                'status' => ProductStatus::Active,
                'product_type' => 'Shirt',
                'tags' => ['shirt', 'button-down', 'casual'],
                'price' => 4499,
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L']],
                    ['name' => 'Color', 'values' => ['White', 'Light Blue']],
                ],
            ],
            // Product #12
            [
                'title' => 'Heavyweight Hoodie',
                'handle' => 'heavyweight-hoodie',
                'description_html' => 'A warm, heavyweight hoodie with kangaroo pocket.',
                'status' => ProductStatus::Active,
                'product_type' => 'Hoodie',
                'tags' => ['hoodie', 'heavyweight', 'winter'],
                'price' => 5499,
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                    ['name' => 'Color', 'values' => ['Black', 'Grey', 'Navy']],
                ],
            ],
            // Product #13
            [
                'title' => 'Stretch Cargo Pants',
                'handle' => 'stretch-cargo-pants',
                'description_html' => 'Functional cargo pants with stretch comfort.',
                'status' => ProductStatus::Active,
                'product_type' => 'Pants',
                'tags' => ['pants', 'cargo', 'stretch'],
                'price' => 4999,
                'options' => [
                    ['name' => 'Size', 'values' => ['28', '30', '32', '34']],
                    ['name' => 'Color', 'values' => ['Olive', 'Black']],
                ],
            ],
            // Product #14
            [
                'title' => 'Striped Crew Neck T-Shirt',
                'handle' => 'striped-crew-neck-t-shirt',
                'description_html' => 'Nautical-inspired striped crew neck t-shirt.',
                'status' => ProductStatus::Active,
                'product_type' => 'T-Shirt',
                'tags' => ['t-shirt', 'striped', 'nautical'],
                'price' => 2799,
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L']],
                    ['name' => 'Color', 'values' => ['Navy/White', 'Red/White']],
                ],
            ],
            // Product #15 - DRAFT (should NOT appear on storefront)
            [
                'title' => 'Upcoming Limited Edition Jacket',
                'handle' => 'upcoming-limited-edition-jacket',
                'description_html' => 'A limited edition jacket coming soon.',
                'status' => ProductStatus::Draft,
                'product_type' => 'Jacket',
                'tags' => ['jacket', 'limited-edition', 'upcoming'],
                'price' => 12999,
                'options' => [
                    ['name' => 'Size', 'values' => ['M', 'L']],
                ],
            ],
            // Product #16
            [
                'title' => 'Athletic Tank Top',
                'handle' => 'athletic-tank-top',
                'description_html' => 'Moisture-wicking athletic tank top for workouts.',
                'status' => ProductStatus::Active,
                'product_type' => 'T-Shirt',
                'tags' => ['tank-top', 'athletic', 'workout'],
                'price' => 1999,
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L']],
                    ['name' => 'Color', 'values' => ['Black', 'White']],
                ],
            ],
            // Product #17 - Active, inventory 0, policy deny (sold out)
            [
                'title' => 'Sold Out Vintage Tee',
                'handle' => 'sold-out-vintage-tee',
                'description_html' => 'A popular vintage tee that is currently sold out.',
                'status' => ProductStatus::Active,
                'product_type' => 'T-Shirt',
                'tags' => ['t-shirt', 'vintage', 'sold-out'],
                'price' => 3499,
                'inventory' => 0,
                'inventory_policy' => InventoryPolicy::Deny,
                'options' => [
                    ['name' => 'Size', 'values' => ['M', 'L']],
                ],
            ],
            // Product #18 - Active, inventory 0, policy continue (backorder)
            [
                'title' => 'Backorder Organic Hoodie',
                'handle' => 'backorder-organic-hoodie',
                'description_html' => 'Organic cotton hoodie available for backorder.',
                'status' => ProductStatus::Active,
                'product_type' => 'Hoodie',
                'tags' => ['hoodie', 'organic', 'backorder'],
                'price' => 6499,
                'inventory' => 0,
                'inventory_policy' => InventoryPolicy::Continue,
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L']],
                    ['name' => 'Color', 'values' => ['Forest Green', 'Oatmeal']],
                ],
            ],
            // Product #19
            [
                'title' => 'Relaxed Fit Bermuda Shorts',
                'handle' => 'relaxed-fit-bermuda-shorts',
                'description_html' => 'Relaxed fit bermuda shorts for the warm season.',
                'status' => ProductStatus::Active,
                'product_type' => 'Shorts',
                'tags' => ['shorts', 'bermuda', 'relaxed'],
                'price' => 3299,
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L']],
                    ['name' => 'Color', 'values' => ['Sand', 'Navy']],
                ],
            ],
            // Product #20
            [
                'title' => 'Performance Running T-Shirt',
                'handle' => 'performance-running-t-shirt',
                'description_html' => 'High-performance running t-shirt with reflective details.',
                'status' => ProductStatus::Active,
                'product_type' => 'T-Shirt',
                'tags' => ['t-shirt', 'running', 'performance'],
                'price' => 3999,
                'compare_at_price' => 4999,
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                    ['name' => 'Color', 'values' => ['Neon Yellow', 'Black']],
                ],
            ],
        ];
    }

    /**
     * @param  array<int, array{name: string, values: array<int, string>}>  $options
     * @return array<int, array<string, string>>
     */
    private function generateVariantCombinations(array $options): array
    {
        $combinations = [[]];

        foreach ($options as $option) {
            $newCombinations = [];
            foreach ($combinations as $combination) {
                foreach ($option['values'] as $value) {
                    $newCombinations[] = array_merge($combination, [$option['name'] => $value]);
                }
            }
            $combinations = $newCombinations;
        }

        return $combinations;
    }

    /**
     * @param  array<int, Product>  $products
     */
    private function assignCollections(Store $store, array $products): void
    {
        $tshirtCollection = Collection::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('handle', 't-shirts')
            ->first();

        $newArrivalsCollection = Collection::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('handle', 'new-arrivals')
            ->first();

        $saleCollection = Collection::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('handle', 'sale')
            ->first();

        if ($tshirtCollection) {
            // T-shirt products: #1, #3, #7, #14, #16, #17
            $tshirtProductIds = collect([1, 3, 7, 14, 16, 17])
                ->filter(fn ($id) => isset($products[$id]))
                ->mapWithKeys(fn ($id, $index) => [$products[$id]->id => ['position' => $index]]);
            $tshirtCollection->products()->attach($tshirtProductIds);
        }

        if ($newArrivalsCollection) {
            // Recent products: #18, #19, #20
            $newArrivalIds = collect([18, 19, 20])
                ->filter(fn ($id) => isset($products[$id]))
                ->mapWithKeys(fn ($id, $index) => [$products[$id]->id => ['position' => $index]]);
            $newArrivalsCollection->products()->attach($newArrivalIds);
        }

        if ($saleCollection) {
            // Sale products (those with compare_at_price): #2, #20
            $saleProductIds = collect([2, 20])
                ->filter(fn ($id) => isset($products[$id]))
                ->mapWithKeys(fn ($id, $index) => [$products[$id]->id => ['position' => $index]]);
            $saleCollection->products()->attach($saleProductIds);
        }
    }
}
