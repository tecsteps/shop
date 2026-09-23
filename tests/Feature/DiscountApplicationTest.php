<?php

use App\Enums\PaymentMethod;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Order;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Validation\ValidationException;

it('automatically stacks store discounts without a code and records each redemption', function () {
    $store = shopStore();
    app()->instance('current_store', $store);
    $percentDiscount = Discount::factory()->create([
        'store_id' => $store->id,
        'code' => null,
        'type' => 'percentage',
        'value' => 10,
        'rules_json' => ['activation_method' => 'automatic'],
    ]);
    $fixedDiscount = Discount::factory()->create([
        'store_id' => $store->id,
        'code' => null,
        'type' => 'fixed_amount',
        'value' => 500,
        'rules_json' => ['activation_method' => 'automatic'],
    ]);
    $product = shopProduct($store, price: 10000, stock: 5, requiresShipping: false);
    $cart = Cart::factory()->create(['store_id' => $store->id]);

    $cart = app(CartService::class)->add($cart, $product['variant'], 1);
    $line = $cart->lines->firstOrFail();
    expect($cart->discount_code)->toBeNull()
        ->and($line->line_discount_amount)->toBe(1500)
        ->and($line->line_total_amount)->toBe(8500);

    $checkouts = app(CheckoutService::class);
    $checkout = $checkouts->start($cart);
    $checkout = $checkouts->setContact($checkout, 'buyer@example.test');
    $checkout = $checkouts->setAddress($checkout, 'buyer@example.test', []);
    $checkout = $checkouts->selectShippingMethod($checkout);
    $checkout = $checkouts->selectPaymentMethod($checkout, PaymentMethod::CreditCard);
    $order = $checkouts->pay($checkout, ['card_number' => '4242424242424242']);

    expect($checkout->refresh()->discount_code)->toBeNull()
        ->and($checkout->totals_json['applied_discount_ids'])->toBe([$percentDiscount->id, $fixedDiscount->id])
        ->and($order->discount_amount)->toBe(1500)
        ->and($order->total_amount)->toBe(8500)
        ->and($order->lines->firstOrFail()->discount_allocations_json)->toBe([
            ['discount_id' => $percentDiscount->id, 'amount' => 1000],
            ['discount_id' => $fixedDiscount->id, 'amount' => 500],
        ])
        ->and($percentDiscount->refresh()->usage_count)->toBe(1)
        ->and($fixedDiscount->refresh()->usage_count)->toBe(1);
});

it('allows a guest or registered customer one automatic use and blocks subsequent use', function () {
    $store = shopStore();
    app()->instance('current_store', $store);
    $discount = Discount::factory()->create([
        'store_id' => $store->id,
        'code' => null,
        'type' => 'percentage',
        'value' => 10,
        'rules_json' => ['activation_method' => 'automatic', 'one_per_customer' => true],
    ]);
    $product = shopProduct($store, price: 10000, stock: 8, requiresShipping: false);
    $checkouts = app(CheckoutService::class);

    $placeOrder = function (?Customer $customer, string $email) use ($store, $product, $checkouts): Order {
        $cart = Cart::factory()->create(['store_id' => $store->id, 'customer_id' => $customer?->id]);
        $cart = app(CartService::class)->add($cart, $product['variant'], 1);
        $checkout = $checkouts->start($cart, $customer);
        $checkout = $checkouts->setContact($checkout, $email);
        $checkout = $checkouts->setAddress($checkout, $email, []);
        $checkout = $checkouts->selectShippingMethod($checkout);
        $checkout = $checkouts->selectPaymentMethod($checkout, PaymentMethod::CreditCard);

        return $checkouts->pay($checkout, ['card_number' => '4242424242424242']);
    };

    $guestOrder = $placeOrder(null, 'guest@example.test');
    expect($guestOrder->discount_amount)->toBe(1000);

    $repeatGuestCart = Cart::factory()->create(['store_id' => $store->id]);
    $repeatGuestCart = app(CartService::class)->add($repeatGuestCart, $product['variant'], 1);
    expect($repeatGuestCart->lines->firstOrFail()->line_discount_amount)->toBe(1000);
    $repeatGuestCheckout = $checkouts->start($repeatGuestCart);
    $repeatGuestCheckout = $checkouts->setContact($repeatGuestCheckout, 'GUEST@example.test');
    expect($repeatGuestCheckout->discount_amount)->toBe(0)
        ->and($repeatGuestCheckout->totals_json['applied_discount_ids'])->toBe([]);

    $customer = Customer::factory()->create(['store_id' => $store->id, 'email' => 'customer@example.test']);
    $customerOrder = $placeOrder($customer, $customer->email);
    expect($customerOrder->discount_amount)->toBe(1000);

    $repeatCustomerCart = Cart::factory()->create(['store_id' => $store->id, 'customer_id' => $customer->id]);
    $repeatCustomerCart = app(CartService::class)->add($repeatCustomerCart, $product['variant'], 1);
    expect($repeatCustomerCart->lines->firstOrFail()->line_discount_amount)->toBe(0)
        ->and($discount->refresh()->usage_count)->toBe(2);
});

it('rejects a repeat code redemption by a registered customer', function () {
    $store = shopStore();
    app()->instance('current_store', $store);
    $customer = Customer::factory()->create(['store_id' => $store->id, 'email' => 'once@example.test']);
    $discount = Discount::factory()->create([
        'store_id' => $store->id,
        'code' => 'ONCE20',
        'rules_json' => ['activation_method' => 'code', 'one_per_customer' => true],
    ]);
    $historyCart = Cart::factory()->create(['store_id' => $store->id, 'customer_id' => $customer->id]);
    $historyCheckout = Checkout::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'cart_id' => $historyCart->id,
        'customer_id' => $customer->id,
        'status' => 'completed',
        'email' => $customer->email,
        'discount_code' => $discount->code,
        'totals_json' => ['applied_discount_ids' => [$discount->id]],
    ]);
    Order::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'checkout_id' => $historyCheckout->id,
        'customer_id' => $customer->id,
        'order_number' => '#1001',
        'payment_method' => 'credit_card',
        'status' => 'paid',
        'financial_status' => 'paid',
        'fulfillment_status' => 'unfulfilled',
        'currency' => 'EUR',
        'email' => $customer->email,
        'placed_at' => now(),
    ]);
    $product = shopProduct($store);
    $cart = Cart::factory()->create(['store_id' => $store->id, 'customer_id' => $customer->id]);
    $cart = app(CartService::class)->add($cart, $product['variant'], 1);

    expect(fn () => app(CartService::class)->applyDiscount($cart, 'once20'))
        ->toThrow(ValidationException::class, 'This discount can only be used once per customer.');
});

it('restores the selected shipping price when a free shipping code is removed', function () {
    $store = shopStore();
    $product = shopProduct($store, price: 10000);
    $cart = Cart::factory()->create(['store_id' => $store->id]);
    $cart = app(CartService::class)->add($cart, $product['variant'], 1);
    $zone = ShippingZone::factory()->create(['store_id' => $store->id]);
    $rate = ShippingRate::factory()->create(['shipping_zone_id' => $zone->id, 'price_amount' => 500]);
    Discount::factory()->create([
        'store_id' => $store->id,
        'code' => 'FREESHIP',
        'type' => 'free_shipping',
        'value' => 0,
        'rules_json' => ['activation_method' => 'code'],
    ]);
    $checkoutService = app(CheckoutService::class);
    $checkout = $checkoutService->start($cart);
    $checkout = $checkoutService->setAddress($checkout, 'buyer@example.test', [
        'first_name' => 'Alex',
        'last_name' => 'Buyer',
        'address1' => '1 Test Street',
        'city' => 'Berlin',
        'country' => 'Germany',
        'country_code' => 'DE',
        'zip' => '10115',
    ]);
    $checkout = $checkoutService->applyDiscount($checkout, 'FREESHIP');
    $checkout = $checkoutService->selectShippingMethod($checkout, $rate->id);

    expect($checkout->shipping_amount)->toBe(0);

    $checkout = $checkoutService->removeDiscount($checkout);

    expect($checkout->shipping_amount)->toBe(500)
        ->and($checkout->total_amount)->toBe(10500);
});
