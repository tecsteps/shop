<?php

use App\Enums\CheckoutStatus;
use App\Enums\DiscountStatus;
use App\Enums\DiscountValueType;
use App\Enums\ProductStatus;
use App\Enums\TaxMode;
use App\Enums\VariantStatus;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\TaxSettings;
use App\Services\CartService;
use App\Services\PricingEngine;
use App\ValueObjects\PricingResult;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->cartService = app(CartService::class);
    $this->pricingEngine = app(PricingEngine::class);
});

it('calculates basic pricing without discount, shipping or tax', function () {
    $cart = $this->cartService->create($this->store);
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'status' => ProductStatus::Active,
    ]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 2500,
        'status' => VariantStatus::Active,
    ]);

    $this->cartService->addLine($cart, $variant->id, 2);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::Started,
    ]);

    $result = $this->pricingEngine->calculate($checkout);

    expect($result)->toBeInstanceOf(PricingResult::class)
        ->and($result->subtotal)->toBe(5000)
        ->and($result->discount)->toBe(0)
        ->and($result->shipping)->toBe(0)
        ->and($result->taxTotal)->toBe(0)
        ->and($result->total)->toBe(5000);
});

it('applies a percent discount code', function () {
    $cart = $this->cartService->create($this->store);
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'status' => ProductStatus::Active,
    ]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 5000,
        'status' => VariantStatus::Active,
    ]);

    $this->cartService->addLine($cart, $variant->id, 1);

    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'SAVE20',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 20,
        'status' => DiscountStatus::Active,
        'starts_at' => now()->subDay(),
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $cart->id,
        'discount_code' => 'SAVE20',
    ]);

    $result = $this->pricingEngine->calculate($checkout);

    expect($result->subtotal)->toBe(5000)
        ->and($result->discount)->toBe(1000)
        ->and($result->total)->toBe(4000);
});

it('calculates tax on discounted amount', function () {
    $cart = $this->cartService->create($this->store);
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'status' => ProductStatus::Active,
    ]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 10000,
        'status' => VariantStatus::Active,
    ]);
    $this->cartService->addLine($cart, $variant->id, 1);

    TaxSettings::factory()->create([
        'store_id' => $this->store->id,
        'mode' => TaxMode::Manual,
        'prices_include_tax' => false,
        'config_json' => ['default_rate_basis_points' => 1000], // 10%
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $cart->id,
        'shipping_address_json' => ['country' => 'US'],
    ]);

    $result = $this->pricingEngine->calculate($checkout);

    // Tax on 10000: 10000 * 1000 / 10000 = 1000
    expect($result->subtotal)->toBe(10000)
        ->and($result->taxTotal)->toBe(1000)
        ->and($result->total)->toBe(11000);
});

it('snapshots totals on checkout record', function () {
    $cart = $this->cartService->create($this->store);
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'status' => ProductStatus::Active,
    ]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 3000,
        'status' => VariantStatus::Active,
    ]);
    $this->cartService->addLine($cart, $variant->id, 1);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $cart->id,
    ]);

    $this->pricingEngine->calculate($checkout);

    $checkout->refresh();
    expect($checkout->totals_json)->not->toBeNull()
        ->and($checkout->totals_json['subtotal'])->toBe(3000);
});
