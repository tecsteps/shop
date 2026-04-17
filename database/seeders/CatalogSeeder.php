<?php

namespace Database\Seeders;

use App\Enums\CollectionStatus;
use App\Enums\CollectionType;
use App\Enums\InventoryPolicy;
use App\Enums\ProductMediaStatus;
use App\Enums\ProductMediaType;
use App\Enums\ProductStatus;
use App\Enums\ProductVariantStatus;
use App\Models\Collection;
use App\Models\CollectionProduct;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\VariantOptionValue;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::query()->where('handle', 'demo-store')->firstOrFail();

        $collection = Collection::query()->updateOrCreate(
            ['store_id' => $store->id, 'handle' => 'featured'],
            [
                'title' => 'Featured Products',
                'description_html' => '<p>Top picks from the catalog.</p>',
                'type' => CollectionType::Manual,
                'status' => CollectionStatus::Active,
            ],
        );

        $product = Product::query()->updateOrCreate(
            ['store_id' => $store->id, 'handle' => 'demo-t-shirt'],
            [
                'title' => 'Demo T-Shirt',
                'status' => ProductStatus::Active,
                'description_html' => '<p>Comfortable cotton t-shirt.</p>',
                'vendor' => 'Acme Apparel',
                'product_type' => 'Shirts',
                'tags' => ['new', 'popular'],
                'published_at' => now()->subDay(),
            ],
        );

        $sizeOption = ProductOption::query()->updateOrCreate(
            ['product_id' => $product->id, 'position' => 1],
            ['name' => 'Size'],
        );

        $sizeM = ProductOptionValue::query()->updateOrCreate(
            ['product_option_id' => $sizeOption->id, 'position' => 1],
            ['value' => 'M'],
        );

        $variant = ProductVariant::query()->updateOrCreate(
            ['product_id' => $product->id, 'sku' => 'DEMO-TSHIRT-M'],
            [
                'barcode' => '4006381333931',
                'price_amount' => 2499,
                'compare_at_amount' => 2999,
                'currency' => 'EUR',
                'weight_g' => 220,
                'requires_shipping' => true,
                'is_default' => true,
                'position' => 1,
                'status' => ProductVariantStatus::Active,
            ],
        );

        VariantOptionValue::query()->updateOrCreate(
            ['variant_id' => $variant->id, 'product_option_value_id' => $sizeM->id],
            [],
        );

        InventoryItem::query()->updateOrCreate(
            ['variant_id' => $variant->id],
            [
                'store_id' => $store->id,
                'quantity_on_hand' => 25,
                'quantity_reserved' => 2,
                'policy' => InventoryPolicy::Deny,
            ],
        );

        CollectionProduct::query()->updateOrCreate(
            ['collection_id' => $collection->id, 'product_id' => $product->id],
            ['position' => 1],
        );

        ProductMedia::query()->updateOrCreate(
            ['product_id' => $product->id, 'position' => 1],
            [
                'type' => ProductMediaType::Image,
                'storage_key' => 'products/demo-tshirt/main.jpg',
                'alt_text' => 'Demo T-Shirt front view',
                'width' => 1600,
                'height' => 1600,
                'mime_type' => 'image/jpeg',
                'byte_size' => 240_000,
                'status' => ProductMediaStatus::Ready,
                'created_at' => now(),
            ],
        );
    }
}
