<?php

use App\Enums\CartStatus;
use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentFailedException;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\TaxSettings;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\OrderService;
use App\Services\Payments\MockPaymentProvider;
use App\Services\PaymentService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function bootOrderFixture(int $onHand = 10, int $qty = 1, bool $requiresShipping = true): array
{
    $store = Store::factory()->create();
    $product = Product::factory()->active()->create(['store_id' => $store->getKey()]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->getKey(),
        'price_amount' => 1500,
        'requires_shipping' => $requiresShipping ? 1 : 0,
    ]);
    InventoryItem::factory()->create([
        'store_id' => $store->getKey(),
        'variant_id' => $variant->getKey(),
        'quantity_on_hand' => $onHand,
        'quantity_reserved' => 0,
    ]);

    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, (int) $variant->getKey(), $qty);

    $zone = ShippingZone::factory()->create([
        'store_id' => $store->getKey(),
        'countries_json' => ['US'],
    ]);
    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->getKey(),
        'config_json' => ['amount' => 500],
    ]);

    TaxSettings::factory()->create([
        'store_id' => $store->getKey(),
        'config_json' => ['default_rate_bps' => 0],
    ]);

    $checkout = app(CheckoutService::class)->start($store, $cart);
    $checkout = app(CheckoutService::class)->setAddress($checkout, [
        'email' => 'buyer@example.test',
        'shipping_address' => [
            'first_name' => 'A',
            'last_name' => 'B',
            'address1' => '1 Main',
            'city' => 'City',
            'country_code' => 'US',
            'postal_code' => '99999',
        ],
    ]);

    if ($requiresShipping) {
        $checkout = app(CheckoutService::class)->setShippingMethod($checkout, (int) $rate->getKey());
    }

    return [$store, $cart, $variant, $checkout];
}

it('completes a successful card checkout', function () {
    [, $cart, $variant, $checkout] = bootOrderFixture(onHand: 5, qty: 2);

    app(CheckoutService::class)->selectPaymentMethod($checkout, PaymentMethod::CreditCard);
    $checkout->refresh();

    $result = app(PaymentService::class)->authorize($checkout, PaymentMethod::CreditCard, [
        'card_number' => MockPaymentProvider::CARD_SUCCESS,
    ]);
    $order = app(OrderService::class)->createFromCheckout($checkout);
    $payment = app(PaymentService::class)->recordPayment($order, PaymentMethod::CreditCard, $result);

    expect($order->status)->toBe(OrderStatus::Paid)
        ->and($order->financial_status)->toBe(FinancialStatus::Paid)
        ->and($order->fulfillment_status)->toBe(FulfillmentStatus::Unfulfilled)
        ->and($order->lines()->count())->toBe(1)
        ->and($order->lines()->first()->quantity)->toBe(2)
        ->and($payment->status)->toBe(PaymentStatus::Captured)
        ->and($cart->refresh()->status)->toBe(CartStatus::Converted)
        ->and((int) $variant->inventoryItem()->first()->quantity_on_hand)->toBe(3);
});

it('leaves no order and releases reservation on declined card', function () {
    [, , $variant, $checkout] = bootOrderFixture(onHand: 3, qty: 1);

    app(CheckoutService::class)->selectPaymentMethod($checkout, PaymentMethod::CreditCard);
    $checkout->refresh();

    expect($variant->inventoryItem()->first()->quantity_reserved)->toBe(1);

    try {
        app(PaymentService::class)->authorize($checkout, PaymentMethod::CreditCard, [
            'card_number' => MockPaymentProvider::CARD_DECLINE,
        ]);
    } catch (PaymentFailedException $e) {
        expect($e->errorCode)->toBe('card_declined');
    }

    expect(Order::query()->count())->toBe(0)
        ->and($variant->inventoryItem()->first()->quantity_reserved)->toBe(0);
});

it('returns insufficient_funds for the 9995 magic card', function () {
    [, , , $checkout] = bootOrderFixture();
    app(CheckoutService::class)->selectPaymentMethod($checkout, PaymentMethod::CreditCard);

    app(PaymentService::class)->authorize($checkout->refresh(), PaymentMethod::CreditCard, [
        'card_number' => MockPaymentProvider::CARD_INSUFFICIENT_FUNDS,
    ]);
})->throws(PaymentFailedException::class, 'insufficient_funds');

it('creates a bank-transfer order with pending financial status', function () {
    [, , $variant, $checkout] = bootOrderFixture(onHand: 10, qty: 1);

    app(CheckoutService::class)->selectPaymentMethod($checkout, PaymentMethod::BankTransfer);
    $checkout->refresh();

    $result = app(PaymentService::class)->authorize($checkout, PaymentMethod::BankTransfer, []);
    $order = app(OrderService::class)->createFromCheckout($checkout);
    $payment = app(PaymentService::class)->recordPayment($order, PaymentMethod::BankTransfer, $result);

    expect($order->financial_status)->toBe(FinancialStatus::Pending)
        ->and($order->status)->toBe(OrderStatus::Pending)
        ->and($payment->status)->toBe(PaymentStatus::Pending)
        ->and($variant->inventoryItem()->first()->quantity_on_hand)->toBe(10)
        ->and($variant->inventoryItem()->first()->quantity_reserved)->toBe(1);
});

it('auto-fulfills orders with only digital line items', function () {
    [, , , $checkout] = bootOrderFixture(onHand: 5, qty: 1, requiresShipping: false);

    app(CheckoutService::class)->selectPaymentMethod($checkout, PaymentMethod::CreditCard);
    $checkout->refresh();

    $result = app(PaymentService::class)->authorize($checkout, PaymentMethod::CreditCard, [
        'card_number' => MockPaymentProvider::CARD_SUCCESS,
    ]);
    $order = app(OrderService::class)->createFromCheckout($checkout);
    app(PaymentService::class)->recordPayment($order, PaymentMethod::CreditCard, $result);

    expect($order->refresh()->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled)
        ->and($order->fulfillments()->count())->toBe(1);
});

it('is idempotent on duplicate provider_payment_id', function () {
    [, , , $checkout] = bootOrderFixture();
    app(CheckoutService::class)->selectPaymentMethod($checkout, PaymentMethod::CreditCard);

    $result = app(PaymentService::class)->authorize($checkout->refresh(), PaymentMethod::CreditCard, [
        'card_number' => MockPaymentProvider::CARD_SUCCESS,
    ]);
    $order = app(OrderService::class)->createFromCheckout($checkout);

    $p1 = app(PaymentService::class)->recordPayment($order, PaymentMethod::CreditCard, $result);
    $p2 = app(PaymentService::class)->recordPayment($order, PaymentMethod::CreditCard, $result);

    expect($p1->getKey())->toBe($p2->getKey());
});
