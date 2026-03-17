<?php

use App\Enums\TaxMode;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\TaxSettings;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\PricingEngine;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->store = $this->ctx['store'];
    $this->checkoutService = app(CheckoutService::class);
    $this->cartService = app(CartService::class);
    $this->pricingEngine = app(PricingEngine::class);
});

function createTaxCheckout($store, int $price = 2500, int $qty = 2): \App\Models\Checkout
{
    $product = Product::factory()->active()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => $price]);
    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $variant->id, $qty);

    $checkout = app(CheckoutService::class)->createFromCart($cart->fresh('lines'));
    $checkout = app(CheckoutService::class)->setAddress($checkout, [
        'email' => 'test@example.com',
        'shipping_address' => [
            'first_name' => 'John', 'last_name' => 'Doe',
            'address1' => '123 Main St', 'city' => 'Berlin',
            'country' => 'DE', 'postal_code' => '10115',
        ],
    ]);

    return $checkout;
}

it('calculates exclusive tax correctly at checkout', function () {
    TaxSettings::create([
        'store_id' => $this->store->id,
        'mode' => TaxMode::Manual,
        'provider' => 'none',
        'prices_include_tax' => false,
        'config_json' => ['default_rate' => 1900, 'tax_name' => 'VAT'],
    ]);

    $checkout = createTaxCheckout($this->store, 2500, 2);

    $result = $this->pricingEngine->calculate($checkout->fresh());

    // Subtotal = 5000, exclusive tax at 19% = round(5000 * 1900 / 10000) = 950
    expect($result->subtotal)->toBe(5000)
        ->and($result->taxTotal)->toBe(950)
        ->and($result->total)->toBe(5950);
});

it('extracts inclusive tax correctly at checkout', function () {
    TaxSettings::create([
        'store_id' => $this->store->id,
        'mode' => TaxMode::Manual,
        'provider' => 'none',
        'prices_include_tax' => true,
        'config_json' => ['default_rate' => 1900, 'tax_name' => 'VAT'],
    ]);

    $checkout = createTaxCheckout($this->store, 11900, 1);

    $result = $this->pricingEngine->calculate($checkout->fresh());

    expect($result->subtotal)->toBe(11900)
        ->and($result->taxTotal)->toBe(1900);
});

it('applies zero tax when no tax settings exist', function () {
    // No TaxSettings row for this store
    $checkout = createTaxCheckout($this->store, 2500, 2);

    $result = $this->pricingEngine->calculate($checkout->fresh());

    expect($result->taxTotal)->toBe(0)
        ->and($result->taxLines)->toBeEmpty();
});

it('stores tax lines in totals_json', function () {
    TaxSettings::create([
        'store_id' => $this->store->id,
        'mode' => TaxMode::Manual,
        'provider' => 'none',
        'prices_include_tax' => false,
        'config_json' => ['default_rate' => 1900, 'tax_name' => 'VAT'],
    ]);

    $checkout = createTaxCheckout($this->store, 2500, 2);
    $this->pricingEngine->calculate($checkout->fresh());

    $checkout->refresh();
    $totals = $checkout->totals_json;

    expect($totals)->toHaveKey('tax_lines')
        ->and($totals['tax_lines'])->toHaveCount(1)
        ->and($totals['tax_lines'][0])->toHaveKeys(['name', 'rate', 'amount'])
        ->and($totals['tax_lines'][0]['name'])->toBe('VAT')
        ->and($totals['tax_lines'][0]['rate'])->toBe(1900);
});
