<?php

namespace Database\Seeders;

use App\Enums\CollectionStatus;
use App\Enums\CollectionType;
use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Models\Collection;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Support\HandleGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::query()
            ->where('handle', 'shop')
            ->orWhere('handle', 'acme-fashion')
            ->first();

        if ($store === null) {
            return;
        }

        $storeId = (int) $store->getKey();

        $catalog = [
            ['title' => 'Classic Tee', 'type' => 'Apparel', 'options' => [['Size', ['S', 'M', 'L']], ['Color', ['Black', 'White']]], 'price' => 2500],
            ['title' => 'Summer Dress', 'type' => 'Apparel', 'options' => [['Size', ['S', 'M', 'L', 'XL']]], 'price' => 5900],
            ['title' => 'Denim Jacket', 'type' => 'Apparel', 'options' => [['Size', ['M', 'L']], ['Color', ['Blue', 'Black']]], 'price' => 8900],
            ['title' => 'Running Sneakers', 'type' => 'Footwear', 'options' => [['Size', ['8', '9', '10', '11']]], 'price' => 11900],
            ['title' => 'Leather Wallet', 'type' => 'Accessories', 'options' => [['Color', ['Brown', 'Black']]], 'price' => 4500],
            ['title' => 'Canvas Tote', 'type' => 'Accessories', 'options' => [['Color', ['Natural', 'Navy']]], 'price' => 2200],
            ['title' => 'Wool Beanie', 'type' => 'Accessories', 'options' => [['Color', ['Grey', 'Black', 'Cream']]], 'price' => 1800],
            ['title' => 'Ceramic Mug', 'type' => 'Home', 'options' => [['Color', ['White', 'Terracotta']]], 'price' => 1500],
            ['title' => 'Scented Candle', 'type' => 'Home', 'options' => [['Scent', ['Vanilla', 'Pine', 'Citrus']]], 'price' => 2900],
            ['title' => 'Cotton Socks', 'type' => 'Apparel', 'options' => [['Size', ['S', 'M', 'L']], ['Color', ['White', 'Black']]], 'price' => 1200],
        ];

        $featured = Collection::query()->create([
            'store_id' => $storeId,
            'title' => 'Featured',
            'handle' => HandleGenerator::unique(Collection::class, $storeId, 'Featured'),
            'type' => CollectionType::Manual->value,
            'status' => CollectionStatus::Active->value,
            'description_html' => '<p>Featured picks from the team.</p>',
        ]);

        $createdProducts = [];

        foreach ($catalog as $entry) {
            $product = Product::query()->create([
                'store_id' => $storeId,
                'title' => $entry['title'],
                'handle' => HandleGenerator::unique(Product::class, $storeId, $entry['title']),
                'status' => ProductStatus::Active->value,
                'description_html' => '<p>'.$entry['title'].' from Acme Fashion.</p>',
                'vendor' => 'Acme',
                'product_type' => $entry['type'],
                'tags' => [strtolower($entry['type'])],
                'published_at' => now(),
            ]);

            $valueGroups = [];

            foreach ($entry['options'] as $optionIndex => [$name, $values]) {
                $option = ProductOption::query()->create([
                    'product_id' => $product->getKey(),
                    'name' => $name,
                    'position' => $optionIndex,
                ]);

                $valueIds = [];

                foreach ($values as $valueIndex => $value) {
                    $pov = ProductOptionValue::query()->create([
                        'product_option_id' => $option->getKey(),
                        'value' => $value,
                        'position' => $valueIndex,
                    ]);
                    $valueIds[] = (int) $pov->getKey();
                }

                $valueGroups[] = $valueIds;
            }

            $combos = $this->cartesianProduct($valueGroups);
            $combos = array_slice($combos, 0, 4);

            $position = 0;

            foreach ($combos as $combo) {
                $variant = ProductVariant::query()->create([
                    'product_id' => $product->getKey(),
                    'sku' => strtoupper(Str::slug($entry['title']).'-'.Str::random(4)),
                    'price_amount' => $entry['price'],
                    'currency' => 'USD',
                    'requires_shipping' => 1,
                    'is_default' => $position === 0 ? 1 : 0,
                    'position' => $position,
                    'status' => VariantStatus::Active->value,
                ]);

                if (! empty($combo)) {
                    $variant->optionValues()->sync($combo);
                }

                InventoryItem::query()->create([
                    'store_id' => $storeId,
                    'variant_id' => $variant->getKey(),
                    'quantity_on_hand' => 50,
                    'quantity_reserved' => 0,
                    'policy' => InventoryPolicy::Deny->value,
                ]);

                $position++;
            }

            $createdProducts[] = $product;
        }

        foreach (array_slice($createdProducts, 0, 5) as $index => $product) {
            $featured->products()->attach($product->getKey(), ['position' => $index]);
        }
    }

    /**
     * @param  array<int, array<int, int>>  $groups
     * @return array<int, array<int, int>>
     */
    protected function cartesianProduct(array $groups): array
    {
        $groups = array_values(array_filter($groups, fn (array $g): bool => $g !== []));

        if ($groups === []) {
            return [[]];
        }

        $result = [[]];

        foreach ($groups as $group) {
            $next = [];

            foreach ($result as $partial) {
                foreach ($group as $value) {
                    $next[] = array_merge($partial, [$value]);
                }
            }

            $result = $next;
        }

        return $result;
    }
}
