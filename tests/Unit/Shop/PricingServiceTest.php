<?php

use App\Models\Cart;
use App\Models\CartLine;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\Shop\Money;
use App\Services\Shop\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed();
    $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    app()->instance('current_store', $store);
});

test('money formatting uses storefront display convention', function (): void {
    expect(Money::format(2499, 'EUR'))->toBe('24.99 EUR')
        ->and(Money::format(149900, 'EUR'))->toBe('1,499.00 EUR')
        ->and(Money::format(-1250, 'EUR'))->toBe('-12.50 EUR');
});

test('pricing applies percentage fixed and usage limited discounts', function (): void {
    $store = app('current_store');
    $variant = ProductVariant::query()->whereHas('product', fn ($query) => $query->where('handle', 'classic-cotton-t-shirt'))->firstOrFail();
    $cart = Cart::query()->create(['store_id' => $store->id, 'currency' => 'EUR', 'discount_code' => 'WELCOME10']);
    CartLine::query()->create(['cart_id' => $cart->id, 'product_variant_id' => $variant->id, 'quantity' => 2, 'unit_price_amount' => 2499]);

    $totals = app(PricingService::class)->cartTotals($cart);

    expect($totals['subtotal'])->toBe(4998)
        ->and($totals['discount'])->toBe(500);

    $cart->update(['discount_code' => 'MAXED']);

    expect(fn () => app(PricingService::class)->cartTotals($cart))->toThrow(ValidationException::class);
});
