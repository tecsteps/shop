<?php

use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->store = $this->ctx['store'];
    $this->user = $this->ctx['user'];
});

it('lists orders with authentication', function () {
    Order::factory()->count(3)->create(['store_id' => $this->store->id]);

    $token = $this->user->createToken('test', ['read-orders']);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token->plainTextToken,
    ])->getJson("/api/admin/v1/stores/{$this->store->id}/orders");

    $response->assertOk()
        ->assertJsonStructure(['data', 'meta']);
});

it('retrieves a single order', function () {
    $order = Order::factory()->paid()->create(['store_id' => $this->store->id]);
    OrderLine::factory()->create(['order_id' => $order->id]);
    Payment::factory()->create([
        'order_id' => $order->id,
        'amount' => $order->total_amount,
        'currency' => $order->currency,
    ]);

    $token = $this->user->createToken('test', ['read-orders']);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token->plainTextToken,
    ])->getJson("/api/admin/v1/stores/{$this->store->id}/orders/{$order->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $order->id)
        ->assertJsonStructure(['data' => ['lines', 'payments', 'fulfillments', 'refunds']]);
});

it('filters orders by status', function () {
    Order::factory()->paid()->count(2)->create(['store_id' => $this->store->id]);
    Order::factory()->pending()->create(['store_id' => $this->store->id]);

    $token = $this->user->createToken('test', ['read-orders']);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token->plainTextToken,
    ])->getJson("/api/admin/v1/stores/{$this->store->id}/orders?status=paid");

    $response->assertOk()
        ->assertJsonCount(2, 'data');

    collect($response->json('data'))->each(function ($order) {
        expect($order['status'])->toBe('paid');
    });
});

it('creates a fulfillment via API', function () {
    $order = Order::factory()->paid()->create(['store_id' => $this->store->id]);
    $line = OrderLine::factory()->create([
        'order_id' => $order->id,
        'quantity' => 2,
    ]);
    Payment::factory()->create([
        'order_id' => $order->id,
        'amount' => $order->total_amount,
    ]);

    $token = $this->user->createToken('test', ['write-orders', 'read-orders']);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token->plainTextToken,
    ])->postJson("/api/admin/v1/stores/{$this->store->id}/orders/{$order->id}/fulfillments", [
        'tracking_company' => 'DHL',
        'tracking_number' => '123456',
        'line_items' => [
            ['order_line_id' => $line->id, 'quantity' => 2],
        ],
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.tracking_company', 'DHL')
        ->assertJsonPath('data.tracking_number', '123456');
});

it('creates a refund via API', function () {
    $order = Order::factory()->paid()->create([
        'store_id' => $this->store->id,
        'total_amount' => 5000,
    ]);
    Payment::factory()->create([
        'order_id' => $order->id,
        'amount' => 5000,
        'currency' => $order->currency,
    ]);

    $token = $this->user->createToken('test', ['write-orders', 'read-orders']);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token->plainTextToken,
    ])->postJson("/api/admin/v1/stores/{$this->store->id}/orders/{$order->id}/refunds", [
        'amount' => 2500,
        'reason' => 'Customer return',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.amount', 2500)
        ->assertJsonPath('data.reason', 'Customer return');
});

it('requires write-orders ability for mutations', function () {
    $order = Order::factory()->paid()->create(['store_id' => $this->store->id]);
    $line = OrderLine::factory()->create(['order_id' => $order->id]);

    $token = $this->user->createToken('test', ['read-orders']);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token->plainTextToken,
    ])->postJson("/api/admin/v1/stores/{$this->store->id}/orders/{$order->id}/fulfillments", [
        'line_items' => [
            ['order_line_id' => $line->id, 'quantity' => 1],
        ],
    ]);

    $response->assertForbidden();
});
