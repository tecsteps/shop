<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Livewire\Admin\Orders\Index;
use App\Livewire\Admin\Orders\Show;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use Livewire\Livewire;
use Tests\Support\CommerceTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    ['store' => $this->store, 'user' => $this->user] = $this->createStoreContext();
    $this->actingAsAdmin($this->user, $this->store);
});

function createAdminOrder($store, array $overrides = []): Order
{
    $customer = Customer::factory()->create(['store_id' => $store->getKey()]);
    $variant = CommerceTestHelper::purchasableVariant($store);

    $order = Order::factory()->create(array_merge([
        'store_id' => $store->getKey(),
        'customer_id' => $customer->getKey(),
        'payment_method' => PaymentMethod::CreditCard,
        'status' => OrderStatus::Paid,
        'financial_status' => FinancialStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Unfulfilled,
        'subtotal_amount' => 5000,
        'discount_amount' => 0,
        'shipping_amount' => 499,
        'tax_amount' => 800,
        'total_amount' => 6299,
        'placed_at' => now(),
    ], $overrides));

    OrderLine::factory()->create([
        'order_id' => $order->getKey(),
        'product_id' => $variant->product_id,
        'variant_id' => $variant->getKey(),
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'total_amount' => 5000,
    ]);

    return $order;
}

it('lists orders scoped to the current store', function () {
    $order = createAdminOrder($this->store);

    $this->get(route('admin.orders.index'))
        ->assertOk()
        ->assertSee($order->order_number);
});

it('searches orders by number and email', function () {
    $order = createAdminOrder($this->store, ['email' => 'buyer@example.com']);
    createAdminOrder($this->store, ['email' => 'other@example.com']);

    Livewire::test(Index::class)
        ->set('search', 'buyer@example.com')
        ->assertSee($order->order_number)
        ->assertDontSee('other@example.com');
});

it('shows order detail with lines and payments', function () {
    $order = createAdminOrder($this->store);
    Payment::factory()->create([
        'order_id' => $order->getKey(),
        'method' => PaymentMethod::CreditCard,
        'status' => PaymentStatus::Captured,
        'amount' => $order->total_amount,
    ]);

    $this->get(route('admin.orders.show', $order))
        ->assertOk()
        ->assertSee($order->order_number);
});

it('confirms bank transfer payments', function () {
    $order = createAdminOrder($this->store, [
        'payment_method' => PaymentMethod::BankTransfer,
        'status' => OrderStatus::Pending,
        'financial_status' => FinancialStatus::Pending,
    ]);
    Payment::factory()->create([
        'order_id' => $order->getKey(),
        'method' => PaymentMethod::BankTransfer,
        'status' => PaymentStatus::Pending,
        'amount' => $order->total_amount,
    ]);

    Livewire::test(Show::class, ['order' => $order])
        ->call('confirmPayment')
        ->assertDispatched('toast');

    expect($order->refresh()->financial_status)->toBe(FinancialStatus::Paid);
});

it('creates a fulfillment for paid orders', function () {
    $order = createAdminOrder($this->store);
    $lineId = $order->orderLines()->first()->getKey();

    Livewire::test(Show::class, ['order' => $order])
        ->call('openFulfillmentModal')
        ->assertSet('fulfillmentLines.'.$lineId, 2)
        ->set('trackingCompany', 'DHL')
        ->set('trackingNumber', 'DHL123')
        ->call('createFulfillment')
        ->assertDispatched('toast');

    expect($order->fulfillments()->count())->toBe(1)
        ->and($order->refresh()->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled);
});

it('blocks fulfillment for unpaid bank transfer orders', function () {
    $order = createAdminOrder($this->store, [
        'payment_method' => PaymentMethod::BankTransfer,
        'status' => OrderStatus::Pending,
        'financial_status' => FinancialStatus::Pending,
    ]);

    Livewire::test(Show::class, ['order' => $order])
        ->call('openFulfillmentModal')
        ->call('createFulfillment')
        ->assertDispatched('toast');

    expect($order->fulfillments()->count())->toBe(0);
});

it('creates a partial refund', function () {
    $order = createAdminOrder($this->store);
    Payment::factory()->create([
        'order_id' => $order->getKey(),
        'method' => PaymentMethod::CreditCard,
        'status' => PaymentStatus::Captured,
        'amount' => $order->total_amount,
    ]);
    $lineId = $order->orderLines()->first()->getKey();

    Livewire::test(Show::class, ['order' => $order])
        ->call('openRefundModal')
        ->set('refundSelected.'.$lineId, true)
        ->set('refundLines.'.$lineId, 1)
        ->set('refundReason', 'Item returned')
        ->call('createRefund')
        ->assertDispatched('toast');

    expect($order->refunds()->count())->toBe(1)
        ->and($order->refresh()->financial_status)->toBe(FinancialStatus::PartiallyRefunded);
});

it('returns 404 for an order from another store', function () {
    ['store' => $otherStore] = $this->createStoreContext();
    $order = createAdminOrder($otherStore);

    $this->get(route('admin.orders.show', $order))->assertNotFound();
});
