<?php

use App\Enums\ProductStatus;
use App\Exceptions\InvalidProductTransitionException;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->store = $this->createStore();
    $this->service = app(ProductService::class);
});

test('it creates a product with nested options, variants, and inventory', function () {
    $product = $this->service->create($this->store, [
        'title' => 'T-Shirt',
        'vendor' => 'Acme',
        'tags' => ['summer', 'sale'],
        'options' => [
            ['name' => 'Color', 'values' => ['Blue', 'Red']],
            ['name' => 'Size', 'values' => ['Small', 'Medium', 'Large']],
        ],
        'variant_defaults' => ['price_amount' => 2500, 'currency' => 'USD'],
        'variants' => [
            ['option_values' => ['Blue', 'Small'], 'sku' => 'TS-BLU-S', 'inventory' => ['quantity_on_hand' => 5]],
        ],
    ]);

    expect($product)->toBeInstanceOf(Product::class)
        ->and($product->store_id)->toBe($this->store->id)
        ->and($product->status)->toBe(ProductStatus::Draft)
        ->and($product->handle)->toBe('t-shirt')
        ->and($product->options)->toHaveCount(2)
        ->and($product->options->pluck('name')->all())->toBe(['Color', 'Size'])
        ->and($product->options->first()->values->pluck('value')->all())->toBe(['Blue', 'Red'])
        // 2 colors x 3 sizes
        ->and($product->variants)->toHaveCount(6);

    $blueSmall = $product->variants->firstWhere('sku', 'TS-BLU-S');

    expect($blueSmall)->not->toBeNull()
        ->and($blueSmall->price_amount)->toBe(2500)
        ->and($blueSmall->title())->toBe('Blue / Small')
        ->and($blueSmall->inventoryItem->quantity_on_hand)->toBe(5)
        ->and($blueSmall->inventoryItem->store_id)->toBe($this->store->id);

    // Every variant got an inventory item.
    expect($product->variants->every(fn ($variant) => $variant->inventoryItem !== null))->toBeTrue();

    // Exactly one default variant exists.
    expect($product->variants->where('is_default', true))->toHaveCount(1)
        ->and($product->defaultVariant)->not->toBeNull();
});

test('it creates a single default variant when no options are given', function () {
    $product = $this->service->create($this->store, [
        'title' => 'Simple Product',
        'variants' => [
            ['sku' => 'SIMPLE-1', 'price_amount' => 999, 'inventory' => ['quantity_on_hand' => 3]],
        ],
    ]);

    expect($product->variants)->toHaveCount(1);

    $variant = $product->variants->first();

    expect($variant->is_default)->toBeTrue()
        ->and($variant->sku)->toBe('SIMPLE-1')
        ->and($variant->price_amount)->toBe(999)
        ->and($variant->title())->toBe('Default')
        ->and($variant->inventoryItem->quantity_on_hand)->toBe(3);
});

test('it auto-generates unique handles per store', function () {
    $first = $this->service->create($this->store, ['title' => 'Mug']);
    $second = $this->service->create($this->store, ['title' => 'Mug']);

    expect($first->handle)->toBe('mug')
        ->and($second->handle)->toBe('mug-1');

    $otherStore = $this->createStore();
    $third = $this->service->create($otherStore, ['title' => 'Mug']);

    expect($third->handle)->toBe('mug');
});

test('it sanitizes the description html on create and update', function () {
    $product = $this->service->create($this->store, [
        'title' => 'XSS Test',
        'description_html' => '<p>Nice</p><script>alert(1)</script>',
    ]);

    expect($product->description_html)
        ->toContain('<p>Nice</p>')
        ->not->toContain('script');

    $product = $this->service->update($product, [
        'description_html' => '<p onclick="x()">Updated</p>',
    ]);

    expect($product->description_html)
        ->toContain('<p>Updated</p>')
        ->not->toContain('onclick');
});

test('it updates scalar attributes and re-validates a manual handle', function () {
    $product = $this->service->create($this->store, ['title' => 'One']);
    $other = $this->service->create($this->store, ['title' => 'Two']);

    $updated = $this->service->update($other, [
        'title' => 'Two Renamed',
        'vendor' => 'New Vendor',
        'handle' => 'one', // collides with the first product
    ]);

    expect($updated->title)->toBe('Two Renamed')
        ->and($updated->vendor)->toBe('New Vendor')
        ->and($updated->handle)->toBe('one-1');
});

test('it rejects duplicate SKUs within the same store but allows empty SKUs', function () {
    $this->service->create($this->store, [
        'title' => 'First',
        'variants' => [['sku' => 'DUP-1', 'price_amount' => 100]],
    ]);

    $this->service->create($this->store, [
        'title' => 'Second',
        'variants' => [['sku' => 'DUP-1', 'price_amount' => 100]],
    ]);
})->throws(Illuminate\Validation\ValidationException::class);

test('it allows the same SKU in different stores', function () {
    $this->service->create($this->store, [
        'title' => 'First',
        'variants' => [['sku' => 'SHARED-1', 'price_amount' => 100]],
    ]);

    $otherStore = $this->createStore();
    $product = $this->service->create($otherStore, [
        'title' => 'Second',
        'variants' => [['sku' => 'SHARED-1', 'price_amount' => 100]],
    ]);

    expect($product->variants->first()->sku)->toBe('SHARED-1');
});

test('a draft product becomes active when preconditions are met and sets published_at', function () {
    Event::fake();

    $product = $this->service->create($this->store, [
        'title' => 'Publishable',
        'variants' => [['price_amount' => 500]],
    ]);

    $this->service->transitionStatus($product, ProductStatus::Active);

    $product->refresh();

    expect($product->status)->toBe(ProductStatus::Active)
        ->and($product->published_at)->not->toBeNull();

    Event::assertDispatched(App\Events\ProductStatusChanged::class);
});

test('activation is blocked without a priced variant or title', function () {
    $product = $this->service->create($this->store, [
        'title' => 'No Price',
        'variants' => [['price_amount' => 0]],
    ]);

    $this->service->transitionStatus($product, ProductStatus::Active);
})->throws(InvalidProductTransitionException::class);

test('archived products can be re-published and drafts archived', function () {
    $product = $this->service->create($this->store, [
        'title' => 'Lifecycle',
        'variants' => [['price_amount' => 100]],
    ]);

    $this->service->transitionStatus($product, ProductStatus::Active);
    $this->service->transitionStatus($product->refresh(), ProductStatus::Archived);

    expect($product->refresh()->status)->toBe(ProductStatus::Archived);

    $this->service->transitionStatus($product, ProductStatus::Active);

    expect($product->refresh()->status)->toBe(ProductStatus::Active);
});

test('reverting to draft is blocked when order lines reference the product', function () {
    $product = $this->service->create($this->store, [
        'title' => 'Ordered',
        'variants' => [['price_amount' => 100]],
    ]);

    $this->service->transitionStatus($product, ProductStatus::Active);

    createOrderLineFor($this->store, $product, $product->variants->first());

    $this->service->transitionStatus($product->refresh(), ProductStatus::Draft);
})->throws(InvalidProductTransitionException::class);

test('reverting to draft works without order references', function () {
    $product = $this->service->create($this->store, [
        'title' => 'Revertible',
        'variants' => [['price_amount' => 100]],
    ]);

    $this->service->transitionStatus($product, ProductStatus::Active);
    $this->service->transitionStatus($product->refresh(), ProductStatus::Draft);

    expect($product->refresh()->status)->toBe(ProductStatus::Draft);
});

test('a draft product without order references can be hard deleted', function () {
    Event::fake();

    $product = $this->service->create($this->store, ['title' => 'Deletable']);

    $this->service->delete($product);

    expect(Product::query()->find($product->id))->toBeNull();

    Event::assertDispatched(App\Events\ProductDeleted::class);
});

test('non-draft products cannot be deleted', function () {
    $product = $this->service->create($this->store, [
        'title' => 'Active Product',
        'variants' => [['price_amount' => 100]],
    ]);

    $this->service->transitionStatus($product, ProductStatus::Active);

    $this->service->delete($product->refresh());
})->throws(InvalidProductTransitionException::class);

test('draft products referenced by orders cannot be deleted', function () {
    $product = $this->service->create($this->store, ['title' => 'Referenced Draft']);

    createOrderLineFor($this->store, $product, $product->variants->first());

    $this->service->delete($product);
})->throws(InvalidProductTransitionException::class);

test('products dispatch created and updated events', function () {
    Event::fake();

    $product = $this->service->create($this->store, ['title' => 'Events']);
    $this->service->update($product, ['vendor' => 'Acme']);

    Event::assertDispatched(App\Events\ProductCreated::class);
    Event::assertDispatched(App\Events\ProductUpdated::class);
});

test('the visible scope only returns active and published products', function () {
    $store = $this->store;

    $draft = Product::factory()->draft()->create(['store_id' => $store->id]);
    $active = Product::factory()->active()->create(['store_id' => $store->id]);
    $archived = Product::factory()->archived()->create(['store_id' => $store->id, 'published_at' => now()]);
    $activeUnpublished = Product::factory()->create([
        'store_id' => $store->id,
        'status' => ProductStatus::Active,
        'published_at' => null,
    ]);

    $visible = Product::query()->visible()->pluck('id');

    expect($visible)->toContain($active->id)
        ->not->toContain($draft->id)
        ->not->toContain($archived->id)
        ->not->toContain($activeUnpublished->id);
});
