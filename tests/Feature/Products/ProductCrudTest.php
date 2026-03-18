<?php

use App\Enums\ProductStatus;
use App\Exceptions\InvalidProductTransitionException;
use App\Models\Product;
use App\Services\ProductService;

it('lists products for the current store', function () {
    $context = createStoreContext();

    Product::factory()->count(5)->create(['store_id' => $context['store']->id]);

    expect(Product::count())->toBe(5);
});

it('creates a product with a default variant', function () {
    $context = createStoreContext();
    $service = app(ProductService::class);

    $product = $service->create($context['store'], [
        'title' => 'Test Product',
        'description_html' => '<p>Description</p>',
    ]);

    expect($product)->toBeInstanceOf(Product::class)
        ->and($product->title)->toBe('Test Product')
        ->and($product->status)->toBe(ProductStatus::Draft)
        ->and($product->variants)->toHaveCount(1)
        ->and($product->variants->first()->is_default)->toBeTrue()
        ->and($product->variants->first()->inventoryItem)->not->toBeNull();
});

it('generates a unique handle from the title', function () {
    $context = createStoreContext();
    $service = app(ProductService::class);

    $product = $service->create($context['store'], [
        'title' => 'Summer T-Shirt',
    ]);

    expect($product->handle)->toBe('summer-t-shirt');
});

it('appends suffix when handle collides', function () {
    $context = createStoreContext();
    $service = app(ProductService::class);

    $service->create($context['store'], ['title' => 'T-Shirt']);
    $product2 = $service->create($context['store'], ['title' => 'T-Shirt']);

    expect($product2->handle)->toBe('t-shirt-1');
});

it('updates a product', function () {
    $context = createStoreContext();
    $service = app(ProductService::class);

    $product = $service->create($context['store'], ['title' => 'Old Title']);
    $updated = $service->update($product, [
        'title' => 'New Title',
        'description_html' => '<p>Updated</p>',
    ]);

    expect($updated->title)->toBe('New Title')
        ->and($updated->description_html)->toBe('<p>Updated</p>');
});

it('transitions product from draft to active', function () {
    $context = createStoreContext();
    $service = app(ProductService::class);

    $product = $service->create($context['store'], [
        'title' => 'Active Product',
        'price_amount' => 2499,
    ]);

    $service->transitionStatus($product, ProductStatus::Active);

    expect($product->fresh()->status)->toBe(ProductStatus::Active)
        ->and($product->fresh()->published_at)->not->toBeNull();
});

it('rejects draft to active without a priced variant', function () {
    $context = createStoreContext();
    $service = app(ProductService::class);

    $product = $service->create($context['store'], [
        'title' => 'No Price Product',
    ]);

    expect(fn () => $service->transitionStatus($product, ProductStatus::Active))
        ->toThrow(InvalidProductTransitionException::class);
});

it('transitions product from active to archived', function () {
    $context = createStoreContext();
    $service = app(ProductService::class);

    $product = $service->create($context['store'], [
        'title' => 'To Archive',
        'price_amount' => 2499,
    ]);

    $service->transitionStatus($product, ProductStatus::Active);
    $service->transitionStatus($product->fresh(), ProductStatus::Archived);

    expect($product->fresh()->status)->toBe(ProductStatus::Archived);
});

it('prevents active to draft when order lines exist', function () {
    $context = createStoreContext();
    $service = app(ProductService::class);

    $product = $service->create($context['store'], [
        'title' => 'Ordered Product',
        'price_amount' => 2499,
    ]);

    $service->transitionStatus($product, ProductStatus::Active);

    // Simulate order_lines table with a reference
    \Illuminate\Support\Facades\Schema::create('order_lines', function ($table) {
        $table->id();
        $table->foreignId('variant_id');
    });

    \Illuminate\Support\Facades\DB::table('order_lines')->insert([
        'variant_id' => $product->variants->first()->id,
    ]);

    $product->refresh();

    expect(fn () => $service->transitionStatus($product, ProductStatus::Draft))
        ->toThrow(InvalidProductTransitionException::class);
});

it('hard deletes a draft product with no order references', function () {
    $context = createStoreContext();
    $service = app(ProductService::class);

    $product = $service->create($context['store'], [
        'title' => 'Delete Me',
    ]);

    $productId = $product->id;
    $service->delete($product);

    expect(Product::withoutGlobalScopes()->find($productId))->toBeNull();
});

it('prevents deletion of product with order references', function () {
    $context = createStoreContext();
    $service = app(ProductService::class);

    $product = $service->create($context['store'], [
        'title' => 'Cannot Delete',
    ]);

    \Illuminate\Support\Facades\Schema::create('order_lines', function ($table) {
        $table->id();
        $table->foreignId('variant_id');
    });

    \Illuminate\Support\Facades\DB::table('order_lines')->insert([
        'variant_id' => $product->variants->first()->id,
    ]);

    expect(fn () => $service->delete($product))
        ->toThrow(InvalidProductTransitionException::class);
});

it('filters products by status', function () {
    $context = createStoreContext();

    Product::factory()->count(3)->active()->create(['store_id' => $context['store']->id]);
    Product::factory()->count(2)->create(['store_id' => $context['store']->id, 'status' => 'draft']);
    Product::factory()->count(1)->archived()->create(['store_id' => $context['store']->id]);

    expect(Product::where('status', 'active')->count())->toBe(3);
});

it('searches products by title', function () {
    $context = createStoreContext();

    Product::factory()->create([
        'store_id' => $context['store']->id,
        'title' => 'Organic Cotton Hoodie',
        'handle' => 'organic-cotton-hoodie',
    ]);

    Product::factory()->create([
        'store_id' => $context['store']->id,
        'title' => 'Silk Blouse',
        'handle' => 'silk-blouse',
    ]);

    $results = Product::where('title', 'like', '%cotton%')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->title)->toBe('Organic Cotton Hoodie');
});
