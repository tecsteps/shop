<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
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
    $this->orderService = new OrderService;
    $this->checkoutService = new CheckoutService(
        new PricingEngine(new DiscountService, new ShippingCalculator, new TaxCalculator),
        $this->inventoryService,
        $this->orderService,
        new MockPaymentProvider,
    );

    $product = Product::factory()->for($this->store)->create();
    $this->variant = ProductVariant::factory()->for($product)->create([
        'price_amount' => 4500,
        'requires_shipping' => true,
    ]);
    InventoryItem::factory()
        ->for($this->store)
        ->for($this->variant, 'variant')
        ->create(['quantity_on_hand' => 10]);

    $this->zone = ShippingZone::factory()->for($this->store)->create([
        'countries_json' => ['DE'],
        'regions_json' => [],
    ]);
    $this->rate = ShippingRate::factory()->for($this->zone, 'zone')->flat(599)->create();
});

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

it('records a payment with method and captured status on successful complete', function (): void {
    $cart = $this->cartService->create($this->store);
    $this->cartService->addLine($cart, $this->variant->id, 1);
    $checkout = $this->checkoutService->start($cart->fresh());
    $this->checkoutService->setAddress($checkout, [
        'email' => 'a@b.c',
        'shipping_address' => ['country' => 'DE'],
    ]);
    $this->checkoutService->setShippingMethod($checkout->fresh(), $this->rate->id);
    $this->checkoutService->selectPaymentMethod($checkout->fresh(), 'credit_card');

    $this->checkoutService->complete($checkout->fresh(), ['card_number' => '4242424242424242']);

    $payment = \App\Models\Payment::query()->first();
    expect($payment)->not->toBeNull()
        ->and($payment->method)->toBe(PaymentMethod::CreditCard)
        ->and($payment->status)->toBe(PaymentStatus::Captured)
        ->and($payment->amount)->toBeGreaterThan(0)
        ->and($payment->provider)->toBe('mock')
        ->and($payment->provider_payment_id)->toStartWith('mock_');
});
