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
        $store = Store::where('handle', 'acme-fashion')->first();

        if (! $store) {
            return;
        }

        $this->seedTShirt($store);
        $this->seedMug($store);
        $this->seedCollection($store);
    }

    private function seedTShirt(Store $store): void
    {
        $product = Product::factory()->create([
            'store_id' => $store->id,
            'title' => 'Classic T-Shirt',
            'handle' => 'classic-t-shirt',
            'status' => ProductStatus::Active,
            'description_html' => '<p>A comfortable classic t-shirt made from 100% organic cotton.</p>',
            'vendor' => 'Acme Apparel',
            'product_type' => 'Clothing',
            'tags' => ['cotton', 'basics', 'summer'],
            'published_at' => now(),
        ]);

        $sizeOption = ProductOption::factory()->create([
            'product_id' => $product->id,
            'name' => 'Size',
            'position' => 0,
        ]);

        $colorOption = ProductOption::factory()->create([
            'product_id' => $product->id,
            'name' => 'Color',
            'position' => 1,
        ]);

        $sizes = [];
        foreach (['Small', 'Medium', 'Large'] as $i => $size) {
            $sizes[] = ProductOptionValue::factory()->create([
                'product_option_id' => $sizeOption->id,
                'value' => $size,
                'position' => $i,
            ]);
        }

        $colors = [];
        foreach (['White', 'Black', 'Navy'] as $i => $color) {
            $colors[] = ProductOptionValue::factory()->create([
                'product_option_id' => $colorOption->id,
                'value' => $color,
                'position' => $i,
            ]);
        }

        $position = 0;
        $isFirst = true;
        foreach ($sizes as $size) {
            foreach ($colors as $color) {
                $variant = ProductVariant::factory()->create([
                    'product_id' => $product->id,
                    'sku' => 'TSH-'.strtoupper(substr($size->value, 0, 1)).'-'.strtoupper(substr($color->value, 0, 3)),
                    'price_amount' => 2499,
                    'compare_at_amount' => 2999,
                    'is_default' => $isFirst,
                    'position' => $position,
                    'status' => VariantStatus::Active,
                ]);

                $variant->optionValues()->attach([$size->id, $color->id]);

                InventoryItem::factory()->create([
                    'store_id' => $store->id,
                    'variant_id' => $variant->id,
                    'quantity_on_hand' => fake()->numberBetween(5, 50),
                    'quantity_reserved' => 0,
                    'policy' => InventoryPolicy::Deny,
                ]);

                $position++;
                $isFirst = false;
            }
        }

        ProductMedia::factory()->create([
            'product_id' => $product->id,
            'type' => MediaType::Image,
            'storage_key' => 'products/classic-t-shirt-main.jpg',
            'alt_text' => 'Classic T-Shirt front view',
            'position' => 0,
            'status' => MediaStatus::Ready,
        ]);
    }

    private function seedMug(Store $store): void
    {
        $product = Product::factory()->create([
            'store_id' => $store->id,
            'title' => 'Acme Coffee Mug',
            'handle' => 'acme-coffee-mug',
            'status' => ProductStatus::Active,
            'description_html' => '<p>Start your day with the Acme branded coffee mug.</p>',
            'vendor' => 'Acme Home',
            'product_type' => 'Home',
            'tags' => ['kitchen', 'mugs'],
            'published_at' => now(),
        ]);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'MUG-001',
            'price_amount' => 1299,
            'is_default' => true,
            'position' => 0,
        ]);

        InventoryItem::factory()->create([
            'store_id' => $store->id,
            'variant_id' => $variant->id,
            'quantity_on_hand' => 100,
            'quantity_reserved' => 0,
            'policy' => InventoryPolicy::Deny,
        ]);
    }

    private function seedCollection(Store $store): void
    {
        $collection = Collection::factory()->create([
            'store_id' => $store->id,
            'title' => 'Summer Collection',
            'handle' => 'summer-collection',
            'description_html' => '<p>Our best picks for the summer season.</p>',
        ]);

        $products = Product::where('store_id', $store->id)->get();
        foreach ($products as $i => $product) {
            $collection->products()->attach($product->id, ['position' => $i]);
        }
    }
}
