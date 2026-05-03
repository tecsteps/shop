<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\VariantMatrixService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $collection = Collection::query()
            ->where('store_id', $store->id)
            ->where('handle', 'summer-essentials')
            ->firstOrFail();

        $shirt = Product::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'handle' => 'linen-shirt',
            ],
            [
                'title' => 'Linen Shirt',
                'status' => ProductStatus::Active,
                'description_html' => '<p>A breathable linen shirt with a relaxed fit.</p>',
                'vendor' => 'Acme Apparel',
                'product_type' => 'Shirts',
                'tags' => ['new', 'summer'],
                'published_at' => now(),
            ],
        );

        $variant = ProductVariant::query()->updateOrCreate(
            [
                'product_id' => $shirt->id,
                'sku' => 'LINEN-SHIRT-DEFAULT',
            ],
            [
                'price_amount' => 4999,
                'currency' => $store->default_currency,
                'requires_shipping' => true,
                'is_default' => true,
                'position' => 0,
                'status' => VariantStatus::Active,
            ],
        );

        $variant->inventoryItem()->withoutGlobalScopes()->updateOrCreate(
            ['variant_id' => $variant->id],
            [
                'store_id' => $store->id,
                'quantity_on_hand' => 50,
                'quantity_reserved' => 0,
                'policy' => 'deny',
            ],
        );

        $tee = Product::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'handle' => 'logo-tee',
            ],
            [
                'title' => 'Logo Tee',
                'status' => ProductStatus::Active,
                'description_html' => '<p>Soft cotton tee with the Acme mark.</p>',
                'vendor' => 'Acme Apparel',
                'product_type' => 'Shirts',
                'tags' => ['popular'],
                'published_at' => now(),
            ],
        );

        $sizeOption = ProductOption::query()->updateOrCreate(
            [
                'product_id' => $tee->id,
                'position' => 0,
            ],
            ['name' => 'Size'],
        );

        foreach (['S', 'M', 'L'] as $position => $value) {
            $sizeOption->values()->updateOrCreate(
                ['position' => $position],
                ['value' => $value],
            );
        }

        ProductVariant::query()->firstOrCreate(
            [
                'product_id' => $tee->id,
                'sku' => 'LOGO-TEE-TEMPLATE',
            ],
            [
                'price_amount' => 2999,
                'currency' => $store->default_currency,
                'is_default' => true,
                'position' => 0,
                'status' => VariantStatus::Active,
            ],
        );

        app(VariantMatrixService::class)->rebuildMatrix($tee->refresh());

        $tee->variants()->with('inventoryItem')->get()->each(function (ProductVariant $variant) use ($store): void {
            $variant->inventoryItem()->withoutGlobalScopes()->updateOrCreate(
                ['variant_id' => $variant->id],
                [
                    'store_id' => $store->id,
                    'quantity_on_hand' => 30,
                    'quantity_reserved' => 0,
                    'policy' => 'deny',
                ],
            );
        });

        DB::table('collection_products')->updateOrInsert(
            [
                'collection_id' => $collection->id,
                'product_id' => $shirt->id,
            ],
            ['position' => 0],
        );

        DB::table('collection_products')->updateOrInsert(
            [
                'collection_id' => $collection->id,
                'product_id' => $tee->id,
            ],
            ['position' => 1],
        );
    }
}
