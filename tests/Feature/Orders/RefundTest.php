<?php

use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Events\OrderRefunded;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\OrderService;
use App\Services\Payments\MockPaymentProvider;
use App\Services\RefundService;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    $ctx = $this->createStoreContext();
    $this->store = $ctx['store'];

    $product = \App\Models\Product::factory()->create(['store_id' => $this->store->id, 'status' => \App\Enums\ProductStatus::Active]);
    $this->variant = \App\Models\ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 5000, 'requires_shipping' => true]);
    \App\Models\InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $this->variant->id, 'quantity_on_hand' => 10, 'policy' => \App\Enums\InventoryPolicy::Deny]);

    $zone = \App\Models\ShippingZone::factory()->create(['store_id' => $this->store->id, 'countries_json' => ['US']]);
    $this->rate = \App\Models\ShippingRate::factory()->create(['zone_id' => $zone->id, 'type' => \App\Enums\ShippingRateType::Flat, 'config_json' => ['amount' => 0]]);
});

function makeOrder(mixed $ctx, int $quantity = 2): \App\Models\Order
{
    $cart = app(CartService::class)->create($ctx->store);
    app(CartService::class)->addLine($cart, $ctx->variant->id, $quantity);
    $checkout = app(CheckoutService::class)->startFromCart($cart);
    app(CheckoutService::class)->setAddress($checkout, [
        'email' => 'a@b.co',
        'shipping_address' => [
            'first_name' => 'A', 'last_name' => 'B',
            'address1' => '1', 'city' => 'NY',
            'country_code' => 'US', 'zip' => '10001',
        ],
    ]);
    app(CheckoutService::class)->setShippingMethod($checkout->fresh(), $ctx->rate->id);
    app(CheckoutService::class)->selectPaymentMethod($checkout->fresh(), 'credit_card');

    return app(OrderService::class)->createFromCheckout($checkout->fresh(), ['card_number' => MockPaymentProvider::CARD_SUCCESS]);
}

it('processes a full refund, marks order refunded, updates payment', function (): void {
    Event::fake([OrderRefunded::class]);

    $order = makeOrder($this);
    $payment = $order->payments()->first();
    $total = $order->total_amount;

    $refund = app(RefundService::class)->create($order, $payment, $total);

    expect($refund->status)->toBe(RefundStatus::Processed);
    expect($order->fresh()->financial_status)->toBe(FinancialStatus::Refunded);
    expect($order->fresh()->status)->toBe(OrderStatus::Refunded);
    expect($payment->fresh()->status)->toBe(PaymentStatus::Refunded);
    Event::assertDispatched(OrderRefunded::class);
});

it('marks partial refunds correctly', function (): void {
    $order = makeOrder($this);
    $payment = $order->payments()->first();

    app(RefundService::class)->create($order, $payment, 1000);

    expect($order->fresh()->financial_status)->toBe(FinancialStatus::PartiallyRefunded);
});

it('rejects refunds larger than the refundable balance', function (): void {
    $order = makeOrder($this);
    $payment = $order->payments()->first();

    expect(fn () => app(RefundService::class)->create($order, $payment, $order->total_amount + 1))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('restocks inventory when flagged', function (): void {
    $order = makeOrder($this, 2);
    $payment = $order->payments()->first();

    $before = $this->variant->fresh()->inventoryItem->quantity_on_hand;

    app(RefundService::class)->create($order, $payment, $order->total_amount, null, true);

    $after = $this->variant->fresh()->inventoryItem->quantity_on_hand;
    expect($after)->toBe($before + 2);
});
