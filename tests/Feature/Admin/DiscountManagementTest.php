<?php

use App\Models\Discount;
use App\Models\Store;
use App\Models\User;

it('lists discounts', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    Discount::factory()->count(3)->create(['store_id' => $store->id]);

    $this->actingAs($user)->withSession(['current_store_id' => $store->id])->get('/admin/discounts')
        ->assertStatus(200);
});

it('owner can create discounts', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    app()->instance('current_store', $store);

    expect($user->can('create', Discount::class))->toBeTrue();
});

it('renders the discount create form', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    $this->actingAs($user)->withSession(['current_store_id' => $store->id])->get('/admin/discounts/create')
        ->assertStatus(200);
});
