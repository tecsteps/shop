<?php

use App\Models\Store;
use App\Models\User;

it('lists orders with status filter', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    \App\Models\Order::factory()->count(2)->create(['store_id' => $store->id, 'status' => 'paid']);

    $this->actingAs($user)->withSession(['current_store_id' => $store->id])->get('/admin/orders')
        ->assertStatus(200);
});

it('shows order detail page', function () {
    $order = makeCompletedOrder();
    $store = $order->store;
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    $this->actingAs($user)->withSession(['current_store_id' => $store->id])->get('/admin/orders/'.$order->id)
        ->assertStatus(200);
});
