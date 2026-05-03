<?php

use App\Enums\InventoryPolicy;
use App\Models\Collection;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Store;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('catalog tables and seeded fixtures exist', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Schema::hasColumns('products', ['store_id', 'title', 'handle', 'status', 'tags']))->toBeTrue()
        ->and(Schema::hasColumns('product_variants', ['product_id', 'sku', 'price_amount', 'requires_shipping']))->toBeTrue()
        ->and(Schema::hasColumns('inventory_items', ['store_id', 'variant_id', 'quantity_on_hand', 'quantity_reserved', 'policy']))->toBeTrue()
        ->and(Schema::hasColumns('collections', ['store_id', 'title', 'handle', 'type', 'status']))->toBeTrue()
        ->and(Store::query()->count())->toBe(2)
        ->and(Product::withoutGlobalScopes()->count())->toBe(25)
        ->and(ProductVariant::withoutGlobalScopes()->count())->toBe(127)
        ->and(InventoryItem::withoutGlobalScopes()->count())->toBe(127)
        ->and(ProductMedia::withoutGlobalScopes()->count())->toBe(0)
        ->and(Collection::withoutGlobalScopes()->count())->toBe(6)
        ->and(Product::withoutGlobalScopes()->where('handle', 'classic-cotton-t-shirt')->first()?->variants()->withoutGlobalScopes()->count())->toBe(12)
        ->and(Product::withoutGlobalScopes()->where('handle', 'pro-laptop-15')->first()?->variants()->withoutGlobalScopes()->count())->toBe(3)
        ->and(Product::withoutGlobalScopes()->where('handle', 'limited-edition-sneakers')->first()?->variants()->withoutGlobalScopes()->first()?->inventoryItem?->policy?->value)->toBe('deny')
        ->and(Product::withoutGlobalScopes()->where('handle', 'backorder-denim-jacket')->first()?->variants()->withoutGlobalScopes()->first()?->inventoryItem?->policy?->value)->toBe('continue');
});

test('catalog models are scoped to the resolved store', function () {
    $firstStore = Store::factory()->create();
    $secondStore = Store::factory()->create();

    Product::factory()->create(['store_id' => $firstStore->getKey(), 'handle' => 'first-store-product']);
    Product::factory()->create(['store_id' => $secondStore->getKey(), 'handle' => 'second-store-product']);

    app()->instance('current_store', $firstStore);

    expect(Product::query()->pluck('handle')->all())->toBe(['first-store-product']);
});

test('catalog child models are scoped through their product store', function () {
    $firstStore = Store::factory()->create();
    $secondStore = Store::factory()->create();
    $firstProduct = Product::factory()->create(['store_id' => $firstStore->getKey()]);
    $secondProduct = Product::factory()->create(['store_id' => $secondStore->getKey()]);

    $firstOption = ProductOption::factory()->create(['product_id' => $firstProduct->getKey(), 'position' => 0]);
    $secondOption = ProductOption::factory()->create(['product_id' => $secondProduct->getKey(), 'position' => 0]);
    ProductOptionValue::factory()->create(['product_option_id' => $firstOption->getKey(), 'position' => 0]);
    ProductOptionValue::factory()->create(['product_option_id' => $secondOption->getKey(), 'position' => 0]);
    ProductVariant::factory()->create(['product_id' => $firstProduct->getKey(), 'sku' => 'FIRST']);
    ProductVariant::factory()->create(['product_id' => $secondProduct->getKey(), 'sku' => 'SECOND']);
    ProductMedia::factory()->create(['product_id' => $firstProduct->getKey()]);
    ProductMedia::factory()->create(['product_id' => $secondProduct->getKey()]);

    app()->instance('current_store', $firstStore);

    expect(ProductOption::query()->pluck('product_id')->all())->toBe([$firstProduct->getKey()])
        ->and(ProductOptionValue::query()->count())->toBe(1)
        ->and(ProductVariant::query()->pluck('sku')->all())->toBe(['FIRST'])
        ->and(ProductMedia::query()->pluck('product_id')->all())->toBe([$firstProduct->getKey()]);
});

test('variants create inventory for their product store regardless of resolved store scope', function () {
    $resolvedStore = Store::factory()->create();
    $productStore = Store::factory()->create();
    $product = Product::factory()->create(['store_id' => $productStore->getKey()]);

    app()->instance('current_store', $resolvedStore);

    $variant = ProductVariant::factory()->create(['product_id' => $product->getKey()]);
    $inventory = InventoryItem::withoutGlobalScopes()
        ->where('variant_id', $variant->getKey())
        ->first();

    expect($inventory)->not->toBeNull()
        ->and($inventory->store_id)->toBe($productStore->getKey())
        ->and($inventory->policy)->toBe(InventoryPolicy::Deny);
});

test('inventory store must match the variant product store', function () {
    $variantStore = Store::factory()->create();
    $otherStore = Store::factory()->create();
    $product = Product::factory()->create(['store_id' => $variantStore->getKey()]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->getKey()]);

    expect(fn () => InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $otherStore->getKey(),
        'variant_id' => $variant->getKey(),
        'quantity_on_hand' => 1,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Deny,
    ]))->toThrow(InvalidArgumentException::class);
});

test('variant sku uniqueness is enforced for direct model saves per store', function () {
    $store = Store::factory()->create();
    $product = Product::factory()->create(['store_id' => $store->getKey()]);
    $secondProduct = Product::factory()->create(['store_id' => $store->getKey()]);

    ProductVariant::factory()->create(['product_id' => $product->getKey(), 'sku' => 'STORE-SKU']);

    expect(fn () => ProductVariant::factory()->create([
        'product_id' => $secondProduct->getKey(),
        'sku' => 'STORE-SKU',
    ]))->toThrow(RuntimeException::class);
});
