<?php

use App\Enums\ProductStatus;
use App\Exceptions\InvalidProductTransitionException;
use App\Models\Product;
use App\Services\ProductService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->service = app(ProductService::class);
});

it('creates a product with a default variant', function () {
    $product = $this->service->create($this->store, [
        'title' => 'Summer T-Shirt',
        'description_html' => '<p>A cool shirt</p>',
    ]);

    expect($product)->toBeInstanceOf(Product::class)
        ->and($product->title)->toBe('Summer T-Shirt')
        ->and($product->status)->toBe(ProductStatus::Draft)
        ->and($product->variants)->toHaveCount(1)
        ->and($product->variants->first()->is_default)->toBeTrue()
        ->and($product->variants->first()->inventoryItem)->not->toBeNull()
        ->and($product->variants->first()->inventoryItem->quantity_on_hand)->toBe(0)
        ->and($product->variants->first()->inventoryItem->quantity_reserved)->toBe(0);
});

it('generates a unique handle from the title', function () {
    $product = $this->service->create($this->store, [
        'title' => 'Summer T-Shirt',
    ]);

    expect($product->handle)->toBe('summer-t-shirt');
});

it('appends suffix when handle collides', function () {
    $this->service->create($this->store, ['title' => 'T-Shirt']);
    $product2 = $this->service->create($this->store, ['title' => 'T-Shirt']);

    expect($product2->handle)->toBe('t-shirt-1');
});

it('updates a product', function () {
    $product = $this->service->create($this->store, ['title' => 'Old Title']);

    $updated = $this->service->update($product, [
        'title' => 'New Title',
        'description_html' => 'New description',
    ]);

    expect($updated->title)->toBe('New Title')
        ->and($updated->description_html)->toBe('New description');
});

it('transitions product from draft to active', function () {
    $product = $this->service->create($this->store, ['title' => 'Test Product']);
    $product->variants()->first()->update(['price_amount' => 2999]);

    $this->service->transitionStatus($product, ProductStatus::Active);

    $product->refresh();
    expect($product->status)->toBe(ProductStatus::Active)
        ->and($product->published_at)->not->toBeNull();
});

it('rejects draft to active without a priced variant', function () {
    $product = $this->service->create($this->store, ['title' => 'Test Product']);

    expect(fn () => $this->service->transitionStatus($product, ProductStatus::Active))
        ->toThrow(InvalidProductTransitionException::class);

    $product->refresh();
    expect($product->status)->toBe(ProductStatus::Draft);
});

it('transitions product from active to archived', function () {
    $product = $this->service->create($this->store, ['title' => 'Test Product']);
    $product->variants()->first()->update(['price_amount' => 2999]);
    $this->service->transitionStatus($product, ProductStatus::Active);

    $this->service->transitionStatus($product, ProductStatus::Archived);

    $product->refresh();
    expect($product->status)->toBe(ProductStatus::Archived);
});

it('prevents active to draft when order lines exist', function () {
    $product = $this->service->create($this->store, ['title' => 'Test Product']);
    $product->variants()->first()->update(['price_amount' => 2999]);
    $this->service->transitionStatus($product, ProductStatus::Active);

    if (\Illuminate\Support\Facades\Schema::hasTable('order_lines')) {
        $variant = $product->variants()->first();
        \Illuminate\Support\Facades\DB::table('order_lines')->insert([
            'order_id' => 1,
            'variant_id' => $variant->id,
            'product_id' => $product->id,
            'title' => 'Test',
            'quantity' => 1,
            'unit_price_amount' => 2999,
            'subtotal_amount' => 2999,
            'total_amount' => 2999,
        ]);

        expect(fn () => $this->service->transitionStatus($product, ProductStatus::Draft))
            ->toThrow(InvalidProductTransitionException::class);

        $product->refresh();
        expect($product->status)->toBe(ProductStatus::Active);
    } else {
        // order_lines table not yet created; transition should succeed
        $this->service->transitionStatus($product, ProductStatus::Draft);
        $product->refresh();
        expect($product->status)->toBe(ProductStatus::Draft);
    }
});

it('hard deletes a draft product with no order references', function () {
    $product = $this->service->create($this->store, ['title' => 'Test Product']);

    $this->service->delete($product);

    expect(Product::withoutGlobalScopes()->find($product->id))->toBeNull();
});

it('prevents deletion of product with order references', function () {
    $product = $this->service->create($this->store, ['title' => 'Test Product']);

    if (\Illuminate\Support\Facades\Schema::hasTable('order_lines')) {
        $variant = $product->variants()->first();
        \Illuminate\Support\Facades\DB::table('order_lines')->insert([
            'order_id' => 1,
            'variant_id' => $variant->id,
            'product_id' => $product->id,
            'title' => 'Test',
            'quantity' => 1,
            'unit_price_amount' => 2999,
            'subtotal_amount' => 2999,
            'total_amount' => 2999,
        ]);

        expect(fn () => $this->service->delete($product))
            ->toThrow(InvalidProductTransitionException::class);

        expect(Product::withoutGlobalScopes()->find($product->id))->not->toBeNull();
    } else {
        // Without order_lines table, deletion should succeed
        $this->service->delete($product);
        expect(Product::withoutGlobalScopes()->find($product->id))->toBeNull();
    }
});

it('prevents deletion of non-draft products', function () {
    $product = $this->service->create($this->store, ['title' => 'Test Product']);
    $product->variants()->first()->update(['price_amount' => 2999]);
    $this->service->transitionStatus($product, ProductStatus::Active);

    expect(fn () => $this->service->delete($product))
        ->toThrow(InvalidProductTransitionException::class);

    expect(Product::withoutGlobalScopes()->find($product->id))->not->toBeNull();
});

it('filters products by status', function () {
    for ($i = 0; $i < 3; $i++) {
        $p = $this->service->create($this->store, ['title' => "Active Product $i"]);
        $p->variants()->first()->update(['price_amount' => 2999]);
        $this->service->transitionStatus($p, ProductStatus::Active);
    }

    for ($i = 0; $i < 2; $i++) {
        $this->service->create($this->store, ['title' => "Draft Product $i"]);
    }

    $activeProducts = Product::where('status', ProductStatus::Active)->get();
    expect($activeProducts)->toHaveCount(3);
});

it('searches products by title', function () {
    $this->service->create($this->store, ['title' => 'Organic Cotton Hoodie']);
    $this->service->create($this->store, ['title' => 'Silk Blouse']);

    $results = Product::where('title', 'like', '%cotton%')->get();
    expect($results)->toHaveCount(1)
        ->and($results->first()->title)->toBe('Organic Cotton Hoodie');
});
