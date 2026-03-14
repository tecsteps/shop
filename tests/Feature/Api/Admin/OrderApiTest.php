<?php

use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $context = createStoreContext();
    $this->store = $context['store'];
    $this->user = $context['user'];
});

it('lists orders for a store', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);
    Order::factory()->count(3)->create([
        'store_id' => $this->store->id,
        'customer_id' => $customer->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/admin/v1/stores/'.$this->store->id.'/orders');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('filters orders by status', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);
    Order::factory()->paid()->count(2)->create([
        'store_id' => $this->store->id,
        'customer_id' => $customer->id,
    ]);
    Order::factory()->create([
        'store_id' => $this->store->id,
        'customer_id' => $customer->id,
        'status' => OrderStatus::Pending,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/admin/v1/stores/'.$this->store->id.'/orders?status=paid');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

it('filters orders by financial status', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);
    Order::factory()->create([
        'store_id' => $this->store->id,
        'customer_id' => $customer->id,
        'financial_status' => FinancialStatus::Paid,
    ]);
    Order::factory()->create([
        'store_id' => $this->store->id,
        'customer_id' => $customer->id,
        'financial_status' => FinancialStatus::Pending,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/admin/v1/stores/'.$this->store->id.'/orders?financial_status=paid');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

it('shows a single order with relations', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);
    $order = Order::factory()->paid()->create([
        'store_id' => $this->store->id,
        'customer_id' => $customer->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/admin/v1/stores/'.$this->store->id.'/orders/'.$order->id);

    $response->assertOk()
        ->assertJsonPath('data.id', $order->id)
        ->assertJsonPath('data.order_number', $order->order_number)
        ->assertJsonStructure(['data' => ['lines', 'payments', 'fulfillments', 'refunds']]);
});

it('rejects unauthenticated access', function () {
    $response = $this->getJson('/api/admin/v1/stores/'.$this->store->id.'/orders');

    $response->assertUnauthorized();
});

it('returns 404 for order from another store', function () {
    $otherStore = \App\Models\Store::factory()->create();
    $customer = Customer::factory()->create(['store_id' => $otherStore->id]);
    $order = Order::factory()->create([
        'store_id' => $otherStore->id,
        'customer_id' => $customer->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/admin/v1/stores/'.$this->store->id.'/orders/'.$order->id);

    $response->assertNotFound();
});
