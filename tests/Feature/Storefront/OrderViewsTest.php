<?php

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Livewire\Storefront\Account\Orders\Index as AccountOrdersIndex;
use App\Livewire\Storefront\Account\Orders\Show as AccountOrderShow;
use App\Livewire\Storefront\Checkout\Confirmation;
use App\Livewire\Storefront\Checkout\Show as CheckoutShow;
use App\Models\Checkout;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderLine;
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

function orderViewsStore(): Store
{
    $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    app()->instance('current_store', $store);

    return $store;
}

function orderViewsVariant(Store $store): ProductVariant
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

function orderViewsShippingRate(Store $store): ShippingRate
{
    return ShippingRate::withoutGlobalScopes()
        ->whereHas('zone', fn ($query) => $query->withoutGlobalScopes()->where('store_id', $store->getKey()))
        ->where('name', 'Standard Shipping')
        ->firstOrFail();
}

test('checkout page places an order and redirects to confirmation', function () {
    $store = orderViewsStore();
    $variant = orderViewsVariant($store);
    $cart = app(CartService::class)->create($store);
    session(['cart_id' => $cart->getKey()]);
    app(CartService::class)->addLine($cart, $variant->getKey(), 2);

    $component = Livewire::test(CheckoutShow::class)
        ->set('email', 'buyer@example.test')
        ->set('shippingAddress.first_name', 'Test')
        ->set('shippingAddress.last_name', 'Buyer')
        ->set('shippingAddress.address1', 'Main Street 1')
        ->set('shippingAddress.city', 'Berlin')
        ->set('shippingAddress.country', 'DE')
        ->set('shippingAddress.postal_code', '10115')
        ->call('saveAddress')
        ->set('selectedShippingRateId', orderViewsShippingRate($store)->getKey())
        ->call('selectShippingMethod')
        ->set('paymentMethod', 'credit_card')
        ->call('selectPaymentMethod')
        ->set('cardNumber', '4242 4242 4242 4242')
        ->call('placeOrder');

    $order = Order::withoutGlobalScopes()->firstOrFail();

    $component->assertRedirect(route('checkout.confirmation', $order));

    expect($order->email)->toBe('buyer@example.test')
        ->and($order->lines)->toHaveCount(1)
        ->and($cart->refresh()->status)->toBe(CartStatus::Converted)
        ->and(session('last_order_id'))->toBe($order->getKey())
        ->and(session('cart_id'))->toBeNull();
});

test('checkout page surfaces payment failures and releases reservations', function () {
    $store = orderViewsStore();
    $variant = orderViewsVariant($store);
    $cart = app(CartService::class)->create($store);
    session(['cart_id' => $cart->getKey()]);
    app(CartService::class)->addLine($cart, $variant->getKey(), 2);

    Livewire::test(CheckoutShow::class)
        ->set('email', 'buyer@example.test')
        ->set('shippingAddress.first_name', 'Test')
        ->set('shippingAddress.last_name', 'Buyer')
        ->set('shippingAddress.address1', 'Main Street 1')
        ->set('shippingAddress.city', 'Berlin')
        ->set('shippingAddress.country', 'DE')
        ->set('shippingAddress.postal_code', '10115')
        ->call('saveAddress')
        ->set('selectedShippingRateId', orderViewsShippingRate($store)->getKey())
        ->call('selectShippingMethod')
        ->set('paymentMethod', 'credit_card')
        ->call('selectPaymentMethod')
        ->set('cardNumber', '4000 0000 0000 0002')
        ->call('placeOrder')
        ->assertHasErrors('cardNumber')
        ->assertSet('step', 'payment');

    $checkout = Checkout::withoutGlobalScopes()->where('cart_id', $cart->getKey())->firstOrFail();
    $inventory = InventoryItem::withoutGlobalScopes()->where('variant_id', $variant->getKey())->firstOrFail();

    expect(Order::withoutGlobalScopes()->count())->toBe(0)
        ->and($checkout->status)->toBe(CheckoutStatus::ShippingSelected)
        ->and($inventory->quantity_reserved)->toBe(0);
});

test('order confirmation is visible only for the session order or customer order', function () {
    $store = orderViewsStore();
    $customer = Customer::withoutGlobalScopes()->where('store_id', $store->getKey())->firstOrFail();
    $otherCustomer = Customer::factory()->create(['store_id' => $store->getKey()]);
    $order = Order::factory()->forCustomer($customer)->paid()->create([
        'store_id' => $store->getKey(),
        'customer_id' => $customer->getKey(),
        'email' => $customer->email,
    ]);

    session(['last_order_id' => $order->getKey()]);

    Livewire::test(Confirmation::class, ['order' => $order])
        ->assertSee($order->order_number);

    session()->forget('last_order_id');

    Livewire::test(Confirmation::class, ['order' => $order])
        ->assertStatus(404);

    $this->actingAs($otherCustomer, 'customer');

    Livewire::test(Confirmation::class, ['order' => $order])
        ->assertStatus(404);

    $this->actingAs($customer, 'customer');

    Livewire::test(Confirmation::class, ['order' => $order])
        ->assertSee($order->order_number);
});

test('customer account lists and shows customer orders', function () {
    $store = orderViewsStore();
    $customer = Customer::withoutGlobalScopes()->where('store_id', $store->getKey())->firstOrFail();
    $otherCustomer = Customer::factory()->create(['store_id' => $store->getKey()]);
    $order = Order::factory()->forCustomer($customer)->paid()->create([
        'store_id' => $store->getKey(),
        'customer_id' => $customer->getKey(),
        'order_number' => '#7777',
    ]);
    $otherOrder = Order::factory()->forCustomer($otherCustomer)->paid()->create([
        'store_id' => $store->getKey(),
        'customer_id' => $otherCustomer->getKey(),
        'order_number' => '#8888',
    ]);
    OrderLine::factory()->create([
        'order_id' => $order->getKey(),
        'title_snapshot' => 'Classic Cotton T-Shirt',
    ]);

    $this->actingAs($customer, 'customer');

    Livewire::test(AccountOrdersIndex::class)
        ->assertSee('#7777')
        ->assertDontSee('#8888');

    Livewire::test(AccountOrderShow::class, ['order' => $order])
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('#7777');

    Livewire::test(AccountOrderShow::class, ['order' => $otherOrder])
        ->assertStatus(404);
});
