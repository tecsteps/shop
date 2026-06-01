<?php

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Enums\VariantStatus;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductVariant;

beforeEach(function () {
    $context = createStoreContext();
    $this->store = $context['store'];
});

it('published scope returns only active products', function () {
    Product::factory()->count(2)->active()->create(['store_id' => $this->store->id]);
    Product::factory()->create(['store_id' => $this->store->id]); // draft
    Product::factory()->archived()->create(['store_id' => $this->store->id]);

    expect(Product::published()->count())->toBe(2);
});

it('published scope returns only active collections', function () {
    Collection::factory()->count(3)->create(['store_id' => $this->store->id]); // active default
    Collection::factory()->draft()->create(['store_id' => $this->store->id]);

    expect(Collection::published()->count())->toBe(3);
});

it('primaryVariant prefers the default variant', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 5000, 'is_default' => false]);
    $default = ProductVariant::factory()->default()->create(['product_id' => $product->id, 'price_amount' => 9000]);

    expect($product->primaryVariant()->id)->toBe($default->id)
        ->and($product->displayPriceAmount())->toBe(9000);
});

it('primaryVariant falls back to the cheapest active variant', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 5000, 'is_default' => false]);
    $cheap = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500, 'is_default' => false]);
    ProductVariant::factory()->archived()->create(['product_id' => $product->id, 'price_amount' => 100, 'is_default' => false]);

    expect($product->primaryVariant()->id)->toBe($cheap->id)
        ->and($product->displayPriceAmount())->toBe(2500);
});

it('compareAtAmount reflects the primary variant', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    ProductVariant::factory()->default()->create([
        'product_id' => $product->id,
        'price_amount' => 2500,
        'compare_at_amount' => 4000,
    ]);

    expect($product->compareAtAmount())->toBe(4000);
});

it('primaryImage returns the first ready image by position', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    ProductMedia::factory()->processing()->create(['product_id' => $product->id, 'position' => 0]);
    $ready = ProductMedia::factory()->create([
        'product_id' => $product->id,
        'type' => MediaType::Image->value,
        'status' => MediaStatus::Ready->value,
        'position' => 1,
    ]);

    expect($product->primaryImage()->id)->toBe($ready->id);
});

it('primaryImage is null when no image is ready', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    ProductMedia::factory()->processing()->create(['product_id' => $product->id]);

    expect($product->primaryImage())->toBeNull();
});

it('accessors work from an eager-loaded relation without extra queries', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    ProductVariant::factory()->default()->create(['product_id' => $product->id, 'price_amount' => 3300, 'status' => VariantStatus::Active->value]);
    ProductMedia::factory()->create(['product_id' => $product->id, 'status' => MediaStatus::Ready->value, 'position' => 0]);

    $loaded = Product::with(['variants', 'media'])->find($product->id);

    expect($loaded->displayPriceAmount())->toBe(3300)
        ->and($loaded->primaryImage())->not->toBeNull();
});
