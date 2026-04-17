<?php

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ProductService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->service = app(ProductService::class);
});

it('lists products for the current store', function () {
    Product::factory()->count(5)->create(['store_id' => $this->ctx['store']->id]);

    expect(Product::count())->toBe(5);
});

it('creates a product with a default variant', function () {
    $product = $this->service->create($this->ctx['store'], [
        'title' => 'Test Product',
        'price_amount' => 2500,
    ]);

    expect($product)->toBeInstanceOf(Product::class);
    expect($product->title)->toBe('Test Product');

    $variant = $product->variants()->first();
    expect($variant)->not->toBeNull();
    expect($variant->is_default)->toBeTrue();
    expect($variant->price_amount)->toBe(2500);

    $inventoryItem = $variant->inventoryItem;
    expect($inventoryItem)->not->toBeNull();
    expect($inventoryItem->quantity_on_hand)->toBe(0);
    expect($inventoryItem->quantity_reserved)->toBe(0);
});

it('generates a unique handle from the title', function () {
    $product = $this->service->create($this->ctx['store'], [
        'title' => 'Summer T-Shirt',
    ]);

    expect($product->handle)->toBe('summer-t-shirt');
});

it('appends suffix when handle collides', function () {
    $this->service->create($this->ctx['store'], ['title' => 'T-Shirt']);
    $product2 = $this->service->create($this->ctx['store'], ['title' => 'T-Shirt']);

    expect($product2->handle)->toBe('t-shirt-1');
});

it('updates a product', function () {
    $product = $this->service->create($this->ctx['store'], ['title' => 'Old Title']);

    $updated = $this->service->update($product, [
        'title' => 'New Title',
        'description_html' => '<p>Updated description</p>',
    ]);

    expect($updated->title)->toBe('New Title');
    expect($updated->description_html)->toBe('<p>Updated description</p>');
    expect($updated->handle)->toBe('new-title');
});

it('transitions product from draft to active', function () {
    $product = $this->service->create($this->ctx['store'], [
        'title' => 'Draft Product',
        'price_amount' => 1500,
    ]);

    $this->service->transitionStatus($product, ProductStatus::Active);
    $product->refresh();

    expect($product->status)->toBe(ProductStatus::Active);
    expect($product->published_at)->not->toBeNull();
});

it('rejects draft to active without a priced variant', function () {
    $product = $this->service->create($this->ctx['store'], [
        'title' => 'No Price Product',
        'price_amount' => 0,
    ]);

    expect(fn () => $this->service->transitionStatus($product, ProductStatus::Active))
        ->toThrow(InvalidArgumentException::class);

    $product->refresh();
    expect($product->status)->toBe(ProductStatus::Draft);
});

it('transitions product from active to archived', function () {
    $product = $this->service->create($this->ctx['store'], [
        'title' => 'Active Product',
        'price_amount' => 1000,
    ]);
    $this->service->transitionStatus($product, ProductStatus::Active);

    $this->service->transitionStatus($product->fresh(), ProductStatus::Archived);
    $product->refresh();

    expect($product->status)->toBe(ProductStatus::Archived);
});

it('prevents active to draft when order lines exist', function () {
    if (! \Illuminate\Support\Facades\Schema::hasTable('order_lines')) {
        $this->markTestSkipped('order_lines table does not exist yet.');
    }

    $product = $this->service->create($this->ctx['store'], [
        'title' => 'Ordered Product',
        'price_amount' => 2000,
    ]);
    $this->service->transitionStatus($product, ProductStatus::Active);

    $variant = $product->variants()->first();
    $order = \App\Models\Order::factory()->create(['store_id' => $this->ctx['store']->id]);
    \Illuminate\Support\Facades\DB::table('order_lines')->insert([
        'variant_id' => $variant->id,
        'order_id' => $order->id,
        'quantity' => 1,
        'unit_price_amount' => 2000,
        'subtotal_amount' => 2000,
        'total_amount' => 2000,
        'title_snapshot' => 'Ordered Product',
    ]);

    expect(fn () => $this->service->transitionStatus($product->fresh(), ProductStatus::Draft))
        ->toThrow(InvalidArgumentException::class);
});

it('hard deletes a draft product with no order references', function () {
    $product = $this->service->create($this->ctx['store'], [
        'title' => 'Delete Me',
        'price_amount' => 500,
    ]);
    $productId = $product->id;

    $this->service->delete($product);

    expect(Product::withoutGlobalScopes()->find($productId))->toBeNull();
    expect(ProductVariant::where('product_id', $productId)->count())->toBe(0);
});

it('prevents deletion of product with order references', function () {
    if (! \Illuminate\Support\Facades\Schema::hasTable('order_lines')) {
        $this->markTestSkipped('order_lines table does not exist yet.');
    }

    $product = $this->service->create($this->ctx['store'], [
        'title' => 'Referenced Product',
        'price_amount' => 2000,
    ]);

    $variant = $product->variants()->first();
    $order = \App\Models\Order::factory()->create(['store_id' => $this->ctx['store']->id]);
    \Illuminate\Support\Facades\DB::table('order_lines')->insert([
        'variant_id' => $variant->id,
        'order_id' => $order->id,
        'quantity' => 1,
        'unit_price_amount' => 2000,
        'subtotal_amount' => 2000,
        'total_amount' => 2000,
        'title_snapshot' => 'Referenced Product',
    ]);

    expect(fn () => $this->service->delete($product))
        ->toThrow(InvalidArgumentException::class);
});

it('filters products by status', function () {
    Product::factory()->count(3)->active()->create(['store_id' => $this->ctx['store']->id]);
    Product::factory()->count(2)->create(['store_id' => $this->ctx['store']->id]); // draft
    Product::factory()->archived()->create(['store_id' => $this->ctx['store']->id]);

    $active = Product::where('status', ProductStatus::Active)->get();
    $draft = Product::where('status', ProductStatus::Draft)->get();
    $archived = Product::where('status', ProductStatus::Archived)->get();

    expect($active)->toHaveCount(3);
    expect($draft)->toHaveCount(2);
    expect($archived)->toHaveCount(1);
});

it('searches products by title', function () {
    Product::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Organic Cotton Hoodie',
    ]);
    Product::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Polyester Jacket',
    ]);

    $results = Product::where('title', 'like', '%cotton%')->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->title)->toBe('Organic Cotton Hoodie');
});
