<?php

namespace Database\Seeders;

use App\Enums\CollectionStatus;
use App\Enums\CollectionType;
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
use App\Support\HandleGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::query()->where('handle', 'shop')->first();

        if ($store === null) {
            return;
        }

        $storeId = (int) $store->getKey();

        $catalog = [
            ['title' => 'Classic Tee', 'type' => 'Apparel', 'options' => [['Size', ['S', 'M', 'L']], ['Color', ['Black', 'White']]], 'price' => 2500, 'tags' => ['apparel', 'tops', 'new-arrival']],
            ['title' => 'Summer Dress', 'type' => 'Apparel', 'options' => [['Size', ['S', 'M', 'L', 'XL']]], 'price' => 5900, 'tags' => ['apparel', 'sale']],
            ['title' => 'Denim Jacket', 'type' => 'Apparel', 'options' => [['Size', ['M', 'L']], ['Color', ['Blue', 'Black']]], 'price' => 8900, 'tags' => ['apparel', 'outerwear']],
            ['title' => 'Running Sneakers', 'type' => 'Footwear', 'options' => [['Size', ['8', '9', '10', '11']]], 'price' => 11900, 'tags' => ['footwear', 'sport', 'new-arrival']],
            ['title' => 'Leather Wallet', 'type' => 'Accessories', 'options' => [['Color', ['Brown', 'Black']]], 'price' => 4500, 'tags' => ['accessories']],
            ['title' => 'Canvas Tote', 'type' => 'Accessories', 'options' => [['Color', ['Natural', 'Navy']]], 'price' => 2200, 'tags' => ['accessories', 'sale']],
            ['title' => 'Wool Beanie', 'type' => 'Accessories', 'options' => [['Color', ['Grey', 'Black', 'Cream']]], 'price' => 1800, 'tags' => ['accessories']],
            ['title' => 'Ceramic Mug', 'type' => 'Home', 'options' => [['Color', ['White', 'Terracotta']]], 'price' => 1500, 'tags' => ['home', 'clearance']],
            ['title' => 'Scented Candle', 'type' => 'Home', 'options' => [['Scent', ['Vanilla', 'Pine', 'Citrus']]], 'price' => 2900, 'tags' => ['home']],
            ['title' => 'Cotton Socks', 'type' => 'Apparel', 'options' => [['Size', ['S', 'M', 'L']], ['Color', ['White', 'Black']]], 'price' => 1200, 'tags' => ['apparel']],
            ['title' => 'Rain Jacket', 'type' => 'Apparel', 'options' => [['Size', ['S', 'M', 'L']]], 'price' => 13900, 'tags' => ['apparel', 'outerwear']],
            ['title' => 'Linen Shirt', 'type' => 'Apparel', 'options' => [['Size', ['M', 'L', 'XL']], ['Color', ['White', 'Sky']]], 'price' => 4900, 'tags' => ['apparel', 'new-arrival']],
            ['title' => 'Leather Belt', 'type' => 'Accessories', 'options' => [['Size', ['S', 'M', 'L']]], 'price' => 3500, 'tags' => ['accessories']],
            ['title' => 'Sunglasses', 'type' => 'Accessories', 'options' => [['Color', ['Black', 'Tortoise']]], 'price' => 5900, 'tags' => ['accessories', 'new-arrival']],
            ['title' => 'Throw Blanket', 'type' => 'Home', 'options' => [['Color', ['Charcoal', 'Sand']]], 'price' => 6900, 'tags' => ['home']],
            ['title' => 'Espresso Cup Set', 'type' => 'Home', 'options' => [], 'price' => 3200, 'tags' => ['home', 'clearance']],
            ['title' => 'Hiking Boots', 'type' => 'Footwear', 'options' => [['Size', ['9', '10', '11', '12']]], 'price' => 15900, 'tags' => ['footwear', 'sport']],
            ['title' => 'Yoga Mat', 'type' => 'Sport', 'options' => [['Color', ['Purple', 'Teal', 'Black']]], 'price' => 4200, 'tags' => ['sport', 'new-arrival']],
        ];

        $collections = [
            'featured' => ['title' => 'Featured', 'description' => 'Featured picks from the team.', 'tag' => 'new-arrival', 'status' => CollectionStatus::Active],
            'sale' => ['title' => 'Sale', 'description' => 'Discounted items, limited quantities.', 'tag' => 'sale', 'status' => CollectionStatus::Active],
            'new-arrivals' => ['title' => 'New Arrivals', 'description' => 'Fresh stock, just in.', 'tag' => 'new-arrival', 'status' => CollectionStatus::Active],
            'clearance' => ['title' => 'Clearance', 'description' => 'Final markdowns.', 'tag' => 'clearance', 'status' => CollectionStatus::Active],
            'outerwear' => ['title' => 'Outerwear', 'description' => 'Jackets and cover-ups.', 'tag' => 'outerwear', 'status' => CollectionStatus::Active],
        ];

        $collectionModels = [];

        foreach ($collections as $handleSeed => $entry) {
            $collectionModels[$handleSeed] = Collection::query()->create([
                'store_id' => $storeId,
                'title' => $entry['title'],
                'handle' => HandleGenerator::unique(Collection::class, $storeId, $entry['title']),
                'type' => CollectionType::Manual->value,
                'status' => $entry['status']->value,
                'description_html' => '<p>'.$entry['description'].'</p>',
            ]);
        }

        $createdProducts = [];

        foreach ($catalog as $entry) {
            $product = Product::query()->create([
                'store_id' => $storeId,
                'title' => $entry['title'],
                'handle' => HandleGenerator::unique(Product::class, $storeId, $entry['title']),
                'status' => ProductStatus::Active->value,
                'description_html' => '<p>'.$entry['title'].' from Shop.</p>',
                'vendor' => 'Shop',
                'product_type' => $entry['type'],
                'tags' => $entry['tags'],
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
            if ($combos === []) {
                $combos = [[]];
            }

            $position = 0;

            foreach ($combos as $combo) {
                $variant = ProductVariant::query()->create([
                    'product_id' => $product->getKey(),
                    'sku' => strtoupper(Str::slug($entry['title'])).'-'.strtoupper(Str::random(4)),
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
                    'quantity_on_hand' => random_int(10, 80),
                    'quantity_reserved' => 0,
                    'policy' => InventoryPolicy::Deny->value,
                ]);

                $position++;
            }

            ProductMedia::query()->create([
                'product_id' => $product->getKey(),
                'type' => MediaType::Image->value,
                'storage_key' => 'media/placeholders/'.Str::slug($entry['title']).'.jpg',
                'alt_text' => $entry['title'].' product image',
                'width' => 1200,
                'height' => 1200,
                'mime_type' => 'image/jpeg',
                'byte_size' => 180_000,
                'position' => 0,
                'status' => MediaStatus::Ready->value,
                'created_at' => now(),
            ]);

            $createdProducts[] = $product;

            foreach ($collectionModels as $handleSeed => $collection) {
                $collectionEntry = $collections[$handleSeed];

                if (in_array($collectionEntry['tag'], $entry['tags'], true)) {
                    $collection->products()->syncWithoutDetaching([
                        $product->getKey() => ['position' => $collection->products()->count()],
                    ]);
                }
            }
        }

        $featured = $collectionModels['featured'];

        foreach (array_slice($createdProducts, 0, 6) as $index => $product) {
            if (! $featured->products()->where('products.id', $product->getKey())->exists()) {
                $featured->products()->attach($product->getKey(), ['position' => $index]);
            }
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
