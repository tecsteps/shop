<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Models\Collection;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::first();

        $this->seedTShirt($store);
        $this->seedMug($store);
        $this->seedCollections($store);
    }

    protected function seedTShirt(Store $store): void
    {
        $product = Product::factory()->create([
            'store_id' => $store->id,
            'title' => 'Classic T-Shirt',
            'handle' => 'classic-t-shirt',
            'status' => ProductStatus::Active,
            'description_html' => '<p>A timeless classic cotton t-shirt.</p>',
            'vendor' => 'Acme Apparel',
            'product_type' => 'Clothing',
            'tags' => ['summer', 'basics'],
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
        foreach (['Blue', 'Red', 'Green'] as $i => $color) {
            $colors[] = ProductOptionValue::factory()->create([
                'product_option_id' => $colorOption->id,
                'value' => $color,
                'position' => $i,
            ]);
        }

        $position = 0;
        foreach ($sizes as $size) {
            foreach ($colors as $color) {
                $variant = ProductVariant::factory()->create([
                    'product_id' => $product->id,
                    'sku' => 'TSH-'.strtoupper(substr($color->value, 0, 3)).'-'.strtoupper(substr($size->value, 0, 1)),
                    'price_amount' => 2500,
                    'currency' => $store->default_currency,
                    'weight_g' => 200,
                    'is_default' => $position === 0,
                    'position' => $position,
                ]);

                $variant->optionValues()->attach([$size->id, $color->id]);

                InventoryItem::factory()->create([
                    'store_id' => $store->id,
                    'variant_id' => $variant->id,
                    'quantity_on_hand' => 50,
                    'quantity_reserved' => 0,
                ]);

                $position++;
            }
        }

        ProductMedia::factory()->create([
            'product_id' => $product->id,
            'storage_key' => 'products/classic-t-shirt-1.jpg',
            'alt_text' => 'Classic T-Shirt front view',
            'position' => 0,
        ]);
    }

    protected function seedMug(Store $store): void
    {
        $product = Product::factory()->create([
            'store_id' => $store->id,
            'title' => 'Coffee Mug',
            'handle' => 'coffee-mug',
            'status' => ProductStatus::Active,
            'description_html' => '<p>Ceramic coffee mug, perfect for your morning brew.</p>',
            'vendor' => 'Acme Home',
            'product_type' => 'Home & Kitchen',
            'tags' => ['drinkware', 'kitchen'],
            'published_at' => now(),
        ]);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'MUG-WHT-01',
            'price_amount' => 1200,
            'currency' => $store->default_currency,
            'weight_g' => 350,
            'is_default' => true,
            'position' => 0,
        ]);

        InventoryItem::factory()->create([
            'store_id' => $store->id,
            'variant_id' => $variant->id,
            'quantity_on_hand' => 100,
            'quantity_reserved' => 0,
        ]);
    }

    protected function seedCollections(Store $store): void
    {
        $summerCollection = Collection::factory()->create([
            'store_id' => $store->id,
            'title' => 'Summer Essentials',
            'handle' => 'summer-essentials',
            'description_html' => '<p>Everything you need for summer.</p>',
        ]);

        $products = Product::query()->withoutGlobalScopes()->where('store_id', $store->id)->get();
        foreach ($products as $i => $product) {
            $summerCollection->products()->attach($product->id, ['position' => $i]);
        }
    }
}
