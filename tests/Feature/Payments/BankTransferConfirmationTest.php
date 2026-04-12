<?php

use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Jobs\CancelUnpaidBankTransferOrders;
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

    $product = Product::factory()->for($this->store)->create(['title' => 'Wool Scarf']);
    $this->variant = ProductVariant::factory()->for($product)->create([
        'price_amount' => 3000,
        'requires_shipping' => true,
    ]);
    InventoryItem::factory()
        ->for($this->store)
        ->for($this->variant, 'variant')
        ->create(['quantity_on_hand' => 5]);

    $this->zone = ShippingZone::factory()->for($this->store)->create([
        'countries_json' => ['DE'],
        'regions_json' => [],
    ]);
    $this->rate = ShippingRate::factory()->for($this->zone, 'zone')->flat(599)->create();
});

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

function completeWithBankTransfer(): \App\Models\Order
{
    $cart = test()->cartService->create(test()->store);
    test()->cartService->addLine($cart, test()->variant->id, 1);

    $checkout = test()->checkoutService->start($cart->fresh());
    test()->checkoutService->setAddress($checkout, [
        'email' => 'b@c.d',
        'shipping_address' => ['country' => 'DE'],
    ]);
    test()->checkoutService->setShippingMethod($checkout->fresh(), test()->rate->id);
    test()->checkoutService->selectPaymentMethod($checkout->fresh(), 'bank_transfer');
    test()->checkoutService->complete($checkout->fresh());

    return \App\Models\Order::withoutGlobalScopes()->latest('id')->firstOrFail();
}

it('creates a pending order for bank transfer without committing inventory', function (): void {
    $order = completeWithBankTransfer();
    $inventory = InventoryItem::withoutGlobalScopes()->where('variant_id', $this->variant->id)->first();

    expect($order->financial_status)->toBe(FinancialStatus::Pending)
        ->and($order->status)->toBe(OrderStatus::Pending)
        ->and($inventory->quantity_reserved)->toBe(1)
        ->and($inventory->quantity_on_hand)->toBe(5);

    $payment = $order->payments()->first();
    expect($payment->status)->toBe(PaymentStatus::Pending);
});

it('confirms bank transfer payment and commits inventory', function (): void {
    $order = completeWithBankTransfer();

    $this->orderService->confirmBankTransferPayment($order->fresh());

    $inventory = InventoryItem::withoutGlobalScopes()->where('variant_id', $this->variant->id)->first();

    expect($order->fresh()->financial_status)->toBe(FinancialStatus::Paid)
        ->and($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($inventory->quantity_on_hand)->toBe(4)
        ->and($inventory->quantity_reserved)->toBe(0);

    $payment = $order->payments()->first();
    expect($payment->status)->toBe(PaymentStatus::Captured);
});

it('cancels pending bank transfer orders older than 7 days via job', function (): void {
    $order = completeWithBankTransfer();
    $order->update(['placed_at' => now()->subDays(8)]);

    app(CancelUnpaidBankTransferOrders::class)->handle($this->orderService);

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled);

    $inventory = InventoryItem::withoutGlobalScopes()->where('variant_id', $this->variant->id)->first();
    expect($inventory->quantity_reserved)->toBe(0);
});

it('does not cancel bank transfer orders placed less than 7 days ago', function (): void {
    $order = completeWithBankTransfer();
    $order->update(['placed_at' => now()->subDays(3)]);

    app(CancelUnpaidBankTransferOrders::class)->handle($this->orderService);

    expect($order->fresh()->status)->toBe(OrderStatus::Pending);
});
