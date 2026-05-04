<?php

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderCreated;
use App\Events\OrderPaid;
use App\Exceptions\PaymentFailedException;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\StoreSettings;
use App\Models\TaxSettings;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\PricingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

function orderCompletionStore(int $start = 1001): Store
{
    $store = Store::factory()->create();
    app()->instance('current_store', $store);

    StoreSettings::query()->create([
        'store_id' => $store->getKey(),
        'settings_json' => [
            'order_number_prefix' => '#',
            'order_number_start' => $start,
        ],
    ]);

    TaxSettings::withoutGlobalScopes()->create([
        'store_id' => $store->getKey(),
        'mode' => 'manual',
        'provider' => 'none',
        'prices_include_tax' => false,
        'config_json' => ['default_rate_bps' => 0, 'shipping_taxable' => false],
    ]);

    return $store;
}

function orderCompletionVariant(Store $store, int $price = 2500, int $stock = 10): ProductVariant
{
    $product = Product::factory()
        ->withDefaultVariant($price)
        ->create(['store_id' => $store->getKey()]);
    $variant = ProductVariant::withoutGlobalScopes()
        ->where('product_id', $product->getKey())
        ->firstOrFail();

    InventoryItem::withoutGlobalScopes()
        ->where('variant_id', $variant->getKey())
        ->update([
            'quantity_on_hand' => $stock,
            'quantity_reserved' => 0,
        ]);

    return $variant->refresh();
}

function orderCompletionShippingRate(Store $store): ShippingRate
{
    $zone = ShippingZone::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('name', 'Germany')
        ->first();

    if (! $zone instanceof ShippingZone) {
        $zone = ShippingZone::withoutGlobalScopes()->create([
            'store_id' => $store->getKey(),
            'name' => 'Germany',
            'countries_json' => ['DE'],
            'regions_json' => [],
        ]);
    }

    $rate = ShippingRate::withoutGlobalScopes()
        ->where('zone_id', $zone->getKey())
        ->where('name', 'Standard')
        ->first();

    if ($rate instanceof ShippingRate) {
        return $rate;
    }

    return ShippingRate::withoutGlobalScopes()->create([
        'zone_id' => $zone->getKey(),
        'name' => 'Standard',
        'type' => 'flat',
        'config_json' => ['amount' => 499],
        'is_active' => true,
    ]);
}

/**
 * @return array{0: Checkout, 1: ProductVariant, 2: \App\Models\Cart}
 */
function orderCompletionCheckout(Store $store, string $paymentMethod = 'credit_card', ?string $discountCode = null): array
{
    $variant = orderCompletionVariant($store);
    $rate = orderCompletionShippingRate($store);
    $cart = app(CartService::class)->create($store);

    app(CartService::class)->addLine($cart, $variant->getKey(), 2);

    $checkout = app(CheckoutService::class)->createFromCart($cart);
    $checkout = app(CheckoutService::class)->setAddress($checkout, [
        'email' => 'buyer@example.test',
        'shipping_address' => [
            'first_name' => 'Test',
            'last_name' => 'Buyer',
            'address1' => 'Main Street 1',
            'city' => 'Berlin',
            'country' => 'DE',
            'postal_code' => '10115',
        ],
    ]);
    $checkout = app(CheckoutService::class)->setShippingMethod($checkout, $rate->getKey());

    if ($discountCode !== null) {
        $checkout->forceFill(['discount_code' => $discountCode])->save();
        app(PricingEngine::class)->calculate($checkout);
    }

    $checkout = app(CheckoutService::class)->selectPaymentMethod($checkout, $paymentMethod);

    return [$checkout, $variant, $cart];
}

test('checkout completion creates a paid order and commits inventory', function () {
    $store = orderCompletionStore();
    $discount = Discount::factory()
        ->fixed(500)
        ->create([
            'store_id' => $store->getKey(),
            'code' => 'SAVE500',
        ]);
    [$checkout, $variant, $cart] = orderCompletionCheckout($store, discountCode: 'SAVE500');

    Event::fake();

    $order = app(CheckoutService::class)->completeCheckout($checkout, [
        'card_number' => '4242 4242 4242 4242',
    ]);

    expect($order->order_number)->toBe('#1001')
        ->and($order->status)->toBe(OrderStatus::Paid)
        ->and($order->financial_status)->toBe(FinancialStatus::Paid)
        ->and($order->total_amount)->toBe(4999)
        ->and($order->lines)->toHaveCount(1)
        ->and($order->lines->first()->title_snapshot)->toContain($variant->product->title)
        ->and($order->lines->first()->discount_allocations_json)->toBe([[
            'discount_id' => $discount->getKey(),
            'code' => 'SAVE500',
            'amount' => 500,
        ]])
        ->and($order->payments)->toHaveCount(1)
        ->and($order->payments->first()->status)->toBe(PaymentStatus::Captured)
        ->and($order->payments->first()->raw_json_encrypted['success'])->toBeTrue()
        ->and($cart->refresh()->status)->toBe(CartStatus::Converted)
        ->and($checkout->refresh()->status)->toBe(CheckoutStatus::Completed)
        ->and($discount->refresh()->usage_count)->toBe(1);

    $inventory = InventoryItem::withoutGlobalScopes()->where('variant_id', $variant->getKey())->firstOrFail();

    expect($inventory->quantity_on_hand)->toBe(8)
        ->and($inventory->quantity_reserved)->toBe(0);

    Event::assertDispatched(OrderCreated::class, fn (OrderCreated $event): bool => $event->order->is($order));
    Event::assertDispatched(OrderPaid::class, fn (OrderPaid $event): bool => $event->order->is($order));
});

test('checkout completion attributes automatic discounts on order lines', function () {
    $store = orderCompletionStore();
    $automatic = Discount::factory()
        ->create([
            'store_id' => $store->getKey(),
            'type' => DiscountType::Automatic,
            'code' => null,
            'value_type' => DiscountValueType::Percent,
            'value_amount' => 10,
        ]);
    [$checkout] = orderCompletionCheckout($store);

    $order = app(CheckoutService::class)->completeCheckout($checkout, [
        'card_number' => '4242 4242 4242 4242',
    ]);

    expect($order->discount_amount)->toBe(500)
        ->and($order->lines->first()->discount_allocations_json)->toBe([[
            'discount_id' => $automatic->getKey(),
            'code' => null,
            'amount' => 500,
        ]]);
});

test('checkout completion keeps explicit and automatic discount allocations separate', function () {
    $store = orderCompletionStore();
    $codeDiscount = Discount::factory()
        ->create([
            'store_id' => $store->getKey(),
            'code' => 'SAVE10',
            'value_type' => DiscountValueType::Percent,
            'value_amount' => 10,
        ]);
    $automatic = Discount::factory()
        ->create([
            'store_id' => $store->getKey(),
            'type' => DiscountType::Automatic,
            'code' => null,
            'value_type' => DiscountValueType::Percent,
            'value_amount' => 10,
        ]);
    [$checkout] = orderCompletionCheckout($store, discountCode: 'SAVE10');

    $order = app(CheckoutService::class)->completeCheckout($checkout, [
        'card_number' => '4242 4242 4242 4242',
    ]);

    expect($order->discount_amount)->toBe(950)
        ->and($order->lines->first()->discount_allocations_json)->toBe([
            [
                'discount_id' => $codeDiscount->getKey(),
                'code' => 'SAVE10',
                'amount' => 500,
            ],
            [
                'discount_id' => $automatic->getKey(),
                'code' => null,
                'amount' => 450,
            ],
        ]);
});

test('checkout completion is idempotent for the same checkout', function () {
    $store = orderCompletionStore();
    [$checkout, $variant] = orderCompletionCheckout($store);

    $order = app(CheckoutService::class)->completeCheckout($checkout, [
        'card_number' => '4242 4242 4242 4242',
    ]);
    $sameOrder = app(CheckoutService::class)->completeCheckout($checkout->refresh(), [
        'card_number' => '4000 0000 0000 0002',
    ]);

    expect($sameOrder->is($order))->toBeTrue()
        ->and(Order::withoutGlobalScopes()->count())->toBe(1)
        ->and(Payment::query()->count())->toBe(1)
        ->and(InventoryItem::withoutGlobalScopes()->where('variant_id', $variant->getKey())->first()?->quantity_on_hand)->toBe(8)
        ->and(InventoryItem::withoutGlobalScopes()->where('variant_id', $variant->getKey())->first()?->quantity_reserved)->toBe(0);
});

test('bank transfer completion creates a pending order and keeps inventory reserved', function () {
    $store = orderCompletionStore();
    [$checkout, $variant] = orderCompletionCheckout($store, paymentMethod: 'bank_transfer');

    Event::fake();

    $order = app(CheckoutService::class)->completeCheckout($checkout);

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($order->financial_status)->toBe(FinancialStatus::Pending)
        ->and($order->payments->first()->status)->toBe(PaymentStatus::Pending)
        ->and($checkout->refresh()->status)->toBe(CheckoutStatus::Completed);

    $inventory = InventoryItem::withoutGlobalScopes()->where('variant_id', $variant->getKey())->firstOrFail();

    expect($inventory->quantity_on_hand)->toBe(10)
        ->and($inventory->quantity_reserved)->toBe(2);

    Event::assertDispatched(OrderCreated::class);
    Event::assertNotDispatched(OrderPaid::class);
});

test('payment failures release reserved inventory and allow checkout retry', function () {
    $store = orderCompletionStore();
    [$checkout, $variant] = orderCompletionCheckout($store);

    expect(fn () => app(CheckoutService::class)->completeCheckout($checkout, [
        'card_number' => '4000 0000 0000 0002',
    ]))->toThrow(PaymentFailedException::class);

    $inventory = InventoryItem::withoutGlobalScopes()->where('variant_id', $variant->getKey())->firstOrFail();

    expect(Order::withoutGlobalScopes()->count())->toBe(0)
        ->and($inventory->quantity_on_hand)->toBe(10)
        ->and($inventory->quantity_reserved)->toBe(0)
        ->and($checkout->refresh()->status)->toBe(CheckoutStatus::ShippingSelected)
        ->and($checkout->payment_method)->toBeNull();
});

test('order numbers increment sequentially per store', function () {
    $store = orderCompletionStore(start: 5001);
    [$firstCheckout] = orderCompletionCheckout($store);
    [$secondCheckout] = orderCompletionCheckout($store);

    $firstOrder = app(CheckoutService::class)->completeCheckout($firstCheckout, [
        'card_number' => '4242 4242 4242 4242',
    ]);
    $secondOrder = app(CheckoutService::class)->completeCheckout($secondCheckout, [
        'card_number' => '4242 4242 4242 4242',
    ]);

    expect($firstOrder->order_number)->toBe('#5001')
        ->and($secondOrder->order_number)->toBe('#5002');
});
