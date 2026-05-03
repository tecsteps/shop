<?php

use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Events\ProductStatusChanged;
use App\Exceptions\InvalidProductTransitionException;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\ProductService;
use App\Services\VariantMatrixService;
use Illuminate\Support\Facades\Event;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('product service creates a product with a unique handle default variant and inventory', function () {
    $store = Store::factory()->create(['default_currency' => 'EUR']);

    $product = app(ProductService::class)->create($store, [
        'title' => 'Linen Shirt',
        'price_amount' => 4999,
    ]);

    expect($product->handle)->toBe('linen-shirt')
        ->and($product->variants)->toHaveCount(1)
        ->and($product->variants->first()->price_amount)->toBe(4999)
        ->and($product->variants->first()->currency)->toBe('EUR')
        ->and($product->variants->first()->inventoryItem)->not->toBeNull();
});

test('product service generates colliding handles with suffixes and rejects duplicate skus per store', function () {
    $store = Store::factory()->create();

    app(ProductService::class)->create($store, [
        'title' => 'Logo Tee',
        'variants' => [
            ['sku' => 'LOGO-TEE', 'price_amount' => 1000],
        ],
    ]);

    $second = app(ProductService::class)->create($store, [
        'title' => 'Logo Tee',
        'price_amount' => 2000,
    ]);

    expect($second->handle)->toBe('logo-tee-1');

    expect(fn () => app(ProductService::class)->create($store, [
        'title' => 'Another Tee',
        'variants' => [
            ['sku' => 'LOGO-TEE', 'price_amount' => 1500],
        ],
    ]))->toThrow(InvalidProductTransitionException::class);
});

test('product status transitions require an active priced variant', function () {
    $product = Product::factory()->draft()->create(['title' => 'Draft Product']);
    ProductVariant::factory()->for($product)->default()->create(['price_amount' => 0]);

    expect(fn () => app(ProductService::class)->transitionStatus($product, ProductStatus::Active))
        ->toThrow(InvalidProductTransitionException::class);

    Event::fake();

    $product->variants()->first()->update(['price_amount' => 1000]);

    app(ProductService::class)->transitionStatus($product->refresh(), ProductStatus::Active);

    expect($product->refresh()->status)->toBe(ProductStatus::Active)
        ->and($product->published_at)->not->toBeNull();

    Event::assertDispatched(ProductStatusChanged::class);
});

test('variant matrix rebuild creates combinations and removes orphan variants', function () {
    $product = Product::factory()->create();
    $template = ProductVariant::factory()->for($product)->default()->create(['price_amount' => 1999]);
    $option = ProductOption::factory()->for($product)->create(['name' => 'Size']);

    $small = $option->values()->create(['value' => 'S', 'position' => 0]);
    $medium = $option->values()->create(['value' => 'M', 'position' => 1]);

    app(VariantMatrixService::class)->rebuildMatrix($product);

    $variants = $product->variants()->with('optionValues')->get();

    expect($variants)->toHaveCount(2)
        ->and($variants->pluck('price_amount')->all())->toBe([1999, 1999])
        ->and($variants->flatMap->optionValues->pluck('id')->sort()->values()->all())->toBe([$small->id, $medium->id])
        ->and(ProductVariant::query()->whereKey($template->id)->exists())->toBeFalse();
});

test('product service syncs option matrix variant fields and inventory', function () {
    $store = Store::factory()->create(['default_currency' => 'EUR']);
    $product = Product::factory()->for($store)->create();
    ProductVariant::factory()->for($product)->default()->create(['price_amount' => 1999, 'currency' => 'EUR']);

    app(ProductService::class)->syncOptionMatrix($product, [
        ['name' => 'Size', 'values' => ['S', 'M']],
        ['name' => 'Color', 'values' => ['Black', 'White']],
    ], [
        [
            'option_values' => ['S', 'Black'],
            'sku' => 'TEE-S-BLK',
            'price_amount' => 2499,
            'quantity_on_hand' => 5,
            'requires_shipping' => true,
            'currency' => 'EUR',
        ],
        [
            'option_values' => ['M', 'White'],
            'sku' => 'TEE-M-WHT',
            'price_amount' => 2799,
            'quantity_on_hand' => 8,
            'requires_shipping' => false,
            'currency' => 'EUR',
        ],
    ]);

    $product = $product->refresh()->load('options.values', 'variants.optionValues.option', 'variants.inventoryItem');
    $whiteMedium = $product->variants
        ->first(fn (ProductVariant $variant): bool => $variant->optionValues->pluck('value')->sort()->values()->all() === ['M', 'White']);

    expect($product->options)->toHaveCount(2)
        ->and($product->variants)->toHaveCount(4)
        ->and($whiteMedium->sku)->toBe('TEE-M-WHT')
        ->and($whiteMedium->price_amount)->toBe(2799)
        ->and($whiteMedium->requires_shipping)->toBeFalse()
        ->and($whiteMedium->inventoryItem->quantity_on_hand)->toBe(8);
});

test('product service blocks draft reversion and deletion when order lines reference product', function () {
    $store = Store::factory()->create();
    $product = Product::factory()->for($store)->withDefaultVariant(1000)->create([
        'status' => ProductStatus::Active,
    ]);
    $variant = $product->variants()->firstOrFail();
    $order = Order::factory()->for($store)->create();

    OrderLine::query()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'title_snapshot' => $product->title,
        'sku_snapshot' => $variant->sku,
        'quantity' => 1,
        'unit_price_amount' => $variant->price_amount,
        'total_amount' => $variant->price_amount,
    ]);

    expect(fn () => app(ProductService::class)->transitionStatus($product->refresh(), ProductStatus::Draft))
        ->toThrow(InvalidProductTransitionException::class);

    $product->forceFill(['status' => ProductStatus::Draft])->save();

    expect(fn () => app(ProductService::class)->delete($product->refresh()))
        ->toThrow(InvalidProductTransitionException::class)
        ->and(Product::query()->whereKey($product->id)->exists())->toBeTrue();
});

test('product service hard deletes draft products without order references', function () {
    $product = Product::factory()->draft()->withDefaultVariant(1000)->create();

    app(ProductService::class)->delete($product);

    $this->assertModelMissing($product);
});

test('variant matrix archives orphan variants with order references', function () {
    $store = Store::factory()->create();
    $product = Product::factory()->for($store)->create();
    ProductVariant::factory()->for($product)->default()->create(['price_amount' => 1999]);
    $option = ProductOption::factory()->for($product)->create(['name' => 'Size']);

    $option->values()->create(['value' => 'S', 'position' => 0]);
    $medium = $option->values()->create(['value' => 'M', 'position' => 1]);

    app(VariantMatrixService::class)->rebuildMatrix($product);

    $mediumVariant = ProductVariant::query()
        ->where('product_id', $product->id)
        ->whereHas('optionValues', fn ($query) => $query->whereKey($medium->id))
        ->firstOrFail();
    $order = Order::factory()->for($store)->create();

    OrderLine::query()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'variant_id' => $mediumVariant->id,
        'title_snapshot' => $product->title,
        'sku_snapshot' => $mediumVariant->sku,
        'quantity' => 1,
        'unit_price_amount' => $mediumVariant->price_amount,
        'total_amount' => $mediumVariant->price_amount,
    ]);

    $medium->delete();

    app(VariantMatrixService::class)->rebuildMatrix($product->refresh());

    expect($mediumVariant->refresh()->status)->toBe(VariantStatus::Archived);
});
