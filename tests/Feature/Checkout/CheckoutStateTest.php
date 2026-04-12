<?php

use App\Enums\CheckoutStatus;
use App\Jobs\ExpireAbandonedCheckouts;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\DiscountService;
use App\Services\InventoryService;
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
        new PricingEngine(new DiscountService, new ShippingCalculator, new TaxCalculator),
        $this->inventoryService,
    );
});

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

it('expires checkouts via job and releases reserved inventory', function (): void {
    $product = Product::factory()->for($this->store)->create();
    $variant = ProductVariant::factory()->for($product)->create([
        'price_amount' => 1000,
        'requires_shipping' => false,
    ]);
    $inventory = InventoryItem::factory()
        ->for($this->store)
        ->for($variant, 'variant')
        ->create(['quantity_on_hand' => 10, 'quantity_reserved' => 0]);

    $cart = $this->cartService->create($this->store);
    $this->cartService->addLine($cart, $variant->id, 2);
    $checkout = $this->checkoutService->start($cart);
    $this->checkoutService->setAddress($checkout, [
        'email' => 'a@b.c',
        'shipping_address' => ['country' => 'DE'],
    ]);
    $this->checkoutService->selectPaymentMethod($checkout->fresh(), 'credit_card');

    expect($inventory->fresh()->quantity_reserved)->toBe(2);

    $checkout->fresh()->update(['expires_at' => now()->subHour()]);

    app(ExpireAbandonedCheckouts::class)->handle($this->checkoutService);

    expect($checkout->fresh()->status)->toBe(CheckoutStatus::Expired)
        ->and($inventory->fresh()->quantity_reserved)->toBe(0);
});
