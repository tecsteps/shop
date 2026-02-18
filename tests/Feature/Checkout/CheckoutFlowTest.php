<?php

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Jobs\ExpireAbandonedCheckouts;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\CartService;
use App\Services\CheckoutService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->cartService = new CartService;
    $this->checkoutService = app(CheckoutService::class);
});

it('creates a checkout from a cart', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 2500, 'line_subtotal_amount' => 2500, 'line_total_amount' => 2500]);

    $checkout = $this->checkoutService->createFromCart($cart);

    expect($checkout->status)->toBe(CheckoutStatus::Started)
        ->and($checkout->cart_id)->toBe($cart->id)
        ->and($checkout->store_id)->toBe($this->store->id);
});

it('completes full checkout happy path', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500, 'requires_shipping' => true]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 2, 'unit_price_amount' => 2500, 'line_subtotal_amount' => 5000, 'line_total_amount' => 5000]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 10]);

    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id, 'countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id, 'config_json' => ['amount' => 499]]);

    $checkout = $this->checkoutService->createFromCart($cart);

    $checkout = $this->checkoutService->setAddress($checkout, [
        'email' => 'test@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'address1' => 'Test Street 1',
        'city' => 'Berlin',
        'province_code' => 'DE-BE',
        'country_code' => 'DE',
        'zip' => '10115',
    ]);

    $checkout = $this->checkoutService->setShippingMethod($checkout, $rate->id);
    $checkout = $this->checkoutService->selectPaymentMethod($checkout, 'credit_card');
    $checkout = $this->checkoutService->completeCheckout($checkout);

    expect($checkout->status)->toBe(CheckoutStatus::Completed)
        ->and($checkout->cart->status)->toBe(CartStatus::Converted);

    // Inventory commit handled by OrderService::createFromCheckout, not completeCheckout
    $inventory = InventoryItem::where('variant_id', $variant->id)->first();
    expect($inventory->quantity_on_hand)->toBe(10);
});

it('rejects checkout for empty cart', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);

    $this->checkoutService->createFromCart($cart);
})->throws(InvalidArgumentException::class, 'Cannot create checkout for empty cart');

it('expires checkout after timeout', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500, 'requires_shipping' => true]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 2500, 'line_subtotal_amount' => 2500, 'line_total_amount' => 2500]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 10]);

    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id, 'countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id, 'config_json' => ['amount' => 499]]);

    $checkout = $this->checkoutService->createFromCart($cart);
    $checkout = $this->checkoutService->setAddress($checkout, [
        'first_name' => 'John', 'last_name' => 'Doe', 'address1' => 'Street 1',
        'city' => 'Berlin', 'country_code' => 'DE', 'zip' => '10115',
    ]);
    $checkout = $this->checkoutService->setShippingMethod($checkout, $rate->id);
    $checkout = $this->checkoutService->selectPaymentMethod($checkout, 'credit_card');

    // Set expires_at to the past
    $checkout->update(['expires_at' => now()->subMinute()]);

    (new ExpireAbandonedCheckouts)->handle(app(CheckoutService::class));

    expect($checkout->fresh()->status)->toBe(CheckoutStatus::Expired);

    $inventory = InventoryItem::where('variant_id', $variant->id)->first();
    expect($inventory->quantity_reserved)->toBe(0);
});

it('prevents duplicate orders from same checkout', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500, 'requires_shipping' => true]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 2500, 'line_subtotal_amount' => 2500, 'line_total_amount' => 2500]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 10]);

    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id, 'countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id, 'config_json' => ['amount' => 499]]);

    $checkout = $this->checkoutService->createFromCart($cart);
    $checkout = $this->checkoutService->setAddress($checkout, [
        'first_name' => 'John', 'last_name' => 'Doe', 'address1' => 'Street 1',
        'city' => 'Berlin', 'country_code' => 'DE', 'zip' => '10115',
    ]);
    $checkout = $this->checkoutService->setShippingMethod($checkout, $rate->id);
    $checkout = $this->checkoutService->selectPaymentMethod($checkout, 'credit_card');

    $first = $this->checkoutService->completeCheckout($checkout);
    $second = $this->checkoutService->completeCheckout($first);

    expect($first->id)->toBe($second->id)
        ->and($first->status)->toBe(CheckoutStatus::Completed);
});
