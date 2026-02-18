<?php

use App\Enums\DiscountValueType;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Discount;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\TaxSettings;
use App\Services\CheckoutService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->checkoutService = app(CheckoutService::class);
});

it('calculates correct totals for a simple checkout', function () {
    TaxSettings::factory()->create(['store_id' => $this->store->id, 'rate_percent' => 1900, 'prices_include_tax' => false]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500, 'requires_shipping' => true]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 2, 'unit_price_amount' => 2500, 'line_subtotal_amount' => 5000, 'line_total_amount' => 5000]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 10]);

    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id, 'countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id, 'config_json' => ['amount' => 499]]);

    $checkout = $this->checkoutService->createFromCart($cart);
    $checkout = $this->checkoutService->setAddress($checkout, [
        'first_name' => 'John', 'last_name' => 'Doe', 'address1' => 'Street 1',
        'city' => 'Berlin', 'country_code' => 'DE', 'zip' => '10115',
    ]);
    $checkout = $this->checkoutService->setShippingMethod($checkout, $rate->id);

    $totals = $checkout->totals_json;

    expect($totals['subtotal'])->toBe(5000)
        ->and($totals['shipping'])->toBe(499)
        ->and($totals['tax_total'])->toBe(950); // 19% of 5000 = 950
});

it('applies discount code and recalculates', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 10000]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 10000, 'line_subtotal_amount' => 10000, 'line_total_amount' => 10000]);

    Discount::factory()->create(['store_id' => $this->store->id, 'code' => 'SAVE10', 'value_type' => DiscountValueType::Percent, 'value_amount' => 10]);

    $checkout = $this->checkoutService->createFromCart($cart);
    $checkout = $this->checkoutService->applyDiscount($checkout, 'SAVE10');

    expect($checkout->totals_json['discount'])->toBe(1000)
        ->and($checkout->totals_json['subtotal'])->toBe(10000);
});

it('stores pricing snapshot in totals_json', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 2500, 'line_subtotal_amount' => 2500, 'line_total_amount' => 2500]);

    $checkout = $this->checkoutService->createFromCart($cart);

    $totals = $checkout->fresh()->totals_json;

    expect($totals)->toHaveKeys(['subtotal', 'discount', 'shipping', 'tax_lines', 'tax_total', 'total', 'currency']);
});

it('recalculates on shipping method change', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500, 'requires_shipping' => true, 'weight_g' => 750]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 2500, 'line_subtotal_amount' => 2500, 'line_total_amount' => 2500]);

    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id, 'countries_json' => ['DE']]);
    $flatRate = ShippingRate::factory()->create(['zone_id' => $zone->id, 'config_json' => ['amount' => 499]]);
    $weightRate = ShippingRate::factory()->weightBased()->create(['zone_id' => $zone->id]);

    $checkout = $this->checkoutService->createFromCart($cart);
    $checkout = $this->checkoutService->setAddress($checkout, [
        'first_name' => 'John', 'last_name' => 'Doe', 'address1' => 'Street 1',
        'city' => 'Berlin', 'country_code' => 'DE', 'zip' => '10115',
    ]);

    $checkout = $this->checkoutService->setShippingMethod($checkout, $flatRate->id);
    expect($checkout->totals_json['shipping'])->toBe(499);

    // Switch to weight-based rate (750g maps to 501-2000g range = 899)
    $checkout = $this->checkoutService->setShippingMethod($checkout, $weightRate->id);
    expect($checkout->totals_json['shipping'])->toBe(899);
});

it('handles prices-include-tax correctly', function () {
    TaxSettings::factory()->inclusive()->create(['store_id' => $this->store->id, 'rate_percent' => 1900]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    // Item price 11900 (gross, tax inclusive)
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 11900]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 11900, 'line_subtotal_amount' => 11900, 'line_total_amount' => 11900]);

    $checkout = $this->checkoutService->createFromCart($cart);

    $totals = $checkout->fresh()->totals_json;

    // Tax extracted from inclusive: 11900 - round(11900 * 10000 / 11900) = 11900 - 10000 = 1900
    expect($totals['tax_total'])->toBe(1900)
        ->and($totals['subtotal'])->toBe(11900);
});
