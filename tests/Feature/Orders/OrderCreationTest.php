<?php

use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
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

    $this->product = Product::factory()->for($this->store)->create(['title' => 'Herbal Tea']);
    $this->variant = ProductVariant::factory()->for($this->product)->create([
        'price_amount' => 2500,
        'requires_shipping' => true,
        'sku' => 'TEA-001',
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

function runCheckoutToPayment(): \App\Models\Checkout
{
    $cart = test()->cartService->create(test()->store);
    test()->cartService->addLine($cart, test()->variant->id, 2);

    $checkout = test()->checkoutService->start($cart->fresh());
    test()->checkoutService->setAddress($checkout, [
        'email' => 'buyer@example.com',
        'shipping_address' => ['country' => 'DE'],
    ]);
    test()->checkoutService->setShippingMethod($checkout->fresh(), test()->rate->id);
    test()->checkoutService->selectPaymentMethod($checkout->fresh(), 'credit_card');

    return $checkout->fresh();
}

it('creates an order from a completed checkout', function (): void {
    $checkout = runCheckoutToPayment();

    $this->checkoutService->complete($checkout, ['card_number' => '4242424242424242']);

    $order = \App\Models\Order::withoutGlobalScopes()->first();

    expect($order)->not->toBeNull()
        ->and($order->store_id)->toBe($this->store->id)
        ->and($order->email)->toBe('buyer@example.com')
        ->and($order->status)->toBe(OrderStatus::Paid)
        ->and($order->financial_status)->toBe(FinancialStatus::Paid)
        ->and($order->payment_method)->toBe(PaymentMethod::CreditCard)
        ->and($order->lines)->toHaveCount(1)
        ->and($order->lines->first()->title_snapshot)->toBe('Herbal Tea')
        ->and($order->lines->first()->sku_snapshot)->toBe('TEA-001')
        ->and($order->lines->first()->quantity)->toBe(2);
});

it('generates sequential order numbers per store', function (): void {
    $checkout = runCheckoutToPayment();
    $order1 = $this->orderService->createFromCheckout($checkout);

    // Second cart/checkout cycle in same store
    $cart2 = $this->cartService->create($this->store);
    $this->cartService->addLine($cart2, $this->variant->id, 1);
    $checkout2 = $this->checkoutService->start($cart2->fresh());
    $this->checkoutService->setAddress($checkout2, [
        'email' => 'b@c.d',
        'shipping_address' => ['country' => 'DE'],
    ]);
    $this->checkoutService->setShippingMethod($checkout2->fresh(), $this->rate->id);
    $this->checkoutService->selectPaymentMethod($checkout2->fresh(), 'credit_card');

    $order2 = $this->orderService->createFromCheckout($checkout2->fresh());

    expect($order1->order_number)->toBe('#1001')
        ->and($order2->order_number)->toBe('#1002');
});

it('cancels an order and releases reserved inventory', function (): void {
    $checkout = runCheckoutToPayment();
    $inventory = InventoryItem::withoutGlobalScopes()->where('variant_id', $this->variant->id)->first();

    expect($inventory->quantity_reserved)->toBe(2);

    $order = $this->orderService->createFromCheckout($checkout);

    $this->orderService->cancel($order->fresh(), 'Customer request');

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($inventory->fresh()->quantity_reserved)->toBe(0);
});

it('rejects payment with declined magic card and releases inventory', function (): void {
    $checkout = runCheckoutToPayment();
    $inventory = InventoryItem::withoutGlobalScopes()->where('variant_id', $this->variant->id)->first();

    expect($inventory->quantity_reserved)->toBe(2);

    try {
        $this->checkoutService->complete($checkout, ['card_number' => '4000000000000002']);
    } catch (\App\Exceptions\PaymentFailedException $e) {
        // expected
    }

    expect($inventory->fresh()->quantity_reserved)->toBe(0)
        ->and(\App\Models\Order::withoutGlobalScopes()->count())->toBe(0);
});
