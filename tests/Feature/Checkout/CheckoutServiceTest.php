<?php

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\VariantStatus;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\TaxSettings;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    $this->service = app(CheckoutService::class);
});

function createCartWithOneLine(Store $store, int $price = 2500): Cart
{
    $cart = Cart::factory()->create(['store_id' => $store->id]);
    $product = Product::factory()->active()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => $price,
        'status' => VariantStatus::Active,
    ]);

    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price_amount' => $price,
        'line_subtotal_amount' => $price * 2,
        'line_total_amount' => $price * 2,
    ]);

    return $cart;
}

function validAddressData(): array
{
    return [
        'email' => 'test@example.com',
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
            'postal_code' => '10001',
        ],
    ];
}

test('creates a checkout from a cart', function () {
    $cart = createCartWithOneLine($this->store);

    $checkout = $this->service->createCheckout($cart);

    expect($checkout)->toBeInstanceOf(Checkout::class);
    expect($checkout->status)->toBe(CheckoutStatus::Started);
    expect($checkout->cart_id)->toBe($cart->id);
    expect($checkout->store_id)->toBe($this->store->id);
});

test('rejects checkout for empty cart', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);

    expect(fn () => $this->service->createCheckout($cart))
        ->toThrow(InvalidArgumentException::class, 'Cannot create checkout for empty cart');
});

test('transitions from started to addressed with valid address', function () {
    $cart = createCartWithOneLine($this->store);
    $checkout = $this->service->createCheckout($cart);

    $updated = $this->service->setAddress($checkout, validAddressData());

    expect($updated->status)->toBe(CheckoutStatus::Addressed);
    expect($updated->email)->toBe('test@example.com');
    expect($updated->shipping_address_json['first_name'])->toBe('John');
    expect($updated->billing_address_json)->not->toBeNull();
});

test('transitions from addressed to shipping_selected', function () {
    $cart = createCartWithOneLine($this->store);
    $checkout = $this->service->createCheckout($cart);
    $this->service->setAddress($checkout, validAddressData());

    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['US'],
    ]);
    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'config_json' => ['amount' => 499],
    ]);

    $updated = $this->service->setShippingMethod($checkout, $rate->id);

    expect($updated->status)->toBe(CheckoutStatus::ShippingSelected);
    expect($updated->shipping_method_id)->toBe($rate->id);
});

test('skips shipping selection when no items require shipping', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->digital()->create([
        'product_id' => $product->id,
    ]);

    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 1000,
        'line_subtotal_amount' => 1000,
        'line_total_amount' => 1000,
    ]);

    $checkout = $this->service->createCheckout($cart);
    $this->service->setAddress($checkout, validAddressData());

    $updated = $this->service->skipShipping($checkout);

    expect($updated->status)->toBe(CheckoutStatus::ShippingSelected);
    expect($updated->shipping_method_id)->toBeNull();
});

test('transitions from shipping_selected to payment_selected', function () {
    $cart = createCartWithOneLine($this->store);
    $checkout = $this->service->createCheckout($cart);
    $this->service->setAddress($checkout, validAddressData());

    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['US'],
    ]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id]);
    $this->service->setShippingMethod($checkout, $rate->id);

    $updated = $this->service->selectPaymentMethod($checkout, 'credit_card');

    expect($updated->status)->toBe(CheckoutStatus::PaymentSelected);
    expect($updated->payment_method)->toBe('credit_card');
    expect($updated->expires_at)->not->toBeNull();
});

test('transitions from payment_selected to completed', function () {
    $cart = createCartWithOneLine($this->store);
    $checkout = $this->service->createCheckout($cart);
    $this->service->setAddress($checkout, validAddressData());

    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['US'],
    ]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id]);
    $this->service->setShippingMethod($checkout, $rate->id);
    $this->service->selectPaymentMethod($checkout, 'credit_card');

    $completed = $this->service->completeCheckout($checkout);

    expect($completed->status)->toBe(CheckoutStatus::Completed);

    $cart->refresh();
    expect($cart->status)->toBe(CartStatus::Converted);
});

test('rejects invalid state transitions', function () {
    $cart = createCartWithOneLine($this->store);
    $checkout = $this->service->createCheckout($cart);

    // Cannot go directly from started to completed
    expect(fn () => $this->service->completeCheckout($checkout))
        ->toThrow(InvalidCheckoutTransitionException::class);
});

test('rejects invalid payment method', function () {
    $cart = createCartWithOneLine($this->store);
    $checkout = $this->service->createCheckout($cart);
    $this->service->setAddress($checkout, validAddressData());

    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['US'],
    ]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id]);
    $this->service->setShippingMethod($checkout, $rate->id);

    expect(fn () => $this->service->selectPaymentMethod($checkout, 'bitcoin'))
        ->toThrow(InvalidArgumentException::class);
});

test('expires a checkout', function () {
    $cart = createCartWithOneLine($this->store);
    $checkout = $this->service->createCheckout($cart);

    $expired = $this->service->expireCheckout($checkout);

    expect($expired->status)->toBe(CheckoutStatus::Expired);
});

test('cannot expire a completed checkout', function () {
    $cart = createCartWithOneLine($this->store);
    $checkout = $this->service->createCheckout($cart);
    $this->service->setAddress($checkout, validAddressData());

    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['US'],
    ]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id]);
    $this->service->setShippingMethod($checkout, $rate->id);
    $this->service->selectPaymentMethod($checkout, 'credit_card');
    $this->service->completeCheckout($checkout);

    expect(fn () => $this->service->expireCheckout($checkout))
        ->toThrow(InvalidCheckoutTransitionException::class);
});

test('applies discount code to checkout', function () {
    $cart = createCartWithOneLine($this->store, 5000);
    $checkout = $this->service->createCheckout($cart);

    $updated = $this->service->applyDiscountCode($checkout, 'SAVE10');

    expect($updated->discount_code)->toBe('SAVE10');
});

test('removes discount from checkout', function () {
    $cart = createCartWithOneLine($this->store, 5000);
    $checkout = $this->service->createCheckout($cart);
    $this->service->applyDiscountCode($checkout, 'SAVE10');

    $updated = $this->service->removeDiscount($checkout);

    expect($updated->discount_code)->toBeNull();
});

test('stores pricing snapshot in totals_json on address set', function () {
    $cart = createCartWithOneLine($this->store, 5000);
    $checkout = $this->service->createCheckout($cart);

    $updated = $this->service->setAddress($checkout, validAddressData());

    expect($updated->totals_json)->not->toBeNull();
    expect($updated->totals_json)->toHaveKeys(['subtotal', 'discount', 'shipping', 'tax_total', 'total']);
});

test('recalculates pricing on shipping method change', function () {
    $cart = createCartWithOneLine($this->store, 5000);
    $checkout = $this->service->createCheckout($cart);
    $this->service->setAddress($checkout, validAddressData());

    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['US'],
    ]);
    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'config_json' => ['amount' => 499],
    ]);

    $updated = $this->service->setShippingMethod($checkout, $rate->id);

    expect($updated->totals_json['shipping'])->toBe(499);
});

test('handles prices-include-tax correctly', function () {
    TaxSettings::factory()->inclusive()->create([
        'store_id' => $this->store->id,
        'config_json' => ['default_rate' => 1900],
    ]);

    $cart = createCartWithOneLine($this->store, 11900);
    $checkout = $this->service->createCheckout($cart);

    $updated = $this->service->setAddress($checkout, validAddressData());

    expect($updated->totals_json['tax_total'])->toBeGreaterThan(0);
    // With inclusive pricing, total should equal subtotal (tax already included)
    expect($updated->totals_json['total'])->toBe($updated->totals_json['subtotal'] - $updated->totals_json['discount']);
});

test('full checkout happy path', function () {
    $cart = createCartWithOneLine($this->store, 2500);
    $checkout = $this->service->createCheckout($cart);

    // Step 1: Address
    $this->service->setAddress($checkout, validAddressData());
    expect($checkout->refresh()->status)->toBe(CheckoutStatus::Addressed);

    // Step 2: Shipping
    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['US'],
    ]);
    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'config_json' => ['amount' => 499],
    ]);
    $this->service->setShippingMethod($checkout, $rate->id);
    expect($checkout->refresh()->status)->toBe(CheckoutStatus::ShippingSelected);

    // Step 3: Payment
    $this->service->selectPaymentMethod($checkout, 'credit_card');
    expect($checkout->refresh()->status)->toBe(CheckoutStatus::PaymentSelected);

    // Step 4: Complete
    $this->service->completeCheckout($checkout);
    expect($checkout->refresh()->status)->toBe(CheckoutStatus::Completed);

    $cart->refresh();
    expect($cart->status)->toBe(CartStatus::Converted);
});
