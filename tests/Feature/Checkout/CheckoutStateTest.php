<?php

use App\Enums\CheckoutStatus;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\CheckoutService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->checkoutService = app(CheckoutService::class);
});

it('transitions from started to addressed with valid address', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 2500, 'line_subtotal_amount' => 2500, 'line_total_amount' => 2500]);

    $checkout = $this->checkoutService->createFromCart($cart);

    $checkout = $this->checkoutService->setAddress($checkout, [
        'email' => 'test@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'address1' => 'Street 1',
        'city' => 'Berlin',
        'country_code' => 'DE',
        'zip' => '10115',
    ]);

    expect($checkout->status)->toBe(CheckoutStatus::Addressed);
});

it('rejects address transition with missing required fields', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 2500, 'line_subtotal_amount' => 2500, 'line_total_amount' => 2500]);

    $checkout = $this->checkoutService->createFromCart($cart);

    $this->checkoutService->setAddress($checkout, [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'address1' => 'Street 1',
        'country_code' => 'DE',
        'zip' => '10115',
        // Missing city
    ]);
})->throws(InvalidArgumentException::class, 'Missing required address field: city');

it('transitions from addressed to shipping_selected', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500, 'requires_shipping' => true]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 2500, 'line_subtotal_amount' => 2500, 'line_total_amount' => 2500]);

    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id, 'countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id]);

    $checkout = $this->checkoutService->createFromCart($cart);
    $checkout = $this->checkoutService->setAddress($checkout, [
        'first_name' => 'John', 'last_name' => 'Doe', 'address1' => 'Street 1',
        'city' => 'Berlin', 'country_code' => 'DE', 'zip' => '10115',
    ]);

    $checkout = $this->checkoutService->setShippingMethod($checkout, $rate->id);

    expect($checkout->status)->toBe(CheckoutStatus::ShippingSelected);
});

it('rejects shipping selection with rate from wrong zone', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 2500, 'line_subtotal_amount' => 2500, 'line_total_amount' => 2500]);

    $usZone = ShippingZone::factory()->create(['store_id' => $this->store->id, 'countries_json' => ['US']]);
    $usRate = ShippingRate::factory()->create(['zone_id' => $usZone->id]);

    $checkout = $this->checkoutService->createFromCart($cart);
    $checkout = $this->checkoutService->setAddress($checkout, [
        'first_name' => 'John', 'last_name' => 'Doe', 'address1' => 'Street 1',
        'city' => 'Berlin', 'country_code' => 'DE', 'zip' => '10115',
    ]);

    $this->checkoutService->setShippingMethod($checkout, $usRate->id);
})->throws(InvalidArgumentException::class, 'Shipping rate does not apply');

it('skips shipping selection when no items require shipping', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500, 'requires_shipping' => false]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 2500, 'line_subtotal_amount' => 2500, 'line_total_amount' => 2500]);

    // Even though we use a zone matching DE, shipping should be 0 for digital items
    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id, 'countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id, 'config_json' => ['amount' => 499]]);

    $checkout = $this->checkoutService->createFromCart($cart);
    $checkout = $this->checkoutService->setAddress($checkout, [
        'first_name' => 'John', 'last_name' => 'Doe', 'address1' => 'Street 1',
        'city' => 'Berlin', 'country_code' => 'DE', 'zip' => '10115',
    ]);
    $checkout = $this->checkoutService->setShippingMethod($checkout, $rate->id);

    expect($checkout->status)->toBe(CheckoutStatus::ShippingSelected)
        ->and($checkout->totals_json['shipping'])->toBe(0);
});

it('transitions from shipping_selected to payment_selected', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500, 'requires_shipping' => true]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 2500, 'line_subtotal_amount' => 2500, 'line_total_amount' => 2500]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 10]);

    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id, 'countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id]);

    $checkout = $this->checkoutService->createFromCart($cart);
    $checkout = $this->checkoutService->setAddress($checkout, [
        'first_name' => 'John', 'last_name' => 'Doe', 'address1' => 'Street 1',
        'city' => 'Berlin', 'country_code' => 'DE', 'zip' => '10115',
    ]);
    $checkout = $this->checkoutService->setShippingMethod($checkout, $rate->id);
    $checkout = $this->checkoutService->selectPaymentMethod($checkout, 'credit_card');

    expect($checkout->status)->toBe(CheckoutStatus::PaymentSelected)
        ->and($checkout->expires_at)->not->toBeNull();

    $inventory = InventoryItem::where('variant_id', $variant->id)->first();
    expect($inventory->quantity_reserved)->toBe(1);
});

it('transitions from payment_selected to completed', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500, 'requires_shipping' => true]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 2500, 'line_subtotal_amount' => 2500, 'line_total_amount' => 2500]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 10]);

    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id, 'countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id]);

    $checkout = $this->checkoutService->createFromCart($cart);
    $checkout = $this->checkoutService->setAddress($checkout, [
        'first_name' => 'John', 'last_name' => 'Doe', 'address1' => 'Street 1',
        'city' => 'Berlin', 'country_code' => 'DE', 'zip' => '10115',
    ]);
    $checkout = $this->checkoutService->setShippingMethod($checkout, $rate->id);
    $checkout = $this->checkoutService->selectPaymentMethod($checkout, 'credit_card');
    $checkout = $this->checkoutService->completeCheckout($checkout);

    expect($checkout->status)->toBe(CheckoutStatus::Completed)
        ->and($checkout->completed_at)->not->toBeNull();
});

it('rejects invalid state transitions', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 2500, 'line_subtotal_amount' => 2500, 'line_total_amount' => 2500]);

    $checkout = $this->checkoutService->createFromCart($cart);

    // Try to jump from started directly to completed
    $this->checkoutService->completeCheckout($checkout);
})->throws(InvalidCheckoutTransitionException::class);

it('recalculates pricing on address change', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 2500, 'line_subtotal_amount' => 2500, 'line_total_amount' => 2500]);

    $checkout = $this->checkoutService->createFromCart($cart);
    $checkout = $this->checkoutService->setAddress($checkout, [
        'first_name' => 'John', 'last_name' => 'Doe', 'address1' => 'Street 1',
        'city' => 'Berlin', 'country_code' => 'DE', 'zip' => '10115',
    ]);

    $firstTotals = $checkout->totals_json;
    expect($firstTotals)->not->toBeNull();

    // Change address (re-address from addressed state)
    $checkout = $this->checkoutService->setAddress($checkout, [
        'first_name' => 'Jane', 'last_name' => 'Doe', 'address1' => 'New Street',
        'city' => 'Munich', 'country_code' => 'DE', 'zip' => '80331',
    ]);

    expect($checkout->totals_json)->not->toBeNull();
});
