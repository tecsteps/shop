<?php

use App\Enums\StoreUserRole;
use App\Livewire\Admin\Orders\Index as OrdersIndex;
use App\Livewire\Admin\Orders\Show as OrderShow;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->user = $this->context['user'];
});

it('lists orders with status filter', function () {
    Order::factory()->count(3)->pending()->for($this->store)->create();
    Order::factory()->count(2)->paid()->for($this->store)->create();

    actingAsAdmin($this->user);

    $component = Livewire::test(OrdersIndex::class)
        ->call('setStatusFilter', 'paid');

    expect($component->instance()->orders()->total())->toBe(2);

    $component->call('setStatusFilter', 'all');

    expect($component->instance()->orders()->total())->toBe(5);
});

it('shows order detail page', function () {
    $order = Order::factory()->paid()->for($this->store)->totaling(5000)->create(['currency' => 'EUR']);
    $line = OrderLine::factory()->for($order)->create([
        'title_snapshot' => 'Blue Shirt (M)',
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'total_amount' => 5000,
    ]);

    actingAsAdmin($this->user)
        ->get("/admin/orders/{$order->getKey()}")
        ->assertOk()
        ->assertSee($order->order_number)
        ->assertSee('Blue Shirt (M)')
        ->assertSee('50.00 EUR');
});

it('creates a fulfillment from order detail', function () {
    $order = Order::factory()->paid()->for($this->store)->create();
    $line = OrderLine::factory()->for($order)->create(['quantity' => 2]);

    actingAsAdmin($this->user);

    Livewire::test(OrderShow::class, ['order' => $order->getKey()])
        ->set("fulfillmentLines.{$line->getKey()}.selected", true)
        ->set("fulfillmentLines.{$line->getKey()}.quantity", 2)
        ->set('trackingCompany', 'DHL')
        ->set('trackingNumber', 'TRACK-123')
        ->call('createFulfillment')
        ->assertDispatched('toast');

    $this->assertDatabaseHas('fulfillments', [
        'order_id' => $order->getKey(),
        'tracking_company' => 'DHL',
        'tracking_number' => 'TRACK-123',
    ]);

    $this->assertDatabaseHas('fulfillment_lines', [
        'order_line_id' => $line->getKey(),
        'quantity' => 2,
    ]);

    expect($order->refresh()->fulfillment_status->value)->toBe('fulfilled');
});

it('processes a refund from order detail', function () {
    $order = Order::factory()->paid()->for($this->store)->totaling(5000)->create();
    OrderLine::factory()->for($order)->create(['quantity' => 1, 'total_amount' => 5000]);
    $payment = Payment::factory()->captured()->for($order)->create(['amount' => 5000]);

    actingAsAdmin($this->user);

    Livewire::test(OrderShow::class, ['order' => $order->getKey()])
        ->set('refundAmount', '10.00')
        ->set('refundReason', 'Damaged item')
        ->call('createRefund')
        ->assertDispatched('toast');

    $this->assertDatabaseHas('refunds', [
        'order_id' => $order->getKey(),
        'payment_id' => $payment->getKey(),
        'amount' => 1000,
        'reason' => 'Damaged item',
        'status' => 'processed',
    ]);

    expect($order->refresh()->financial_status->value)->toBe('partially_refunded');
});

it('restricts order management by role', function () {
    $support = createStoreMember($this->store, StoreUserRole::Support);

    $order = Order::factory()->paid()->for($this->store)->create();
    $line = OrderLine::factory()->for($order)->create(['quantity' => 1]);
    Payment::factory()->captured()->for($order)->create();

    actingAsAdmin($support)
        ->get('/admin/orders')
        ->assertOk();

    actingAsAdmin($support)
        ->get("/admin/orders/{$order->getKey()}")
        ->assertOk();

    Livewire::test(OrderShow::class, ['order' => $order->getKey()])
        ->set("fulfillmentLines.{$line->getKey()}.selected", true)
        ->call('createFulfillment')
        ->assertForbidden();

    Livewire::test(OrderShow::class, ['order' => $order->getKey()])
        ->set('refundAmount', '5.00')
        ->call('createRefund')
        ->assertForbidden();

    $this->assertDatabaseMissing('fulfillments', ['order_id' => $order->getKey()]);
    $this->assertDatabaseMissing('refunds', ['order_id' => $order->getKey()]);
});
