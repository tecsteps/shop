<?php

use App\Enums\CheckoutStatus;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\DiscountService;
use App\Services\InventoryService;
use App\Services\OrderService;
use App\Services\Payments\MockPaymentProvider;
use App\Services\PricingEngine;
use App\Services\ShippingCalculator;
use App\Services\TaxCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);

    $this->inventoryService = new InventoryService;
    $this->cartService = new CartService($this->inventoryService);
    $this->checkoutService = new CheckoutService(
        new PricingEngine(
            new DiscountService,
            new ShippingCalculator,
            new TaxCalculator,
        ),
        $this->inventoryService,
        new OrderService,
        new MockPaymentProvider,
    );

    $product = Product::factory()->for($this->store)->create();
    $this->variant = ProductVariant::factory()->for($product)->create([
        'price_amount' => 2500,
        'weight_g' => 300,
        'requires_shipping' => true,
    ]);
    InventoryItem::factory()
        ->for($this->store)
        ->for($this->variant, 'variant')
        ->create(['quantity_on_hand' => 10]);

    $this->cart = $this->cartService->create($this->store);
    $this->cartService->addLine($this->cart, $this->variant->id, 2);
    $this->cart->refresh();

    $this->zone = ShippingZone::factory()->for($this->store)->create([
        'countries_json' => ['DE'],
        'regions_json' => [],
    ]);
    $this->rate = ShippingRate::factory()->for($this->zone, 'zone')->flat(599)->create();
});

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

it('starts checkout from cart', function (): void {
    $checkout = $this->checkoutService->start($this->cart);

    expect($checkout->status)->toBe(CheckoutStatus::Started)
        ->and((int) $checkout->cart_id)->toBe($this->cart->id);
});

it('transitions started -> addressed via setAddress', function (): void {
    $checkout = $this->checkoutService->start($this->cart);

    $result = $this->checkoutService->setAddress($checkout, [
        'email' => 'buyer@example.com',
        'shipping_address' => [
            'first_name' => 'A',
            'last_name' => 'B',
            'address1' => 'Street 1',
            'city' => 'Berlin',
            'country' => 'DE',
            'postal_code' => '10115',
        ],
    ]);

    expect($result->status)->toBe(CheckoutStatus::Addressed)
        ->and($result->email)->toBe('buyer@example.com');
});

it('transitions addressed -> shipping_selected', function (): void {
    $checkout = $this->checkoutService->start($this->cart);
    $this->checkoutService->setAddress($checkout, [
        'email' => 'a@b.c',
        'shipping_address' => ['country' => 'DE'],
    ]);

    $result = $this->checkoutService->setShippingMethod($checkout->fresh(), $this->rate->id);

    expect($result->status)->toBe(CheckoutStatus::ShippingSelected)
        ->and((int) $result->shipping_method_id)->toBe($this->rate->id);
});

it('transitions shipping_selected -> payment_selected', function (): void {
    $checkout = $this->checkoutService->start($this->cart);
    $this->checkoutService->setAddress($checkout, [
        'email' => 'a@b.c',
        'shipping_address' => ['country' => 'DE'],
    ]);
    $this->checkoutService->setShippingMethod($checkout->fresh(), $this->rate->id);

    $result = $this->checkoutService->selectPaymentMethod($checkout->fresh(), 'credit_card');

    expect($result->status)->toBe(CheckoutStatus::PaymentSelected)
        ->and($result->payment_method)->toBe('credit_card')
        ->and($result->expires_at)->not->toBeNull();
});

it('rejects invalid transitions', function (): void {
    $checkout = $this->checkoutService->start($this->cart);

    $this->checkoutService->selectPaymentMethod($checkout, 'credit_card');
})->throws(DomainException::class);

it('recalculates totals on each step', function (): void {
    $checkout = $this->checkoutService->start($this->cart);
    $result = $this->checkoutService->setAddress($checkout, [
        'email' => 'a@b.c',
        'shipping_address' => ['country' => 'DE'],
    ]);

    expect($result->totals_json)->not->toBeNull()
        ->and($result->totals_json['subtotal'])->toBe(5000);

    $result = $this->checkoutService->setShippingMethod($result->fresh(), $this->rate->id);

    expect($result->totals_json['shipping'])->toBe(599);
});
