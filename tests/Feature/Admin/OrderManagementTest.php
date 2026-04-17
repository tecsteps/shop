<?php

use App\Enums\FulfillmentStatus;
use App\Livewire\Admin\Orders\Index as OrderIndex;
use App\Livewire\Admin\Orders\Show as OrderShow;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\User;
use Livewire\Livewire;

it('lists orders with status filter', function () {
    $ctx = createStoreContext();

    Order::factory()->count(5)->create([
        'store_id' => $ctx['store']->id,
        'placed_at' => now(),
    ]);

    actingAsAdmin($ctx['user'], $ctx['store']);

    Livewire::test(OrderIndex::class)
        ->assertOk()
        ->assertSee('Orders');
});

it('shows order detail page', function () {
    $ctx = createStoreContext();

    $order = Order::factory()->create([
        'store_id' => $ctx['store']->id,
        'order_number' => '#7001',
    ]);

    OrderLine::factory()->create(['order_id' => $order->id]);

    actingAsAdmin($ctx['user'], $ctx['store']);

    Livewire::test(OrderShow::class, ['order' => $order])
        ->assertOk()
        ->assertSee('#7001');
});

it('creates a fulfillment from order detail', function () {
    $ctx = createStoreContext();

    $order = Order::factory()->paid()->create([
        'store_id' => $ctx['store']->id,
        'fulfillment_status' => FulfillmentStatus::Unfulfilled,
    ]);
    OrderLine::factory()->create(['order_id' => $order->id]);

    actingAsAdmin($ctx['user'], $ctx['store']);

    Livewire::test(OrderShow::class, ['order' => $order])
        ->call('openFulfillmentModal')
        ->set('trackingCompany', 'UPS')
        ->set('trackingNumber', '1Z999999')
        ->call('createFulfillment');

    $order->refresh();
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled)
        ->and($order->fulfillments()->count())->toBe(1);
});

it('processes a refund from order detail', function () {
    $ctx = createStoreContext();

    $order = Order::factory()->create([
        'store_id' => $ctx['store']->id,
        'total_amount' => 10000,
    ]);
    Payment::factory()->create([
        'order_id' => $order->id,
        'amount' => 10000,
    ]);

    actingAsAdmin($ctx['user'], $ctx['store']);

    Livewire::test(OrderShow::class, ['order' => $order])
        ->call('openRefundModal')
        ->set('refundAmount', 5000)
        ->set('refundReason', 'Customer request')
        ->call('processRefund')
        ->assertHasNoErrors();

    expect($order->refunds()->count())->toBe(1)
        ->and($order->refunds()->first()->amount)->toBe(5000);
});

it('restricts order management by role', function () {
    $ctx = createStoreContext();

    $unauthorizedUser = User::factory()->create();

    test()->actingAs($unauthorizedUser);
    session(['current_store_id' => $ctx['store']->id]);

    $this->get('/admin/orders')
        ->assertStatus(404);
});
