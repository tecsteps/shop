<?php

use App\Enums\DiscountValueType;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Discount;
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

it('applies a valid percent discount code at checkout', function () {
    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'SAVE10',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 5000]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 5000, 'line_subtotal_amount' => 5000, 'line_total_amount' => 5000]);

    $checkout = $this->checkoutService->createFromCart($cart);
    $checkout = $this->checkoutService->applyDiscount($checkout, 'SAVE10');

    expect($checkout->totals_json['discount'])->toBe(500);
});

it('applies a valid fixed discount code at checkout', function () {
    Discount::factory()->fixed(500)->create([
        'store_id' => $this->store->id,
        'code' => '5OFF',
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 5000]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 5000, 'line_subtotal_amount' => 5000, 'line_total_amount' => 5000]);

    $checkout = $this->checkoutService->createFromCart($cart);
    $checkout = $this->checkoutService->applyDiscount($checkout, '5OFF');

    expect($checkout->totals_json['discount'])->toBe(500);
});

it('removes discount when code is cleared', function () {
    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'TEMP',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 5000]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 5000, 'line_subtotal_amount' => 5000, 'line_total_amount' => 5000]);

    $checkout = $this->checkoutService->createFromCart($cart);
    $checkout = $this->checkoutService->applyDiscount($checkout, 'TEMP');
    expect($checkout->totals_json['discount'])->toBe(500);

    $checkout = $this->checkoutService->removeDiscount($checkout);
    expect($checkout->totals_json['discount'])->toBe(0);
});

it('rejects expired discount at checkout', function () {
    Discount::factory()->expired()->create([
        'store_id' => $this->store->id,
        'code' => 'OLDCODE',
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 5000]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 5000, 'line_subtotal_amount' => 5000, 'line_total_amount' => 5000]);

    $checkout = $this->checkoutService->createFromCart($cart);

    // The discount code is stored on checkout, but PricingEngine just looks it up and applies if valid.
    // The discount is expired, so it won't be found as valid by PricingEngine.
    $checkout = $this->checkoutService->applyDiscount($checkout, 'OLDCODE');

    // Expired discount won't match since ends_at is past - it simply won't apply
    expect($checkout->totals_json['discount'])->toBe(0);
});

it('does not increment usage count on checkout completion alone', function () {
    $discount = Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'COUNT',
        'usage_count' => 5,
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500, 'requires_shipping' => true]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 2500, 'line_subtotal_amount' => 2500, 'line_total_amount' => 2500]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 10]);

    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id, 'countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id]);

    $checkout = $this->checkoutService->createFromCart($cart);
    $checkout = $this->checkoutService->applyDiscount($checkout, 'COUNT');
    $checkout = $this->checkoutService->setAddress($checkout, [
        'first_name' => 'John', 'last_name' => 'Doe', 'address1' => 'Street 1',
        'city' => 'Berlin', 'country_code' => 'DE', 'zip' => '10115',
    ]);
    $checkout = $this->checkoutService->setShippingMethod($checkout, $rate->id);
    $checkout = $this->checkoutService->selectPaymentMethod($checkout, 'credit_card');
    $checkout = $this->checkoutService->completeCheckout($checkout);

    // Usage count is only incremented by OrderService::createFromCheckout
    expect($discount->fresh()->usage_count)->toBe(5);
});

it('handles free shipping discount at checkout', function () {
    Discount::factory()->freeShipping()->create([
        'store_id' => $this->store->id,
        'code' => 'FREESHIP',
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 5000, 'requires_shipping' => true]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 5000, 'line_subtotal_amount' => 5000, 'line_total_amount' => 5000]);

    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id, 'countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id, 'config_json' => ['amount' => 499]]);

    $checkout = $this->checkoutService->createFromCart($cart);
    $checkout = $this->checkoutService->applyDiscount($checkout, 'FREESHIP');
    $checkout = $this->checkoutService->setAddress($checkout, [
        'first_name' => 'John', 'last_name' => 'Doe', 'address1' => 'Street 1',
        'city' => 'Berlin', 'country_code' => 'DE', 'zip' => '10115',
    ]);
    $checkout = $this->checkoutService->setShippingMethod($checkout, $rate->id);

    expect($checkout->totals_json['shipping'])->toBe(0);
});
