<?php

use App\Models\Product;
use App\Models\Store;
use App\Models\User;

function adminFor(Store $store): User
{
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    return $user;
}

it('lists products with pagination', function () {
    $store = Store::factory()->create();
    $user = adminFor($store);
    Product::factory()->count(5)->create(['store_id' => $store->id]);

    $this->actingAs($user)->withSession(['current_store_id' => $store->id])->get('/admin/products')
        ->assertStatus(200);
});

it('owner can create products', function () {
    $store = Store::factory()->create();
    $user = adminFor($store);
    app()->instance('current_store', $store);

    expect($user->can('create', Product::class))->toBeTrue();
});

it('renders the product create form', function () {
    $store = Store::factory()->create();
    $user = adminFor($store);

    $this->actingAs($user)->withSession(['current_store_id' => $store->id])->get('/admin/products/create')
        ->assertStatus(200);
});

it('staff can create but not delete products', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'staff']);
    $product = Product::factory()->create(['store_id' => $store->id]);
    app()->instance('current_store', $store);

    expect($user->can('delete', $product))->toBeFalse();
});
