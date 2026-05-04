<?php

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Livewire\Storefront\Account\Auth\Login as CustomerLogin;
use App\Livewire\Storefront\Cart\Show as CartShow;
use App\Livewire\Storefront\Checkout\Show as CheckoutShow;
use App\Livewire\Storefront\Products\Show as ProductShow;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\Store;
use App\Services\CartService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function storefrontUiStore(): Store
{
    $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    app()->instance('current_store', $store);

    return $store;
}

function storefrontUiVariant(Store $store): ProductVariant
{
    $product = Product::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('handle', 'classic-cotton-t-shirt')
        ->firstOrFail();

    return ProductVariant::withoutGlobalScopes()
        ->where('product_id', $product->getKey())
        ->oldest('position')
        ->firstOrFail();
}

test('product detail add to cart persists a session cart', function () {
    $store = storefrontUiStore();
    $variant = storefrontUiVariant($store);

    Livewire::test(ProductShow::class, ['handle' => 'classic-cotton-t-shirt'])
        ->set('quantity', 2)
        ->call('addToCart')
        ->assertDispatched('cart-updated');

    $cart = Cart::withoutGlobalScopes()->findOrFail(session('cart_id'));

    expect($cart->store_id)->toBe($store->getKey())
        ->and($cart->lines()->withoutGlobalScopes()->where('variant_id', $variant->getKey())->first()?->quantity)->toBe(2);
});

test('cart page updates and removes line quantities', function () {
    $store = storefrontUiStore();
    $variant = storefrontUiVariant($store);
    $cart = app(CartService::class)->create($store);
    session(['cart_id' => $cart->getKey()]);
    $line = app(CartService::class)->addLine($cart, $variant->getKey(), 2);

    Livewire::test(CartShow::class)
        ->assertSee('Classic Cotton T-Shirt')
        ->call('increaseQuantity', $line->getKey())
        ->assertDispatched('cart-updated');

    expect($line->refresh()->quantity)->toBe(3);

    Livewire::test(CartShow::class)
        ->call('removeLine', $line->getKey())
        ->assertDispatched('cart-updated');

    expect($cart->lines()->withoutGlobalScopes()->count())->toBe(0);
});

test('cart page applies discount estimates shipping and carries discount into checkout', function () {
    $store = storefrontUiStore();
    $variant = storefrontUiVariant($store);
    $cart = app(CartService::class)->create($store);
    session(['cart_id' => $cart->getKey()]);
    app(CartService::class)->addLine($cart, $variant->getKey(), 2);

    $component = Livewire::test(CartShow::class)
        ->set('discountCode', 'SAVE10')
        ->call('applyDiscount')
        ->assertHasNoErrors()
        ->assertSet('appliedDiscountCode', 'SAVE10')
        ->set('shippingCountry', 'DE')
        ->set('shippingPostalCode', '10115')
        ->call('estimateShipping')
        ->assertHasNoErrors()
        ->assertSee('Standard Shipping');

    expect(session('cart_discount_code'))->toBe('SAVE10')
        ->and($component->instance()->discountAmount())->toBe(500)
        ->and($component->instance()->estimatedShippingAmount())->toBe(499)
        ->and($component->instance()->estimatedTotal())->toBe(4997);

    Livewire::test(CheckoutShow::class)
        ->assertSet('discountCode', 'SAVE10');

    $checkout = Checkout::withoutGlobalScopes()->where('cart_id', $cart->getKey())->firstOrFail();

    expect($checkout->discount_code)->toBe('SAVE10')
        ->and($checkout->totals_json['discount'])->toBe(500);
});

test('customer login merges an existing guest cart without creating empty carts', function () {
    $store = storefrontUiStore();
    $variant = storefrontUiVariant($store);
    $customer = Customer::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('email', 'customer@acme.test')
        ->firstOrFail();
    $customerCart = app(CartService::class)->create($store, $customer);
    $guestCart = app(CartService::class)->create($store);
    session(['cart_id' => $guestCart->getKey()]);

    app(CartService::class)->addLine($customerCart, $variant->getKey(), 1);
    app(CartService::class)->addLine($guestCart, $variant->getKey(), 2);

    Livewire::test(CustomerLogin::class)
        ->set('email', 'customer@acme.test')
        ->set('password', 'password')
        ->call('login');

    expect($guestCart->refresh()->status)->toBe(CartStatus::Abandoned)
        ->and($customerCart->refresh()->lines()->withoutGlobalScopes()->where('variant_id', $variant->getKey())->first()?->quantity)->toBe(2)
        ->and(Cart::withoutGlobalScopes()->where('store_id', $store->getKey())->count())->toBe(2);
});

test('checkout page progresses through address shipping discount and payment selection', function () {
    $store = storefrontUiStore();
    $variant = storefrontUiVariant($store);
    $cart = app(CartService::class)->create($store);
    session(['cart_id' => $cart->getKey()]);
    app(CartService::class)->addLine($cart, $variant->getKey(), 2);

    $rate = ShippingRate::withoutGlobalScopes()
        ->whereHas('zone', fn ($query) => $query->withoutGlobalScopes()->where('store_id', $store->getKey()))
        ->where('name', 'Standard Shipping')
        ->firstOrFail();

    Livewire::test(CheckoutShow::class)
        ->set('email', 'buyer@example.test')
        ->set('shippingAddress.first_name', 'Test')
        ->set('shippingAddress.last_name', 'Buyer')
        ->set('shippingAddress.address1', 'Main Street 1')
        ->set('shippingAddress.city', 'Berlin')
        ->set('shippingAddress.country', 'DE')
        ->set('shippingAddress.postal_code', '10115')
        ->call('saveAddress')
        ->assertSet('step', 'shipping')
        ->set('selectedShippingRateId', $rate->getKey())
        ->call('selectShippingMethod')
        ->assertSet('step', 'payment')
        ->set('discountCode', 'SAVE10')
        ->call('applyDiscount')
        ->assertHasNoErrors()
        ->set('paymentMethod', 'credit_card')
        ->call('selectPaymentMethod')
        ->assertSet('step', 'reserved');

    $checkout = Checkout::withoutGlobalScopes()->where('cart_id', $cart->getKey())->firstOrFail();
    $inventory = InventoryItem::withoutGlobalScopes()->where('variant_id', $variant->getKey())->firstOrFail();

    expect($checkout->status)->toBe(CheckoutStatus::PaymentSelected)
        ->and($checkout->discount_code)->toBe('SAVE10')
        ->and($checkout->totals_json['discount'])->toBeGreaterThan(0)
        ->and($inventory->quantity_reserved)->toBe(2);
});
