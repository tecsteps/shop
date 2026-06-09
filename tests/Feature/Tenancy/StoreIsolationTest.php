<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\Scopes\StoreScope;
use App\Models\Store;

it('scopes product queries to the current store', function () {
    $storeA = Store::factory()->create();
    $storeB = Store::factory()->create();

    Product::factory()->count(3)->for($storeA)->create();
    Product::factory()->count(5)->for($storeB)->create();

    app()->instance('current_store', $storeA);

    expect(Product::all())->toHaveCount(3);
    expect(Product::query()->count())->toBe(3);
});

it('scopes order queries to the current store')->todo('Phase 5: Order model does not exist yet');

it('automatically sets store_id on product creation', function () {
    $context = createStoreContext();

    $product = Product::query()->create([
        'title' => 'Isolation Product',
        'handle' => 'isolation-product',
    ]);

    expect($product->store_id)->toBe($context['store']->getKey());
});

it('prevents accessing another stores products via direct ID', function () {
    $storeA = Store::factory()->create();
    $storeB = Store::factory()->create();

    $product = Product::factory()->for($storeA)->create();

    app()->instance('current_store', $storeB);

    expect(Product::query()->find($product->getKey()))->toBeNull();
});

it('allows cross-store product access when global scope is removed', function () {
    $storeA = Store::factory()->create();
    $storeB = Store::factory()->create();

    Product::factory()->count(2)->for($storeA)->create();
    Product::factory()->count(3)->for($storeB)->create();

    app()->instance('current_store', $storeA);

    expect(Product::query()->withoutGlobalScope(StoreScope::class)->count())->toBe(5);
});

it('scopes store-bound queries to the current store', function () {
    $storeA = Store::factory()->create();
    $storeB = Store::factory()->create();

    Customer::factory()->count(3)->for($storeA)->create();
    Customer::factory()->count(5)->for($storeB)->create();

    app()->instance('current_store', $storeA);

    expect(Customer::all())->toHaveCount(3);
    expect(Customer::query()->count())->toBe(3);
});

it('automatically sets store_id on model creation', function () {
    $context = createStoreContext();

    $customer = Customer::query()->create([
        'email' => 'isolation@example.test',
        'name' => 'Isolation Test',
    ]);

    expect($customer->store_id)->toBe($context['store']->getKey());
});

it('prevents accessing another stores records via direct ID', function () {
    $storeA = Store::factory()->create();
    $storeB = Store::factory()->create();

    $customer = Customer::factory()->for($storeA)->create();

    app()->instance('current_store', $storeB);

    expect(Customer::query()->find($customer->getKey()))->toBeNull();
});

it('allows cross-store access when global scope is removed', function () {
    $storeA = Store::factory()->create();
    $storeB = Store::factory()->create();

    Customer::factory()->count(2)->for($storeA)->create();
    Customer::factory()->count(3)->for($storeB)->create();

    app()->instance('current_store', $storeA);

    expect(Customer::query()->withoutGlobalScope(StoreScope::class)->count())->toBe(5);
});
