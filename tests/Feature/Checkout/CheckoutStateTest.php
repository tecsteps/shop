<?php

use App\Enums\CheckoutStatus;
use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Enums\ShippingRateType;
use App\Exceptions\CheckoutStateException;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\CartService;
use App\Services\CheckoutService;

beforeEach(function (): void {
    $ctx = $this->createStoreContext();
    $this->store = $ctx['store'];
    $this->cartService = app(CartService::class);
    $this->service = app(CheckoutService::class);

    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => ProductStatus::Active]);
    $this->variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2000, 'requires_shipping' => true]);
    InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $this->variant->id,
        'quantity_on_hand' => 10,
        'policy' => InventoryPolicy::Deny,
    ]);

    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id, 'countries_json' => ['US']]);
    $this->rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 799],
    ]);
});

function makeCart(mixed $ctx): \App\Models\Cart
{
    $cart = $ctx->cartService->create($ctx->store);
    $ctx->cartService->addLine($cart, $ctx->variant->id, 1);

    return $cart->fresh();
}

it('starts at status=started', function (): void {
    $cart = makeCart($this);
    $checkout = $this->service->startFromCart($cart);
    expect($checkout->status)->toBe(CheckoutStatus::Started);
});

it('reuses an existing active checkout for the same cart', function (): void {
    $cart = makeCart($this);
    $first = $this->service->startFromCart($cart);
    $second = $this->service->startFromCart($cart);
    expect($first->id)->toBe($second->id);
});

it('transitions to addressed after setAddress', function (): void {
    $cart = makeCart($this);
    $checkout = $this->service->startFromCart($cart);

    $checkout = $this->service->setAddress($checkout, [
        'email' => 'a@b.co',
        'shipping_address' => [
            'first_name' => 'A', 'last_name' => 'B',
            'address1' => '1', 'city' => 'NY',
            'country_code' => 'US', 'zip' => '10001',
        ],
    ]);

    expect($checkout->status)->toBe(CheckoutStatus::Addressed);
    expect($checkout->email)->toBe('a@b.co');
});

it('transitions to shipping_selected after setShippingMethod', function (): void {
    $cart = makeCart($this);
    $checkout = $this->service->startFromCart($cart);

    $this->service->setAddress($checkout, [
        'email' => 'a@b.co',
        'shipping_address' => [
            'first_name' => 'A', 'last_name' => 'B',
            'address1' => '1', 'city' => 'NY',
            'country_code' => 'US', 'zip' => '10001',
        ],
    ]);

    $checkout = $this->service->setShippingMethod($checkout->fresh(), $this->rate->id);

    expect($checkout->status)->toBe(CheckoutStatus::ShippingSelected);
    expect($checkout->shipping_method_id)->toBe($this->rate->id);
});

it('refuses to set shipping before address', function (): void {
    $cart = makeCart($this);
    $checkout = $this->service->startFromCart($cart);

    expect(fn () => $this->service->setShippingMethod($checkout, $this->rate->id))
        ->toThrow(CheckoutStateException::class);
});

it('reserves inventory when selectPaymentMethod is called', function (): void {
    $cart = makeCart($this);
    $checkout = $this->service->startFromCart($cart);

    $this->service->setAddress($checkout, [
        'email' => 'a@b.co',
        'shipping_address' => [
            'first_name' => 'A', 'last_name' => 'B',
            'address1' => '1', 'city' => 'NY',
            'country_code' => 'US', 'zip' => '10001',
        ],
    ]);
    $checkout = $this->service->setShippingMethod($checkout->fresh(), $this->rate->id);

    $checkout = $this->service->selectPaymentMethod($checkout, 'credit_card');

    expect($checkout->status)->toBe(CheckoutStatus::PaymentPending);
    expect($checkout->payment_method)->toBe('credit_card');

    $item = $this->variant->fresh()->inventoryItem;
    expect($item->quantity_reserved)->toBe(1);
});

it('releases inventory on expireCheckout when previously reserved', function (): void {
    $cart = makeCart($this);
    $checkout = $this->service->startFromCart($cart);

    $this->service->setAddress($checkout, [
        'email' => 'a@b.co',
        'shipping_address' => [
            'first_name' => 'A', 'last_name' => 'B',
            'address1' => '1', 'city' => 'NY',
            'country_code' => 'US', 'zip' => '10001',
        ],
    ]);
    $checkout = $this->service->setShippingMethod($checkout->fresh(), $this->rate->id);
    $checkout = $this->service->selectPaymentMethod($checkout, 'credit_card');

    $this->service->expireCheckout($checkout);

    expect($checkout->fresh()->status)->toBe(CheckoutStatus::Expired);
    expect($this->variant->fresh()->inventoryItem->quantity_reserved)->toBe(0);
});

it('rejects invalid payment methods', function (): void {
    $cart = makeCart($this);
    $checkout = $this->service->startFromCart($cart);

    $this->service->setAddress($checkout, [
        'email' => 'a@b.co',
        'shipping_address' => [
            'first_name' => 'A', 'last_name' => 'B',
            'address1' => '1', 'city' => 'NY',
            'country_code' => 'US', 'zip' => '10001',
        ],
    ]);
    $checkout = $this->service->setShippingMethod($checkout->fresh(), $this->rate->id);

    expect(fn () => $this->service->selectPaymentMethod($checkout, 'bitcoin'))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});
