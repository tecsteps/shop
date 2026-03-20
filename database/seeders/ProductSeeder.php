<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\VariantMatrixService;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::first();

        if (! $store) {
            return;
        }

        app()->instance('current_store', $store);

        $tShirt = Product::factory()->create([
            'store_id' => $store->id,
            'title' => 'Classic Cotton T-Shirt',
            'handle' => 'classic-cotton-t-shirt',
            'status' => 'active',
            'published_at' => now()->toIso8601String(),
            'vendor' => 'Acme Apparel',
            'product_type' => 'Apparel',
            'tags' => ['summer', 'basics', 'cotton'],
        ]);

        $sizeOption = ProductOption::factory()->create([
            'product_id' => $tShirt->id,
            'name' => 'Size',
            'position' => 0,
        ]);

        foreach (['S', 'M', 'L', 'XL'] as $i => $size) {
            ProductOptionValue::factory()->create([
                'product_option_id' => $sizeOption->id,
                'value' => $size,
                'position' => $i,
            ]);
        }

        $colorOption = ProductOption::factory()->create([
            'product_id' => $tShirt->id,
            'name' => 'Color',
            'position' => 1,
        ]);

        foreach (['White', 'Black', 'Navy'] as $i => $color) {
            ProductOptionValue::factory()->create([
                'product_option_id' => $colorOption->id,
                'value' => $color,
                'position' => $i,
            ]);
        }

        app(VariantMatrixService::class)->rebuildMatrix($tShirt);

        foreach ($tShirt->variants()->get() as $variant) {
            $variant->update(['price_amount' => 2499]);
            $variant->inventoryItem?->update(['quantity_on_hand' => 50]);
        }

        $hoodie = Product::factory()->create([
            'store_id' => $store->id,
            'title' => 'Organic Cotton Hoodie',
            'handle' => 'organic-cotton-hoodie',
            'status' => 'active',
            'published_at' => now()->toIso8601String(),
            'vendor' => 'Acme Apparel',
            'product_type' => 'Apparel',
            'tags' => ['winter', 'organic'],
        ]);

        $defaultVariant = ProductVariant::factory()->create([
            'product_id' => $hoodie->id,
            'is_default' => true,
            'price_amount' => 5999,
            'sku' => 'HOODIE-001',
        ]);

        InventoryItem::factory()->create([
            'store_id' => $store->id,
            'variant_id' => $defaultVariant->id,
            'quantity_on_hand' => 30,
        ]);

        $collection = Collection::factory()->create([
            'store_id' => $store->id,
            'title' => 'Summer Collection',
            'handle' => 'summer-collection',
            'status' => 'active',
        ]);

        $collection->products()->attach($tShirt->id, ['position' => 0]);

        $basicCollection = Collection::factory()->create([
            'store_id' => $store->id,
            'title' => 'Basics',
            'handle' => 'basics',
            'status' => 'active',
        ]);

        $basicCollection->products()->attach([$tShirt->id => ['position' => 0], $hoodie->id => ['position' => 1]]);
    }
}
