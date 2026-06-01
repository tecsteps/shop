<?php

use App\Enums\StoreUserRole;
use App\Livewire\Admin\Orders\Index;
use App\Livewire\Admin\Orders\Show;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->owner = $this->context['owner'];
});

/**
 * Create a paid order in the current store with one line and a captured payment.
 */
function paidOrderWithLine(int $total = 5000): Order
{
    $store = app('current_store');
    $variant = makeSellableVariant(['price' => $total]);

    $order = Order::factory()->paid()->create([
        'store_id' => $store->id,
        'total_amount' => $total,
        'subtotal_amount' => $total,
        'shipping_amount' => 0,
    ]);

    OrderLine::factory()->create([
        'order_id' => $order->id,
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => $total,
        'total_amount' => $total,
    ]);

    Payment::factory()->create([
        'order_id' => $order->id,
        'amount' => $total,
    ]);

    return $order->fresh();
}

it('lists orders with status filter', function (): void {
    actingAsAdmin($this->owner, $this->store);

    Order::factory()->count(3)->create(['store_id' => $this->store->id, 'financial_status' => 'pending', 'status' => 'pending']);
    Order::factory()->count(2)->paid()->create(['store_id' => $this->store->id]);

    $component = Livewire::test(Index::class)->set('statusFilter', 'paid');

    expect($component->instance()->orders->total())->toBe(2);
});

it('shows the order detail page', function (): void {
    actingAsAdmin($this->owner, $this->store);

    $order = paidOrderWithLine();

    Livewire::test(Show::class, ['order' => $order])
        ->assertOk()
        ->assertSee($order->order_number);
});

it('creates a fulfillment from order detail', function (): void {
    actingAsAdmin($this->owner, $this->store);

    $order = paidOrderWithLine();
    $line = $order->lines->first();

    Livewire::test(Show::class, ['order' => $order])
        ->call('openFulfillmentModal')
        ->set('fulfillmentLines.'.$line->id, 1)
        ->call('createFulfillment');

    expect($order->fresh()->fulfillments()->count())->toBe(1);
});

it('processes a refund from order detail', function (): void {
    actingAsAdmin($this->owner, $this->store);

    $order = paidOrderWithLine(5000);

    Livewire::test(Show::class, ['order' => $order])
        ->call('openRefundModal')
        ->set('refundAmount', '25.00')
        ->set('refundRestock', false)
        ->call('createRefund');

    expect($order->fresh()->refunds()->count())->toBe(1);
    expect((int) $order->fresh()->refunds()->sum('amount'))->toBe(2500);
});

it('confirms a bank transfer payment from order detail', function (): void {
    actingAsAdmin($this->owner, $this->store);

    $store = $this->store;
    $variant = makeSellableVariant(['price' => 5000]);

    $order = Order::factory()->bankTransfer()->create(['store_id' => $store->id, 'total_amount' => 5000]);
    OrderLine::factory()->create([
        'order_id' => $order->id, 'store_id' => $store->id, 'variant_id' => $variant->id,
        'quantity' => 1, 'unit_price_amount' => 5000, 'total_amount' => 5000,
    ]);
    Payment::factory()->pending()->create(['order_id' => $order->id, 'amount' => 5000]);

    Livewire::test(Show::class, ['order' => $order->fresh()])
        ->call('confirmPayment');

    expect($order->fresh()->financial_status->value)->toBe('paid');
});

it('restricts order management by role', function (): void {
    $support = User::factory()->create();
    $this->store->users()->attach($support->id, ['role' => StoreUserRole::Support->value]);
    actingAsAdmin($support, $this->store);

    $order = paidOrderWithLine();

    // Support can view orders (read-only).
    Livewire::test(Show::class, ['order' => $order])->assertOk();

    // Support cannot fulfill.
    Livewire::test(Show::class, ['order' => $order])
        ->call('openFulfillmentModal')
        ->set('fulfillmentLines.'.$order->lines->first()->id, 1)
        ->call('createFulfillment')
        ->assertForbidden();
});
