<?php

use App\Enums\ProductStatus;
use App\Events\ProductStatusChanged;
use App\Exceptions\InvalidProductTransitionException;
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
