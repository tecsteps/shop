<?php

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Enums\StoreDomainType;
use App\Livewire\Storefront\Account\Auth\SetPassword;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Notifications\CustomerWelcomeNotification;
use App\Services\OrderService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('creates a customer from a guest checkout and sends a welcome notification', function () {
    Notification::fake();

    $store = Store::factory()->create(['name' => 'Shop']);
    StoreDomain::factory()->create([
        'store_id' => $store->getKey(),
        'hostname' => 'shop.test',
        'type' => StoreDomainType::Storefront->value,
        'is_primary' => 1,
    ]);

    $product = Product::factory()->create(['store_id' => $store->getKey()]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->getKey(),
        'price_amount' => 1000,
        'requires_shipping' => 0,
    ]);
    InventoryItem::factory()->create([
        'store_id' => $store->getKey(),
        'variant_id' => $variant->getKey(),
        'quantity_on_hand' => 10,
        'quantity_reserved' => 0,
    ]);

    $cart = Cart::query()->create([
        'store_id' => $store->getKey(),
        'customer_id' => null,
        'currency' => 'USD',
        'status' => CartStatus::Active->value,
    ]);
    CartLine::query()->create([
        'cart_id' => $cart->getKey(),
        'variant_id' => $variant->getKey(),
        'quantity' => 1,
        'unit_price_amount' => 1000,
        'line_total_amount' => 1000,
        'line_discount_amount' => 0,
    ]);

    $checkout = Checkout::query()->create([
        'store_id' => $store->getKey(),
        'cart_id' => $cart->getKey(),
        'customer_id' => null,
        'status' => CheckoutStatus::PaymentSelected->value,
        'email' => 'guest@example.com',
        'shipping_address_json' => [
            'first_name' => 'Guest',
            'last_name' => 'Shopper',
            'address1' => '1 Test St',
            'city' => 'Townsville',
            'country_code' => 'US',
            'postal_code' => '12345',
        ],
        'billing_address_json' => [],
        'payment_method' => PaymentMethod::CreditCard->value,
        'totals_json' => ['subtotal' => 1000, 'discount' => 0, 'shipping' => 0, 'tax_total' => 0, 'total' => 1000],
    ]);

    app()->instance('current_store', $store);

    $order = app(OrderService::class)->createFromCheckout($checkout);

    expect($order->customer_id)->not->toBeNull();

    $customer = Customer::query()->withoutGlobalScopes()->find($order->customer_id);
    expect($customer)->not->toBeNull()
        ->and($customer->email)->toBe('guest@example.com')
        ->and($customer->password_hash)->toBeNull()
        ->and($customer->hasPassword())->toBeFalse();

    Notification::assertSentTo($customer, CustomerWelcomeNotification::class);
});

it('lets a guest customer set a password via the set-password link', function () {
    $store = Store::factory()->create(['name' => 'Shop']);
    StoreDomain::factory()->create([
        'store_id' => $store->getKey(),
        'hostname' => 'shop.test',
        'type' => StoreDomainType::Storefront->value,
        'is_primary' => 1,
    ]);
    $customer = Customer::factory()->guest()->create([
        'store_id' => $store->getKey(),
        'email' => 'guest@example.com',
    ]);

    $this->get('http://shop.test/account/set-password');

    $token = Password::broker('customers')->createToken($customer);

    Livewire::test(SetPassword::class, ['token' => $token, 'email' => 'guest@example.com'])
        ->set('password', 'brandnew1')
        ->set('password_confirmation', 'brandnew1')
        ->call('setPassword');

    $customer->refresh();
    expect(Hash::check('brandnew1', $customer->password_hash))->toBeTrue()
        ->and($customer->email_verified_at)->not->toBeNull();
});
