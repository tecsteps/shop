<?php

use App\Models\Product;

it('renders a storefront only for a registered active host', function () {
    shopStore('shop-one.test');

    $this->get('http://shop-one.test/')
        ->assertOk();

    $this->get('http://unknown.test/')
        ->assertNotFound();
});

it('returns a maintenance response for a suspended storefront', function () {
    $store = shopStore('suspended.test');
    $store->update(['status' => 'suspended']);

    $this->get('http://suspended.test/')
        ->assertServiceUnavailable();
});

it('limits store-scoped product queries to the resolved tenant', function () {
    $firstStore = shopStore('first.test');
    $firstProduct = shopProduct($firstStore)['product'];
    $secondStore = shopStore('second.test');
    $secondProduct = shopProduct($secondStore)['product'];

    app()->instance('current_store', $firstStore);

    expect(Product::query()->pluck('id')->all())->toBe([$firstProduct->id])
        ->and(Product::withoutGlobalScopes()->count())->toBe(2);

    app()->instance('current_store', $secondStore);

    expect(Product::query()->pluck('id')->all())->toBe([$secondProduct->id]);
});
