<?php

use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\TaxSettings;
use App\Services\CheckoutService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->checkoutService = app(CheckoutService::class);
});

it('calculates exclusive tax correctly at checkout', function () {
    TaxSettings::factory()->create([
        'store_id' => $this->store->id,
        'rate_percent' => 1900,
        'prices_include_tax' => false,
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 5000]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 5000, 'line_subtotal_amount' => 5000, 'line_total_amount' => 5000]);

    $checkout = $this->checkoutService->createFromCart($cart);

    $totals = $checkout->fresh()->totals_json;

    // 19% of 5000 = 950
    expect($totals['tax_total'])->toBe(950);
});

it('extracts inclusive tax correctly at checkout', function () {
    TaxSettings::factory()->inclusive()->create([
        'store_id' => $this->store->id,
        'rate_percent' => 1900,
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    // Gross price of 11900 contains 1900 tax at 19%
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 11900]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 11900, 'line_subtotal_amount' => 11900, 'line_total_amount' => 11900]);

    $checkout = $this->checkoutService->createFromCart($cart);

    $totals = $checkout->fresh()->totals_json;

    expect($totals['tax_total'])->toBe(1900);
});

it('applies zero tax when no tax settings exist', function () {
    // No TaxSettings created for this store
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 5000]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 5000, 'line_subtotal_amount' => 5000, 'line_total_amount' => 5000]);

    $checkout = $this->checkoutService->createFromCart($cart);

    $totals = $checkout->fresh()->totals_json;

    expect($totals['tax_total'])->toBe(0);
});

it('stores tax lines in totals_json', function () {
    TaxSettings::factory()->create([
        'store_id' => $this->store->id,
        'rate_percent' => 1900,
        'prices_include_tax' => false,
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 5000]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 5000, 'line_subtotal_amount' => 5000, 'line_total_amount' => 5000]);

    $checkout = $this->checkoutService->createFromCart($cart);

    $totals = $checkout->fresh()->totals_json;

    expect($totals['tax_lines'])->toBeArray()
        ->and($totals['tax_lines'])->toHaveCount(1)
        ->and($totals['tax_lines'][0])->toHaveKeys(['name', 'rate', 'amount'])
        ->and($totals['tax_lines'][0]['rate'])->toBe(1900)
        ->and($totals['tax_lines'][0]['amount'])->toBe(950);
});
