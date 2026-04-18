<?php

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Enums\ShippingRateType;
use App\Jobs\CleanupAbandonedCarts;
use App\Jobs\ExpireAbandonedCheckouts;
use App\Models\Cart;
use App\Models\Checkout;
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

it('runs the full happy-path checkout flow', function (): void {
    $cart = $this->cartService->create($this->store);
    $this->cartService->addLine($cart, $this->variant->id, 2);

    $checkout = $this->service->startFromCart($cart);
    $this->service->setAddress($checkout, [
        'email' => 'a@b.co',
        'shipping_address' => [
            'first_name' => 'A', 'last_name' => 'B',
            'address1' => '1', 'city' => 'NY',
            'country_code' => 'US', 'zip' => '10001',
        ],
    ]);
    $this->service->setShippingMethod($checkout->fresh(), $this->rate->id);
    $checkout = $this->service->selectPaymentMethod($checkout->fresh(), 'credit_card');

    expect($checkout->status)->toBe(CheckoutStatus::PaymentPending);
    expect($checkout->expires_at)->not->toBeNull();
});

it('releases reserved inventory when checkout expires via job', function (): void {
    $cart = $this->cartService->create($this->store);
    $this->cartService->addLine($cart, $this->variant->id, 1);

    $checkout = $this->service->startFromCart($cart);
    $this->service->setAddress($checkout, [
        'email' => 'a@b.co',
        'shipping_address' => [
            'first_name' => 'A', 'last_name' => 'B',
            'address1' => '1', 'city' => 'NY',
            'country_code' => 'US', 'zip' => '10001',
        ],
    ]);
    $this->service->setShippingMethod($checkout->fresh(), $this->rate->id);
    $this->service->selectPaymentMethod($checkout->fresh(), 'credit_card');

    // Force checkout into expired candidate by pushing expires_at into the past
    $fresh = Checkout::query()->find($checkout->id);
    $fresh->expires_at = now()->subHour();
    $fresh->save();

    (new ExpireAbandonedCheckouts)->handle($this->service);

    expect($fresh->fresh()->status)->toBe(CheckoutStatus::Expired);
    expect($this->variant->fresh()->inventoryItem->quantity_reserved)->toBe(0);
});

it('marks old carts as abandoned', function (): void {
    $cart = $this->cartService->create($this->store);
    $this->cartService->addLine($cart, $this->variant->id, 1);

    Cart::query()->whereKey($cart->id)->update(['updated_at' => now()->subDays(20)]);

    (new CleanupAbandonedCarts)->handle();

    expect(Cart::query()->find($cart->id)->status)->toBe(CartStatus::Abandoned);
});
