<?php

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\InventoryPolicy;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Livewire\Admin\Orders\Index as OrdersIndex;
use App\Livewire\Admin\Orders\Show as OrdersShow;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use Livewire\Livewire;

function makeAdminOrder(int $storeId, array $overrides = []): Order
{
    $product = Product::factory()->create(['store_id' => $storeId, 'status' => ProductStatus::Active]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2000, 'requires_shipping' => true]);
    InventoryItem::factory()->create([
        'store_id' => $storeId,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'policy' => InventoryPolicy::Deny,
    ]);

    $cart = Cart::factory()->create(['store_id' => $storeId, 'currency' => 'USD', 'status' => CartStatus::Active]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 2000,
        'line_total_amount' => 2000,
    ]);
    $checkout = Checkout::factory()->create([
        'store_id' => $storeId,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::Completed,
        'email' => 'buyer@example.com',
    ]);

    $order = Order::factory()->create(array_merge([
        'store_id' => $storeId,
        'checkout_id' => $checkout->id,
        'email' => 'buyer@example.com',
        'status' => OrderStatus::Paid,
        'financial_status' => FinancialStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Unfulfilled,
        'payment_method' => PaymentMethod::CreditCard,
        'subtotal_amount' => 2000,
        'total_amount' => 2000,
        'placed_at' => now(),
    ], $overrides));

    OrderLine::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'title_snapshot' => $product->title,
        'sku_snapshot' => $variant->sku,
        'quantity' => 1,
        'unit_price_amount' => 2000,
        'total_amount' => 2000,
    ]);

    Payment::create([
        'order_id' => $order->id,
        'provider' => 'mock',
        'method' => $order->payment_method,
        'status' => PaymentStatus::Captured,
        'amount' => $order->total_amount,
        'currency' => 'USD',
        'created_at' => now(),
    ]);

    return $order->fresh(['lines.variant.product', 'payments', 'refunds', 'fulfillments.lines']);
}

it('lists and filters orders', function (): void {
    $ctx = $this->createStoreContext(['hostname' => 'om-list.test']);
    $pending = makeAdminOrder($ctx['store']->id, ['status' => OrderStatus::Pending, 'financial_status' => FinancialStatus::Pending]);
    $paid = makeAdminOrder($ctx['store']->id, ['status' => OrderStatus::Paid]);

    $this->actingAsAdmin($ctx['owner'], $ctx['store']);

    Livewire::test(OrdersIndex::class)
        ->assertSee('#'.$pending->order_number)
        ->assertSee('#'.$paid->order_number)
        ->set('status', 'paid')
        ->assertSee('#'.$paid->order_number)
        ->assertDontSee('#'.$pending->order_number);
});

it('shows order detail page', function (): void {
    $ctx = $this->createStoreContext(['hostname' => 'om-show.test']);
    $order = makeAdminOrder($ctx['store']->id);
    $this->actingAsAdmin($ctx['owner'], $ctx['store']);

    Livewire::test(OrdersShow::class, ['order' => $order])
        ->assertSee('Order #'.$order->order_number)
        ->assertSee('buyer@example.com');
});

it('fulfills order lines via the modal', function (): void {
    $ctx = $this->createStoreContext(['hostname' => 'om-fulfill.test']);
    $order = makeAdminOrder($ctx['store']->id);
    $this->actingAsAdmin($ctx['owner'], $ctx['store']);

    Livewire::test(OrdersShow::class, ['order' => $order])
        ->call('openFulfillment')
        ->call('fulfill');

    $fresh = $order->fresh(['fulfillments.lines']);
    expect($fresh->fulfillments->count())->toBe(1);
    expect($fresh->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled);
});

it('refunds an order fully via the modal', function (): void {
    $ctx = $this->createStoreContext(['hostname' => 'om-refund.test']);
    $order = makeAdminOrder($ctx['store']->id);
    $this->actingAsAdmin($ctx['owner'], $ctx['store']);

    Livewire::test(OrdersShow::class, ['order' => $order])
        ->call('openRefund')
        ->set('refundAmount', $order->total_amount)
        ->call('refund');

    $fresh = $order->fresh(['refunds']);
    expect($fresh->refunds->count())->toBe(1);
    expect($fresh->financial_status)->toBe(FinancialStatus::Refunded);
});

it('confirms bank transfer payment', function (): void {
    $ctx = $this->createStoreContext(['hostname' => 'om-bt.test']);
    $order = makeAdminOrder($ctx['store']->id, [
        'status' => OrderStatus::Pending,
        'financial_status' => FinancialStatus::Pending,
        'payment_method' => PaymentMethod::BankTransfer,
    ]);
    $order->payments()->update(['status' => PaymentStatus::Pending]);

    $this->actingAsAdmin($ctx['owner'], $ctx['store']);

    Livewire::test(OrdersShow::class, ['order' => $order])
        ->call('confirmBankTransfer');

    $fresh = $order->fresh();
    expect($fresh->financial_status)->toBe(FinancialStatus::Paid);
    expect($fresh->status)->toBe(OrderStatus::Paid);
});

it('cancels an order', function (): void {
    $ctx = $this->createStoreContext(['hostname' => 'om-cancel.test']);
    $order = makeAdminOrder($ctx['store']->id, [
        'status' => OrderStatus::Pending,
        'financial_status' => FinancialStatus::Pending,
        'payment_method' => PaymentMethod::BankTransfer,
    ]);

    $this->actingAsAdmin($ctx['owner'], $ctx['store']);

    Livewire::test(OrdersShow::class, ['order' => $order])
        ->call('cancelOrder');

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled);
});
