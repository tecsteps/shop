<?php

use App\Models\Order;

beforeEach(function () {
    $this->ctx = createStoreContext();
});

it('lists orders page', function () {
    Order::factory()->count(3)->for($this->ctx['store'])->create();

    $response = actingAsAdmin($this->ctx['user'])
        ->withSession(['current_store_id' => $this->ctx['store']->id])
        ->get('/admin/orders');

    $response->assertStatus(200);
});

it('shows order detail page', function () {
    $order = Order::factory()->for($this->ctx['store'])->create();

    $response = actingAsAdmin($this->ctx['user'])
        ->withSession(['current_store_id' => $this->ctx['store']->id])
        ->get("/admin/orders/{$order->id}");

    $response->assertStatus(200);
});
