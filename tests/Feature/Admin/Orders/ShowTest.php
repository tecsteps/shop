<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Livewire\Admin\Orders\Show;
use App\Models\Customer;
use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Store;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->store = Store::factory()->create();
    $this->user->stores()->attach($this->store->id, ['role' => 'owner']);
    $this->actingAs($this->user);
    session(['current_store_id' => $this->store->id]);
    app()->instance('current_store', $this->store);

    $this->customer = Customer::factory()->create(['store_id' => $this->store->id]);
    $this->order = Order::factory()->create([
        'store_id' => $this->store->id,
        'customer_id' => $this->customer->id,
        'order_number' => '4001',
        'total_amount' => 5497,
        'subtotal_amount' => 4998,
        'shipping_amount' => 499,
        'tax_amount' => 798,
        'discount_amount' => 798,
    ]);
});

test('order show page can be rendered', function () {
    $response = $this->get(route('admin.orders.show', $this->order));

    $response->assertOk();
    $response->assertSeeLivewire(Show::class);
});

test('order show displays order number and status', function () {
    Livewire::test(Show::class, ['order' => $this->order])
        ->assertSee('#4001')
        ->assertSee('Paid');
});

test('order show displays customer info', function () {
    Livewire::test(Show::class, ['order' => $this->order])
        ->assertSee($this->customer->full_name)
        ->assertSee($this->customer->email);
});

test('order show displays order lines', function () {
    $line = OrderLine::factory()->create([
        'order_id' => $this->order->id,
        'title_snapshot' => 'Test Product',
        'sku_snapshot' => 'SKU-1234',
        'quantity' => 2,
        'unit_price_amount' => 2499,
        'total_amount' => 4998,
    ]);

    Livewire::test(Show::class, ['order' => $this->order])
        ->assertSee('Test Product')
        ->assertSee('SKU-1234')
        ->assertSee('24.99 EUR')
        ->assertSee('49.98 EUR');
});

test('order show displays formatted totals', function () {
    Livewire::test(Show::class, ['order' => $this->order])
        ->assertSee('49.98 EUR')
        ->assertSee('4.99 EUR')
        ->assertSee('7.98 EUR')
        ->assertSee('54.97 EUR');
});

test('order show displays payments', function () {
    Payment::factory()->create([
        'order_id' => $this->order->id,
        'amount' => 5497,
        'status' => 'captured',
    ]);

    Livewire::test(Show::class, ['order' => $this->order])
        ->assertSee('54.97 EUR')
        ->assertSeeHtml('Captured');
});

test('order show displays fulfillments', function () {
    $line = OrderLine::factory()->create([
        'order_id' => $this->order->id,
        'title_snapshot' => 'Shipped Item',
        'quantity' => 1,
    ]);
    $fulfillment = Fulfillment::factory()->create([
        'order_id' => $this->order->id,
        'tracking_number' => 'TRACK123',
        'tracking_company' => 'DHL',
    ]);
    FulfillmentLine::factory()->create([
        'fulfillment_id' => $fulfillment->id,
        'order_line_id' => $line->id,
        'quantity' => 1,
    ]);

    Livewire::test(Show::class, ['order' => $this->order])
        ->assertSee('TRACK123')
        ->assertSee('DHL');
});

test('order show displays refunds', function () {
    $payment = Payment::factory()->create(['order_id' => $this->order->id]);
    Refund::factory()->create([
        'order_id' => $this->order->id,
        'payment_id' => $payment->id,
        'amount' => 1000,
        'reason' => 'Damaged item',
        'status' => 'processed',
    ]);

    Livewire::test(Show::class, ['order' => $this->order])
        ->assertSee('10.00 EUR')
        ->assertSee('Damaged item');
});

test('order show displays shipping address', function () {
    Livewire::test(Show::class, ['order' => $this->order])
        ->assertSee($this->order->shipping_address_json['address1']);
});

test('order show hides fulfill button when fully fulfilled', function () {
    $this->order->update(['fulfillment_status' => FulfillmentStatus::Fulfilled]);

    Livewire::test(Show::class, ['order' => $this->order])
        ->assertDontSeeHtml('wire:click="openFulfillModal"');
});

test('order show hides action buttons when cancelled', function () {
    $this->order->update(['status' => OrderStatus::Cancelled]);

    Livewire::test(Show::class, ['order' => $this->order])
        ->assertDontSeeHtml('wire:click="openFulfillModal"')
        ->assertDontSeeHtml('wire:click="openRefundModal"');
});

test('order show can open fulfill modal', function () {
    OrderLine::factory()->create([
        'order_id' => $this->order->id,
        'title_snapshot' => 'Product to Fulfill',
        'quantity' => 2,
    ]);

    Livewire::test(Show::class, ['order' => $this->order])
        ->call('openFulfillModal')
        ->assertSet('showFulfillModal', true);
});

test('order show can open refund modal', function () {
    Livewire::test(Show::class, ['order' => $this->order])
        ->call('openRefundModal')
        ->assertSet('showRefundModal', true);
});

test('order show can open cancel modal', function () {
    Livewire::test(Show::class, ['order' => $this->order])
        ->call('openCancelModal')
        ->assertSet('showCancelModal', true);
});

test('order show can cancel order', function () {
    Livewire::test(Show::class, ['order' => $this->order])
        ->set('cancelReason', 'Customer requested cancellation')
        ->call('cancelOrder')
        ->assertSet('showCancelModal', false)
        ->assertDispatched('toast');

    expect($this->order->fresh()->status)->toBe(OrderStatus::Cancelled);
    expect($this->order->fresh()->cancel_reason)->toBe('Customer requested cancellation');
});

test('order show cannot cancel already cancelled order', function () {
    $this->order->update(['status' => OrderStatus::Cancelled]);

    Livewire::test(Show::class, ['order' => $this->order])
        ->call('cancelOrder')
        ->assertDispatched('toast', type: 'error', message: 'This order is already cancelled.');
});

test('order show blocks access to orders from other stores', function () {
    $otherStore = Store::factory()->create();
    $otherCustomer = Customer::factory()->create(['store_id' => $otherStore->id]);
    $otherOrder = Order::factory()->create([
        'store_id' => $otherStore->id,
        'customer_id' => $otherCustomer->id,
    ]);

    Livewire::test(Show::class, ['order' => $otherOrder])
        ->assertStatus(404);
});

test('order show can create fulfillment', function () {
    $this->order->update(['financial_status' => FinancialStatus::Paid]);
    $line = OrderLine::factory()->create([
        'order_id' => $this->order->id,
        'quantity' => 2,
        'fulfilled_quantity' => 0,
    ]);

    Livewire::test(Show::class, ['order' => $this->order])
        ->call('openFulfillModal')
        ->set('fulfillLines.'.$line->id, 2)
        ->set('trackingNumber', 'TRACK-456')
        ->set('carrier', 'DHL')
        ->call('createFulfillment')
        ->assertSet('showFulfillModal', false)
        ->assertDispatched('toast');

    $this->order->refresh();
    expect($this->order->fulfillments()->count())->toBe(1);
    expect($this->order->fulfillments()->first()->tracking_number)->toBe('TRACK-456');
});
