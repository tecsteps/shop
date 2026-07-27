<?php

use App\Models\Customer;

test('queries on tenant models only return records for the bound store', function () {
    $storeA = $this->createStore();
    $storeB = $this->createStore();

    Customer::factory()->count(2)->create(['store_id' => $storeA->id]);
    Customer::factory()->count(3)->create(['store_id' => $storeB->id]);

    $this->bindStore($storeA);

    expect(Customer::query()->count())->toBe(2)
        ->and(Customer::query()->pluck('store_id')->unique()->values()->all())->toBe([$storeA->id]);
});

test('queries are unscoped when no store is bound', function () {
    $storeA = $this->createStore();
    $storeB = $this->createStore();

    Customer::factory()->count(2)->create(['store_id' => $storeA->id]);
    Customer::factory()->create(['store_id' => $storeB->id]);

    expect(Customer::query()->count())->toBe(3);
});

test('creating a tenant model sets store_id from the bound store', function () {
    $store = $this->createStore();
    $this->bindStore($store);

    $customer = Customer::factory()->create(['store_id' => null]);

    expect($customer->store_id)->toBe($store->id);
});

test('an explicit store_id is not overridden by the bound store', function () {
    $storeA = $this->createStore();
    $storeB = $this->createStore();
    $this->bindStore($storeA);

    $customer = Customer::factory()->create(['store_id' => $storeB->id]);

    expect($customer->store_id)->toBe($storeB->id);
});
