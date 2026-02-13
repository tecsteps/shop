<?php

use App\Enums\FinancialStatus;
use App\Enums\RefundStatus;
use App\Enums\ShippingRateType;
use App\Enums\VariantStatus;
use App\Events\OrderRefunded;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\CheckoutService;
use App\Services\RefundService;
use Illuminate\Support\Facades\Event;

function createPaidOrderForRefund(): array
{
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $product = Product::factory()->for($store)->active()->create();
    $variant = ProductVariant::factory()->for($product)->create([
        'price_amount' => 5000,
        'status' => VariantStatus::Active,
        'weight_g' => 500,
    ]);

    InventoryItem::factory()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 50,
        'quantity_reserved' => 0,
    ]);

    $cart = Cart::factory()->for($store)->create();
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price_amount' => 5000,
        'line_subtotal_amount' => 10000,
        'line_total_amount' => 10000,
    ]);

    $zone = ShippingZone::factory()->for($store)->create([
        'countries_json' => ['US'],
    ]);

    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 499],
    ]);

    $service = app(CheckoutService::class);
    $checkout = $service->createCheckout($cart);
    $checkout = $service->setAddress($checkout, [
        'email' => 'refund-test@example.com',
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'Los Angeles',
            'province_code' => 'CA',
            'country_code' => 'US',
            'postal_code' => '90001',
        ],
    ]);
    $checkout = $service->setShippingMethod($checkout, $rate->id);
    $checkout = $service->selectPaymentMethod($checkout, 'credit_card');
    $order = $service->completeCheckout($checkout, [
        'card_number' => '4242424242424242',
    ]);

    $payment = $order->payments()->first();

    return array_merge($ctx, [
        'product' => $product,
        'variant' => $variant,
        'order' => $order,
        'payment' => $payment,
    ]);
}

it('creates a full refund', function () {
    $ctx = createPaidOrderForRefund();
    $order = $ctx['order'];
    $payment = $ctx['payment'];

    $refundService = app(RefundService::class);
    $refund = $refundService->create($order, $payment, $payment->amount);

    $order->refresh();

    expect($refund->amount)->toBe($payment->amount)
        ->and($refund->status)->toBe(RefundStatus::Processed)
        ->and($order->financial_status)->toBe(FinancialStatus::Refunded);
});

it('creates a partial refund', function () {
    $ctx = createPaidOrderForRefund();
    $order = $ctx['order'];
    $payment = $ctx['payment'];

    $partialAmount = (int) floor($payment->amount / 2);

    $refundService = app(RefundService::class);
    $refund = $refundService->create($order, $payment, $partialAmount);

    $order->refresh();

    expect($refund->amount)->toBe($partialAmount)
        ->and($order->financial_status)->toBe(FinancialStatus::PartiallyRefunded);
});

it('rejects refund exceeding payment amount', function () {
    $ctx = createPaidOrderForRefund();
    $order = $ctx['order'];
    $payment = $ctx['payment'];

    $refundService = app(RefundService::class);

    expect(fn () => $refundService->create($order, $payment, $payment->amount + 100))
        ->toThrow(\InvalidArgumentException::class);
});

it('restocks inventory when restock flag is true', function () {
    $ctx = createPaidOrderForRefund();
    $order = $ctx['order'];
    $payment = $ctx['payment'];
    $variant = $ctx['variant'];

    $inventoryBefore = InventoryItem::withoutGlobalScopes()
        ->where('variant_id', $variant->id)
        ->first();
    $onHandBefore = $inventoryBefore->quantity_on_hand;

    $refundService = app(RefundService::class);
    $refundService->create($order, $payment, $payment->amount, 'Damaged item', true);

    $inventoryAfter = InventoryItem::withoutGlobalScopes()
        ->where('variant_id', $variant->id)
        ->first();

    // Each order line had quantity 2, so restock should add 2
    expect($inventoryAfter->quantity_on_hand)->toBe($onHandBefore + 2);
});

it('does not restock when restock flag is false', function () {
    $ctx = createPaidOrderForRefund();
    $order = $ctx['order'];
    $payment = $ctx['payment'];
    $variant = $ctx['variant'];

    $inventoryBefore = InventoryItem::withoutGlobalScopes()
        ->where('variant_id', $variant->id)
        ->first();
    $onHandBefore = $inventoryBefore->quantity_on_hand;

    $refundService = app(RefundService::class);
    $refundService->create($order, $payment, $payment->amount, null, false);

    $inventoryAfter = InventoryItem::withoutGlobalScopes()
        ->where('variant_id', $variant->id)
        ->first();

    expect($inventoryAfter->quantity_on_hand)->toBe($onHandBefore);
});

it('records refund reason', function () {
    $ctx = createPaidOrderForRefund();
    $order = $ctx['order'];
    $payment = $ctx['payment'];

    $refundService = app(RefundService::class);
    $refund = $refundService->create($order, $payment, 1000, 'Customer requested');

    expect($refund->reason)->toBe('Customer requested');
});

it('dispatches OrderRefunded event', function () {
    Event::fake([OrderRefunded::class]);

    $ctx = createPaidOrderForRefund();
    $order = $ctx['order'];
    $payment = $ctx['payment'];

    $refundService = app(RefundService::class);
    $refundService->create($order, $payment, 1000);

    Event::assertDispatched(OrderRefunded::class, function (OrderRefunded $event) use ($order) {
        return $event->order->id === $order->id
            && $event->refund->amount === 1000;
    });
});
