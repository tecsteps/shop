<?php

use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Discount;
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

    TaxSettings::factory()->create(['store_id' => $this->store->id, 'rate' => 0, 'is_active' => false]);

    $this->product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $this->variant = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'price_amount' => 5000,
    ]);

    $this->cart = Cart::factory()->create(['store_id' => $this->store->id]);
    CartLine::factory()->create([
        'cart_id' => $this->cart->id,
        'variant_id' => $this->variant->id,
        'quantity' => 2,
        'unit_price_amount' => 5000,
        'line_subtotal_amount' => 10000,
        'line_total_amount' => 10000,
    ]);

    $this->zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['DE'],
    ]);
    $this->rate = ShippingRate::factory()->create([
        'zone_id' => $this->zone->id,
        'config_json' => ['amount' => 499],
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

it('applies percent discount to checkout totals', function () {
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
        ->and($checkout->totals_json['discount'])->toBe(1000)
        ->and($checkout->totals_json['subtotal'])->toBe(10000);
});

it('applies fixed discount to checkout totals', function () {
    Discount::factory()->fixed(500)->create([
        'store_id' => $this->store->id,
        'code' => 'FLAT5',
    ]);

    $checkout = $this->checkoutService->createFromCart($this->store, $this->cart);
    $checkout = $this->checkoutService->setAddress($checkout, $this->addressData);
    $checkout = $this->checkoutService->applyDiscount($checkout, 'FLAT5');

    expect($checkout->totals_json['discount'])->toBe(500);
});

it('replaces existing discount when new one is applied', function () {
    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'SAVE10',
        'value_type' => 'percent',
        'value_amount' => 10,
    ]);

    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'SAVE20',
        'value_type' => 'percent',
        'value_amount' => 20,
    ]);

    $checkout = $this->checkoutService->createFromCart($this->store, $this->cart);
    $checkout = $this->checkoutService->setAddress($checkout, $this->addressData);

    $checkout = $this->checkoutService->applyDiscount($checkout, 'SAVE10');
    expect($checkout->totals_json['discount'])->toBe(1000);

    $checkout = $this->checkoutService->applyDiscount($checkout, 'SAVE20');
    expect($checkout->discount_code)->toBe('SAVE20')
        ->and($checkout->totals_json['discount'])->toBe(2000);
});

it('removes discount and recalculates totals', function () {
    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'SAVE10',
        'value_type' => 'percent',
        'value_amount' => 10,
    ]);

    $checkout = $this->checkoutService->createFromCart($this->store, $this->cart);
    $checkout = $this->checkoutService->setAddress($checkout, $this->addressData);
    $checkout = $this->checkoutService->applyDiscount($checkout, 'SAVE10');

    expect($checkout->totals_json['discount'])->toBe(1000);

    $checkout = $this->checkoutService->removeDiscount($checkout);

    expect($checkout->discount_code)->toBeNull()
        ->and($checkout->totals_json['discount'])->toBe(0);
});

it('rejects invalid discount code with exception', function () {
    $checkout = $this->checkoutService->createFromCart($this->store, $this->cart);
    $checkout = $this->checkoutService->setAddress($checkout, $this->addressData);

    expect(fn () => $this->checkoutService->applyDiscount($checkout, 'INVALID_CODE'))
        ->toThrow(\App\Exceptions\InvalidDiscountException::class);

    // Discount code should not be stored
    $checkout->refresh();
    expect($checkout->discount_code)->toBeNull();
});

it('increments discount usage count on checkout completion', function () {
    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'TRACK',
        'value_type' => 'percent',
        'value_amount' => 5,
        'usage_count' => 0,
    ]);

    \App\Models\InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $this->variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 0,
        'policy' => 'deny',
    ]);

    $checkout = $this->checkoutService->createFromCart($this->store, $this->cart);
    $checkout = $this->checkoutService->setAddress($checkout, $this->addressData);
    $checkout = $this->checkoutService->applyDiscount($checkout, 'TRACK');
    $checkout = $this->checkoutService->setShippingMethod($checkout, $this->rate->id);
    $checkout = $this->checkoutService->selectPaymentMethod($checkout, 'credit_card');
    $checkout = $this->checkoutService->completeCheckout($checkout);

    $discount = Discount::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('code', 'TRACK')
        ->first();

    expect($discount->usage_count)->toBe(1);
});
