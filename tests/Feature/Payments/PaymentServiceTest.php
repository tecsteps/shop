<?php

use App\Contracts\PaymentProvider;
use App\Enums\FinancialStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\OrderService;
use App\Services\Payment\MockPaymentProvider;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->orderService = app(OrderService::class);
});

it('processes credit card payment and creates order as paid', function () {
    $checkout = createCheckoutWithLines($this->store, 'credit_card');

    $order = $this->orderService->createFromCheckout($checkout, [
        'card_number' => '4242424242424242',
    ]);

    expect($order->financial_status)->toBe(FinancialStatus::Paid)
        ->and($order->status)->toBe(\App\Enums\OrderStatus::Paid);

    // Verify inventory was committed
    $checkout->loadMissing('cart.lines.variant.inventoryItem');
    foreach ($checkout->cart->lines as $line) {
        $item = $line->variant->inventoryItem->fresh();
        expect($item->quantity_reserved)->toBe(0);
    }
});

it('processes PayPal payment and creates order as paid', function () {
    $checkout = createCheckoutWithLines($this->store, 'paypal');

    $order = $this->orderService->createFromCheckout($checkout, []);

    expect($order->financial_status)->toBe(FinancialStatus::Paid)
        ->and($order->status)->toBe(\App\Enums\OrderStatus::Paid);
});

it('processes bank transfer and creates order as pending', function () {
    $checkout = createCheckoutWithLines($this->store, 'bank_transfer');

    $order = $this->orderService->createFromCheckout($checkout, []);

    expect($order->financial_status)->toBe(FinancialStatus::Pending)
        ->and($order->status)->toBe(\App\Enums\OrderStatus::Pending);

    // Verify inventory stays reserved (not committed)
    $checkout->loadMissing('cart.lines.variant.inventoryItem');
    foreach ($checkout->cart->lines as $line) {
        $item = $line->variant->inventoryItem->fresh();
        expect($item->quantity_reserved)->toBeGreaterThan(0);
    }
});

it('resolves MockPaymentProvider from container', function () {
    $provider = app(PaymentProvider::class);

    expect($provider)->toBeInstanceOf(MockPaymentProvider::class);
});

it('creates a payment record with correct method', function () {
    $checkout = createCheckoutWithLines($this->store, 'credit_card');

    $order = $this->orderService->createFromCheckout($checkout, [
        'card_number' => '4242424242424242',
    ]);

    $payment = $order->payments()->first();

    expect($payment)->not->toBeNull()
        ->and($payment->method)->toBe(PaymentMethod::CreditCard)
        ->and($payment->provider)->toBe('mock')
        ->and($payment->status)->toBe(PaymentStatus::Captured);
});

/**
 * Helper to create a checkout with cart lines ready for order creation.
 */
function createCheckoutWithLines(\App\Models\Store $store, string $paymentMethod): Checkout
{
    $cart = Cart::factory()->create(['store_id' => $store->id, 'currency' => 'EUR']);
    $product = Product::factory()->active()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 2500,
    ]);
    InventoryItem::factory()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 2,
    ]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 5000,
        'line_discount_amount' => 0,
        'line_total_amount' => 5000,
    ]);

    return Checkout::factory()->paymentSelected()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'payment_method' => $paymentMethod,
        'email' => 'test@example.com',
        'totals_json' => [
            'subtotal' => 5000,
            'discount' => 0,
            'shipping' => 799,
            'taxTotal' => 950,
            'total' => 6749,
            'currency' => 'EUR',
        ],
    ]);
}
