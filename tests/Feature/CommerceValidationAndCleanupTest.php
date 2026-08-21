<?php

use App\Enums\CheckoutStatus;
use App\Jobs\CleanupAbandonedCarts;
use App\Jobs\ExpireAbandonedCheckouts;
use App\Models\Checkout;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Models\Store;
use App\Services\CartService;
use App\Services\InventoryService;
use Database\Seeders\ShopSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    config(['cache.default' => 'array']);
    $this->seed(ShopSeeder::class);
    $this->store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    app()->instance('current_store', $this->store);
    $this->variant = Product::query()->where('handle', 'classic-cotton-t-shirt')->firstOrFail()->variants()->firstOrFail();
});

test('checkout addresses validate every required nested field', function (): void {
    $cart = $this->postJson('http://shop.test/api/storefront/v1/carts')->assertCreated()->json();
    $this->postJson("http://shop.test/api/storefront/v1/carts/{$cart['id']}/lines", [
        'variant_id' => $this->variant->getKey(),
        'quantity' => 1,
        'cart_version' => 1,
    ])->assertCreated();
    $checkout = $this->postJson('http://shop.test/api/storefront/v1/checkouts', [
        'cart_id' => $cart['id'],
        'email' => 'address-validation@example.test',
    ])->assertCreated()->json();

    $this->putJson("http://shop.test/api/storefront/v1/checkouts/{$checkout['id']}/address", [
        'shipping_address' => ['first_name' => 'Incomplete'],
    ])->assertUnprocessable()->assertJsonValidationErrors([
        'shipping_address.last_name',
        'shipping_address.address1',
        'shipping_address.city',
        'shipping_address.country',
        'shipping_address.country_code',
        'shipping_address.postal_code',
    ]);

    $this->putJson("http://shop.test/api/storefront/v1/checkouts/{$checkout['id']}/address", [
        'shipping_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'Berlin',
            'country' => 'Germany',
            'country_code' => 'DE',
            'postal_code' => '10115',
        ],
        'use_shipping_as_billing' => false,
    ])->assertUnprocessable()->assertJsonValidationErrors([
        'billing_address',
        'billing_address.first_name',
    ]);
});

test('credit card payments require all card fields while other payment methods do not', function (): void {
    $cart = $this->postJson('http://shop.test/api/storefront/v1/carts')->assertCreated()->json();
    $this->postJson("http://shop.test/api/storefront/v1/carts/{$cart['id']}/lines", [
        'variant_id' => $this->variant->getKey(),
        'quantity' => 1,
        'cart_version' => 1,
    ])->assertCreated();
    $checkout = $this->postJson('http://shop.test/api/storefront/v1/checkouts', [
        'cart_id' => $cart['id'],
        'email' => 'card-validation@example.test',
    ])->assertCreated()->json();
    $address = [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'address1' => '123 Main St',
        'city' => 'Berlin',
        'country' => 'Germany',
        'country_code' => 'DE',
        'postal_code' => '10115',
    ];
    $this->putJson("http://shop.test/api/storefront/v1/checkouts/{$checkout['id']}/address", ['shipping_address' => $address])->assertOk();
    $rate = ShippingRate::query()->firstOrFail();
    $this->putJson("http://shop.test/api/storefront/v1/checkouts/{$checkout['id']}/shipping-method", ['shipping_method_id' => $rate->getKey()])->assertOk();

    $this->postJson("http://shop.test/api/storefront/v1/checkouts/{$checkout['id']}/pay", [
        'payment_method' => 'credit_card',
        'card_number' => '4242 4242 4242 4242',
    ])->assertUnprocessable()->assertJsonValidationErrors([
        'card_expiry',
        'card_cvc',
        'card_holder',
    ]);
});

test('abandoned cart cleanup does not release inventory owned by another checkout', function (): void {
    $carts = app(CartService::class);
    $inventory = InventoryItem::query()->where('variant_id', $this->variant->getKey())->firstOrFail();

    $expiredCart = $carts->create($this->store);
    $carts->addLine($expiredCart, $this->variant->getKey(), 1);
    $expiredCheckout = Checkout::withoutGlobalScopes()->create([
        'store_id' => $this->store->getKey(),
        'cart_id' => $expiredCart->getKey(),
        'email' => 'expired@example.test',
        'status' => CheckoutStatus::PaymentSelected,
        'expires_at' => now()->subMinute(),
    ]);
    app(InventoryService::class)->reserve($inventory, 1);

    app(ExpireAbandonedCheckouts::class)->handle(app(InventoryService::class));

    $activeCart = $carts->create($this->store);
    $carts->addLine($activeCart, $this->variant->getKey(), 1);
    $activeCheckout = Checkout::withoutGlobalScopes()->create([
        'store_id' => $this->store->getKey(),
        'cart_id' => $activeCart->getKey(),
        'email' => 'active@example.test',
        'status' => CheckoutStatus::PaymentSelected,
        'expires_at' => now()->addHour(),
    ]);
    app(InventoryService::class)->reserve($inventory->refresh(), 1);
    DB::table('carts')->where('id', $expiredCart->getKey())->update(['updated_at' => now()->subDays(15)]);

    app(CleanupAbandonedCarts::class)->handle(app(InventoryService::class));

    expect($expiredCheckout->refresh()->status)->toBe(CheckoutStatus::Expired)
        ->and($activeCheckout->refresh()->status)->toBe(CheckoutStatus::PaymentSelected)
        ->and($expiredCart->refresh()->status->value)->toBe('abandoned')
        ->and($inventory->refresh()->quantity_reserved)->toBe(1);
});

test('commerce rate limiters match the specification', function (): void {
    $request = Request::create('/api/storefront/v1/search', 'GET', [], [], [], ['REMOTE_ADDR' => '192.0.2.1']);
    $search = RateLimiter::limiter('search');
    $webhooks = RateLimiter::limiter('webhooks');

    expect($search)->not->toBeNull()
        ->and($webhooks)->not->toBeNull()
        ->and($search($request)->maxAttempts)->toBe(30)
        ->and($webhooks($request)->maxAttempts)->toBe(100)
        ->and($search($request)->decaySeconds)->toBe(60)
        ->and($webhooks($request)->decaySeconds)->toBe(60);
});
