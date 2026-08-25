<?php

use App\Models\Order;
use App\Models\Store;
use App\Models\User;

it('lists orders with authentication', function () {
    $order = makeCompletedOrder();
    $store = $order->store;
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    $token = $user->createToken('test', ['read-orders'])->plainTextToken;

    $this->withToken($token)->getJson('/api/admin/v1/stores/'.$store->id.'/orders')
        ->assertStatus(200)
        ->assertJsonPath('meta.total', 1);
});

it('retrieves a single order', function () {
    $order = makeCompletedOrder();
    $store = $order->store;
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    $token = $user->createToken('test', ['read-orders'])->plainTextToken;

    $this->withToken($token)->getJson('/api/admin/v1/stores/'.$store->id.'/orders/'.$order->id)
        ->assertStatus(200)
        ->assertJsonPath('data.order_number', $order->order_number);
});

it('creates a fulfillment via API', function () {
    $order = makeCompletedOrder();
    $store = $order->store;
    $line = $order->lines()->first();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    $token = $user->createToken('test', ['write-orders'])->plainTextToken;

    $this->withToken($token)->postJson('/api/admin/v1/stores/'.$store->id.'/orders/'.$order->id.'/fulfillments', [
        'line_items' => [['order_line_id' => $line->id, 'quantity' => $line->quantity]],
        'tracking_company' => 'DHL',
        'tracking_number' => '123456',
    ])->assertStatus(201);
});

it('creates a refund via API', function () {
    $order = makeCompletedOrder();
    $store = $order->store;
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    $token = $user->createToken('test', ['write-orders'])->plainTextToken;

    $this->withToken($token)->postJson('/api/admin/v1/stores/'.$store->id.'/orders/'.$order->id.'/refunds', [
        'amount' => 1000,
        'reason' => 'Return',
    ])->assertStatus(201);
});
