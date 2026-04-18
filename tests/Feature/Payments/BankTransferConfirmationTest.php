<?php

use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderPaid;
use App\Services\OrderService;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    $ctx = $this->createStoreContext();
    $this->store = $ctx['store'];
});

function prepBankOrder(mixed $ctx): \App\Models\Order
{
    $product = \App\Models\Product::factory()->create(['store_id' => $ctx->store->id, 'status' => \App\Enums\ProductStatus::Active]);
    $variant = \App\Models\ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 3000, 'requires_shipping' => true]);
    \App\Models\InventoryItem::factory()->create(['store_id' => $ctx->store->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 5, 'policy' => \App\Enums\InventoryPolicy::Deny]);

    $zone = \App\Models\ShippingZone::factory()->create(['store_id' => $ctx->store->id, 'countries_json' => ['US']]);
    $rate = \App\Models\ShippingRate::factory()->create(['zone_id' => $zone->id, 'type' => \App\Enums\ShippingRateType::Flat, 'config_json' => ['amount' => 500]]);

    $cart = app(\App\Services\CartService::class)->create($ctx->store);
    app(\App\Services\CartService::class)->addLine($cart, $variant->id, 1);
    $checkout = app(\App\Services\CheckoutService::class)->startFromCart($cart);
    app(\App\Services\CheckoutService::class)->setAddress($checkout, [
        'email' => 'a@b.co',
        'shipping_address' => [
            'first_name' => 'A', 'last_name' => 'B',
            'address1' => '1', 'city' => 'NY',
            'country_code' => 'US', 'zip' => '10001',
        ],
    ]);
    app(\App\Services\CheckoutService::class)->setShippingMethod($checkout->fresh(), $rate->id);
    app(\App\Services\CheckoutService::class)->selectPaymentMethod($checkout->fresh(), 'bank_transfer');

    return app(OrderService::class)->createFromCheckout($checkout->fresh());
}

it('marks the order as paid and commits inventory on confirmation', function (): void {
    Event::fake([OrderPaid::class]);

    $order = prepBankOrder($this);
    expect($order->financial_status)->toBe(FinancialStatus::Pending);
    expect($order->status)->toBe(OrderStatus::Pending);

    $variant = $order->lines()->first()->variant;
    expect($variant->fresh()->inventoryItem->quantity_reserved)->toBe(1);

    $updated = app(OrderService::class)->confirmBankTransferPayment($order->fresh());

    expect($updated->financial_status)->toBe(FinancialStatus::Paid);
    expect($updated->status)->toBe(OrderStatus::Paid);

    $payment = $updated->payments()->first();
    expect($payment->status)->toBe(PaymentStatus::Captured);

    $item = $variant->fresh()->inventoryItem;
    expect($item->quantity_on_hand)->toBe(4);
    expect($item->quantity_reserved)->toBe(0);

    Event::assertDispatched(OrderPaid::class);
});

it('is a no-op when order is not bank_transfer pending', function (): void {
    $order = \App\Models\Order::factory()->paid()->create(['store_id' => $this->store->id]);

    $result = app(OrderService::class)->confirmBankTransferPayment($order);

    expect($result->financial_status)->toBe(FinancialStatus::Paid);
});

it('cancels unpaid bank transfer orders via the job', function (): void {
    $order = prepBankOrder($this);
    $order->placed_at = now()->subDays(10);
    $order->save();

    (new \App\Jobs\CancelUnpaidBankTransferOrders)->handle(app(OrderService::class));

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Cancelled);
    expect($order->financial_status)->toBe(FinancialStatus::Voided);
});
