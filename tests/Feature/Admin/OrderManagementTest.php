<?php

use App\Enums\FinancialStatus;
use App\Enums\PaymentMethod;
use App\Livewire\Admin\Orders\Index;
use App\Livewire\Admin\Orders\Show;
use App\Models\Fulfillment;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use Livewire\Livewire;

it('renders the orders list page', function (): void {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    Order::factory()->count(3)->create(['store_id' => $ctx['store']->id]);

    Livewire::test(Index::class)
        ->assertOk()
        ->assertSee('Orders');
});

it('searches orders by order number', function (): void {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    Order::factory()->create(['store_id' => $ctx['store']->id, 'order_number' => '#FIND123']);
    Order::factory()->create(['store_id' => $ctx['store']->id, 'order_number' => '#OTHER456']);

    Livewire::test(Index::class)
        ->set('search', 'FIND123')
        ->assertSee('#FIND123')
        ->assertDontSee('#OTHER456');
});

it('renders order detail page', function (): void {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    $order = Order::factory()->create([
        'store_id' => $ctx['store']->id,
        'order_number' => '#DETAIL001',
    ]);

    Livewire::test(Show::class, ['order' => $order])
        ->assertOk()
        ->assertSee('#DETAIL001');
});

it('confirms bank transfer payment on order', function (): void {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    $order = Order::factory()->bankTransfer()->create([
        'store_id' => $ctx['store']->id,
    ]);

    Payment::factory()->create([
        'order_id' => $order->id,
        'status' => 'pending',
        'method' => PaymentMethod::BankTransfer,
    ]);

    Livewire::test(Show::class, ['order' => $order])
        ->call('confirmPayment')
        ->assertDispatched('toast');

    expect($order->fresh()->financial_status)->toBe(FinancialStatus::Paid);
});

it('creates a fulfillment for an order', function (): void {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    $order = Order::factory()->paid()->create([
        'store_id' => $ctx['store']->id,
    ]);

    $line = OrderLine::factory()->create([
        'order_id' => $order->id,
        'quantity' => 2,
    ]);

    Livewire::test(Show::class, ['order' => $order])
        ->set("fulfillmentLines.{$line->id}", 2)
        ->set('trackingNumber', 'TRACK123')
        ->call('createFulfillment')
        ->assertDispatched('toast');

    expect(Fulfillment::where('order_id', $order->id)->count())->toBe(1);
});
