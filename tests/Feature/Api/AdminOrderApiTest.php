<?php

use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->user = $this->context['user'];
    $this->baseUrl = "/api/admin/v1/stores/{$this->store->getKey()}/orders";
});

/**
 * Authorization headers for a token with the given abilities.
 *
 * @param  list<string>  $abilities
 * @return array<string, string>
 */
function orderApiHeaders(array $abilities = ['read-orders', 'write-orders']): array
{
    return ['Authorization' => 'Bearer '.test()->user->createToken('test', $abilities)->plainTextToken];
}

/**
 * A paid order with one line (qty 2) and a captured payment.
 */
function paidOrderWithLine(): Order
{
    $order = Order::factory()->paid()->for(test()->store)->create([
        'subtotal_amount' => 5000,
        'total_amount' => 5000,
    ]);

    OrderLine::factory()->for($order)->create([
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'total_amount' => 5000,
    ]);

    Payment::factory()->captured()->for($order)->create(['amount' => 5000]);

    return $order;
}

it('lists orders with authentication', function () {
    Order::factory()->count(2)->paid()->for($this->store)->create();

    $this->getJson($this->baseUrl, orderApiHeaders())
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure(['data' => [['id', 'order_number', 'status', 'total_amount']], 'meta']);
});

it('retrieves a single order', function () {
    $order = paidOrderWithLine();

    $this->getJson("{$this->baseUrl}/{$order->getKey()}", orderApiHeaders())
        ->assertOk()
        ->assertJsonPath('data.id', $order->getKey())
        ->assertJsonCount(1, 'data.lines')
        ->assertJsonCount(1, 'data.payments')
        ->assertJsonCount(0, 'data.fulfillments');
});

it('filters orders by status', function () {
    Order::factory()->paid()->for($this->store)->create();
    Order::factory()->pending()->for($this->store)->create();

    $this->getJson("{$this->baseUrl}?status=paid", orderApiHeaders())
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'paid');
});

it('creates a fulfillment via API', function () {
    $order = paidOrderWithLine();
    $line = $order->lines()->first();

    $this->postJson("{$this->baseUrl}/{$order->getKey()}/fulfillments", [
        'tracking_company' => 'DHL',
        'tracking_number' => '1234567890',
        'line_items' => [
            ['order_line_id' => $line->getKey(), 'quantity' => 2],
        ],
    ], orderApiHeaders())
        ->assertCreated()
        ->assertJsonPath('data.status', 'shipped')
        ->assertJsonPath('data.tracking_company', 'DHL')
        ->assertJsonPath('data.line_items.0.quantity', 2);

    $this->assertDatabaseHas('fulfillments', [
        'order_id' => $order->getKey(),
        'tracking_number' => '1234567890',
    ]);
});

it('creates a refund via API', function () {
    $order = paidOrderWithLine();

    $this->postJson("{$this->baseUrl}/{$order->getKey()}/refunds", [
        'amount' => 2500,
        'reason' => 'Customer requested return for 1 item',
    ], orderApiHeaders())
        ->assertCreated()
        ->assertJsonPath('data.amount', 2500)
        ->assertJsonPath('data.status', 'processed');

    $this->assertDatabaseHas('refunds', [
        'order_id' => $order->getKey(),
        'amount' => 2500,
    ]);
});

it('requires write-orders ability for mutations', function () {
    $order = paidOrderWithLine();
    $line = $order->lines()->first();

    $this->postJson("{$this->baseUrl}/{$order->getKey()}/fulfillments", [
        'line_items' => [
            ['order_line_id' => $line->getKey(), 'quantity' => 1],
        ],
    ], orderApiHeaders(['read-orders']))
        ->assertForbidden();
});
