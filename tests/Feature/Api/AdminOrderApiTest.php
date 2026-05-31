<?php

use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->owner = $this->context['owner'];
});

function adminOrdersUrl(int $storeId, string $suffix = ''): string
{
    return "/api/admin/v1/stores/{$storeId}/orders".$suffix;
}

it('lists orders with authentication', function () {
    Order::factory()->count(3)->create(['store_id' => $this->store->id]);

    Sanctum::actingAs($this->owner, ['read-orders']);

    $this->getJson(adminOrdersUrl($this->store->id))
        ->assertSuccessful()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['data', 'meta']);
});

it('retrieves a single order', function () {
    $order = Order::factory()->create(['store_id' => $this->store->id]);
    OrderLine::factory()->create(['order_id' => $order->id, 'store_id' => $this->store->id]);
    Payment::factory()->create(['order_id' => $order->id, 'amount' => $order->total_amount]);

    Sanctum::actingAs($this->owner, ['read-orders']);

    $this->getJson(adminOrdersUrl($this->store->id, "/{$order->id}"))
        ->assertSuccessful()
        ->assertJsonStructure(['data' => ['id', 'lines', 'payments', 'fulfillments', 'refunds']]);
});

it('filters orders by status', function () {
    Order::factory()->count(2)->create(['store_id' => $this->store->id, 'status' => 'paid', 'financial_status' => 'paid']);
    Order::factory()->count(3)->bankTransfer()->create(['store_id' => $this->store->id]);

    Sanctum::actingAs($this->owner, ['read-orders']);

    $this->getJson(adminOrdersUrl($this->store->id).'?status=paid')
        ->assertSuccessful()
        ->assertJsonCount(2, 'data');
});

it('creates a fulfillment via API', function () {
    $order = Order::factory()->paid()->create(['store_id' => $this->store->id]);
    $line = OrderLine::factory()->create([
        'order_id' => $order->id,
        'store_id' => $this->store->id,
        'quantity' => 2,
    ]);

    Sanctum::actingAs($this->owner, ['write-orders']);

    $this->postJson(adminOrdersUrl($this->store->id, "/{$order->id}/fulfillments"), [
        'tracking_company' => 'DHL',
        'tracking_number' => '1234567890',
        'line_items' => [
            ['order_line_id' => $line->id, 'quantity' => 2],
        ],
    ])
        ->assertCreated()
        ->assertJsonPath('data.tracking_company', 'DHL');

    $this->assertDatabaseHas('fulfillments', ['order_id' => $order->id]);
});

it('creates a refund via API', function () {
    $order = Order::factory()->paid()->create(['store_id' => $this->store->id, 'total_amount' => 5000]);
    OrderLine::factory()->create(['order_id' => $order->id, 'store_id' => $this->store->id]);
    Payment::factory()->create(['order_id' => $order->id, 'amount' => 5000]);

    Sanctum::actingAs($this->owner, ['write-orders']);

    $this->postJson(adminOrdersUrl($this->store->id, "/{$order->id}/refunds"), [
        'amount' => 2000,
        'reason' => 'Customer requested',
    ])
        ->assertCreated()
        ->assertJsonPath('data.amount', 2000);

    $this->assertDatabaseHas('refunds', ['order_id' => $order->id, 'amount' => 2000]);
});

it('requires write-orders ability for mutations', function () {
    $order = Order::factory()->paid()->create(['store_id' => $this->store->id]);
    $line = OrderLine::factory()->create(['order_id' => $order->id, 'store_id' => $this->store->id]);

    Sanctum::actingAs($this->owner, ['read-orders']);

    $this->postJson(adminOrdersUrl($this->store->id, "/{$order->id}/fulfillments"), [
        'line_items' => [['order_line_id' => $line->id, 'quantity' => 1]],
    ])->assertForbidden();
});
