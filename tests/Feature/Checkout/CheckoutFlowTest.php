<?php

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
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

    TaxSettings::factory()->create(['store_id' => $this->store->id, 'rate' => 1900]);

    $this->zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['DE'],
    ]);
    $this->rate = ShippingRate::factory()->create([
        'zone_id' => $this->zone->id,
        'config_json' => ['amount' => 499],
    ]);

    $this->product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $this->variant = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'price_amount' => 2500,
    ]);
    InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $this->variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 0,
        'policy' => 'deny',
    ]);

    $this->cart = Cart::factory()->create(['store_id' => $this->store->id]);
    CartLine::factory()->create([
        'cart_id' => $this->cart->id,
        'variant_id' => $this->variant->id,
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 5000,
        'line_total_amount' => 5000,
    ]);

    $this->addressData = [
        'email' => 'customer@example.com',
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'Berlin',
            'country' => 'DE',
            'postal_code' => '10115',
        ],
    ];
});

it('completes full checkout happy path', function () {
    $checkout = $this->checkoutService->createFromCart($this->store, $this->cart);
    expect($checkout->status)->toBe(CheckoutStatus::Started);

    $checkout = $this->checkoutService->setAddress($checkout, $this->addressData);
    expect($checkout->status)->toBe(CheckoutStatus::Addressed);

    $checkout = $this->checkoutService->setShippingMethod($checkout, $this->rate->id);
    expect($checkout->status)->toBe(CheckoutStatus::ShippingSelected);

    $checkout = $this->checkoutService->selectPaymentMethod($checkout, 'credit_card');
    expect($checkout->status)->toBe(CheckoutStatus::PaymentSelected);

    $this->variant->inventoryItem->refresh();
    expect($this->variant->inventoryItem->quantity_reserved)->toBe(2);

    $checkout = $this->checkoutService->completeCheckout($checkout);
    expect($checkout->status)->toBe(CheckoutStatus::Completed);

    $this->cart->refresh();
    expect($this->cart->status)->toBe(CartStatus::Converted);

    $this->variant->inventoryItem->refresh();
    expect($this->variant->inventoryItem->quantity_on_hand)->toBe(8)
        ->and($this->variant->inventoryItem->quantity_reserved)->toBe(0);
});

it('completes checkout with bank transfer keeping inventory reserved', function () {
    $checkout = $this->checkoutService->createFromCart($this->store, $this->cart);
    $checkout = $this->checkoutService->setAddress($checkout, $this->addressData);
    $checkout = $this->checkoutService->setShippingMethod($checkout, $this->rate->id);
    $checkout = $this->checkoutService->selectPaymentMethod($checkout, 'bank_transfer');
    $checkout = $this->checkoutService->completeCheckout($checkout);

    expect($checkout->status)->toBe(CheckoutStatus::Completed);

    $this->variant->inventoryItem->refresh();
    // bank_transfer keeps inventory reserved, not committed
    expect($this->variant->inventoryItem->quantity_on_hand)->toBe(10)
        ->and($this->variant->inventoryItem->quantity_reserved)->toBe(2);
});

it('prevents duplicate orders (idempotency)', function () {
    $checkout = $this->checkoutService->createFromCart($this->store, $this->cart);
    $checkout = $this->checkoutService->setAddress($checkout, $this->addressData);
    $checkout = $this->checkoutService->setShippingMethod($checkout, $this->rate->id);
    $checkout = $this->checkoutService->selectPaymentMethod($checkout, 'credit_card');
    $result1 = $this->checkoutService->completeCheckout($checkout);
    $result2 = $this->checkoutService->completeCheckout($result1);

    expect($result2->id)->toBe($result1->id);
});

it('applies discount in checkout flow', function () {
    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'SAVE10',
        'value_type' => 'percent',
        'value_amount' => 10,
    ]);

    $checkout = $this->checkoutService->createFromCart($this->store, $this->cart);
    $checkout = $this->checkoutService->setAddress($checkout, $this->addressData);
    $checkout = $this->checkoutService->applyDiscount($checkout, 'SAVE10');

    expect($checkout->discount_code)->toBe('SAVE10')
        ->and($checkout->totals_json['discount'])->toBe(500);
});

it('removes discount when cleared', function () {
    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'SAVE10',
        'value_type' => 'percent',
        'value_amount' => 10,
    ]);

    $checkout = $this->checkoutService->createFromCart($this->store, $this->cart);
    $checkout = $this->checkoutService->setAddress($checkout, $this->addressData);
    $checkout = $this->checkoutService->applyDiscount($checkout, 'SAVE10');

    expect($checkout->totals_json['discount'])->toBe(500);

    $checkout = $this->checkoutService->removeDiscount($checkout);

    expect($checkout->discount_code)->toBeNull()
        ->and($checkout->totals_json['discount'])->toBe(0);
});
