<?php

use App\Enums\CartStatus;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Livewire\Storefront\Account\Auth\Login as CustomerLogin;
use App\Livewire\Storefront\Account\Auth\Register as CustomerRegister;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Services\CartService;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

it('renders the customer login page', function () {
    $ctx = createStoreContext('customer-store.test');

    $response = $this->get('http://customer-store.test/account/login');

    $response->assertStatus(200);
});

it('authenticates a customer with valid credentials', function () {
    $ctx = createStoreContext('customer-store.test');
    $customer = Customer::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'email' => 'customer@example.com',
        'password_hash' => Hash::make('password'),
        'name' => 'Test Customer',
    ]);

    Livewire::test(CustomerLogin::class)
        ->set('email', 'customer@example.com')
        ->set('password', 'password')
        ->call('login');

    $this->assertAuthenticatedAs($customer, 'customer');
});

it('rejects invalid customer credentials', function () {
    $ctx = createStoreContext('customer-store.test');
    Customer::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'email' => 'customer@example.com',
        'password_hash' => Hash::make('password'),
        'name' => 'Test Customer',
    ]);

    Livewire::test(CustomerLogin::class)
        ->set('email', 'customer@example.com')
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest('customer');
});

it('scopes customer login to the current store', function () {
    $ctxA = createStoreContext('store-a.test');
    Customer::withoutGlobalScopes()->create([
        'store_id' => $ctxA['store']->id,
        'email' => 'customer@example.com',
        'password_hash' => Hash::make('password'),
        'name' => 'Test Customer',
    ]);

    $orgB = Organization::factory()->create();
    $storeB = Store::factory()->create(['organization_id' => $orgB->id]);
    StoreDomain::factory()->create([
        'store_id' => $storeB->id,
        'hostname' => 'store-b.test',
    ]);

    // Bind store B as current store so login scopes to it
    app()->instance('current_store', $storeB);

    Livewire::test(CustomerLogin::class)
        ->set('email', 'customer@example.com')
        ->set('password', 'password')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest('customer');
});

it('rate limits customer login attempts', function () {
    $ctx = createStoreContext('customer-store.test');

    for ($i = 0; $i < 5; $i++) {
        Livewire::test(CustomerLogin::class)
            ->set('email', 'wrong@example.com')
            ->set('password', 'wrong-password')
            ->call('login');
    }

    Livewire::test(CustomerLogin::class)
        ->set('email', 'wrong@example.com')
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertStatus(429);
});

it('registers a new customer', function () {
    $ctx = createStoreContext('customer-store.test');

    Livewire::test(CustomerRegister::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register');

    $customer = Customer::withoutGlobalScopes()
        ->where('email', 'jane@example.com')
        ->where('store_id', $ctx['store']->id)
        ->first();

    expect($customer)->not->toBeNull();
    $this->assertAuthenticatedAs($customer, 'customer');

    $this->assertDatabaseHas('customers', [
        'store_id' => $ctx['store']->id,
        'email' => 'jane@example.com',
        'name' => 'Jane Doe',
    ]);
});

it('rejects duplicate email registration in the same store', function () {
    $ctx = createStoreContext('customer-store.test');
    Customer::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'email' => 'existing@example.com',
        'name' => 'Existing',
        'password_hash' => Hash::make('password'),
    ]);

    Livewire::test(CustomerRegister::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'existing@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertHasErrors('email');

    $this->assertGuest('customer');
});

it('allows same email in different stores', function () {
    $ctxA = createStoreContext('store-a.test');
    Customer::withoutGlobalScopes()->create([
        'store_id' => $ctxA['store']->id,
        'email' => 'shared@example.com',
        'name' => 'Customer A',
        'password_hash' => Hash::make('password'),
    ]);

    $orgB = Organization::factory()->create();
    $storeB = Store::factory()->create(['organization_id' => $orgB->id]);
    StoreDomain::factory()->create([
        'store_id' => $storeB->id,
        'hostname' => 'store-b.test',
    ]);

    // Bind store B as current store
    app()->instance('current_store', $storeB);

    Livewire::test(CustomerRegister::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'shared@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register');

    $customer = Customer::withoutGlobalScopes()
        ->where('email', 'shared@example.com')
        ->where('store_id', $storeB->id)
        ->first();

    expect($customer)->not->toBeNull();
    $this->assertAuthenticatedAs($customer, 'customer');
});

it('merges guest cart into customer cart on login', function () {
    $ctx = createStoreContext('customer-store.test');
    $store = $ctx['store'];

    $customer = Customer::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'email' => 'merge@example.com',
        'password_hash' => Hash::make('password'),
        'name' => 'Merge Customer',
    ]);

    $product = Product::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'title' => 'Merge Product',
        'handle' => 'merge-product-'.rand(1000, 9999),
        'status' => ProductStatus::Active,
        'published_at' => now(),
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'price_amount' => 2500,
        'currency' => 'EUR',
        'is_default' => true,
        'position' => 0,
        'status' => VariantStatus::Active,
    ]);

    InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 50,
        'quantity_reserved' => 0,
        'policy' => 'deny',
    ]);

    // Customer has an existing cart with qty 1
    $customerCart = Cart::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'customer_id' => $customer->id,
        'currency' => 'EUR',
        'cart_version' => 1,
        'status' => CartStatus::Active,
    ]);

    CartLine::create([
        'cart_id' => $customerCart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 2500,
        'line_discount_amount' => 0,
        'line_total_amount' => 2500,
    ]);

    // Guest cart with qty 3 for the same variant
    $guestCart = Cart::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'currency' => 'EUR',
        'cart_version' => 1,
        'status' => CartStatus::Active,
    ]);

    CartLine::create([
        'cart_id' => $guestCart->id,
        'variant_id' => $variant->id,
        'quantity' => 3,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 7500,
        'line_discount_amount' => 0,
        'line_total_amount' => 7500,
    ]);

    $cartService = app(CartService::class);
    $merged = $cartService->mergeOnLogin($guestCart, $customerCart);

    // Max strategy: max(1, 3) = 3
    expect($merged->lines)->toHaveCount(1)
        ->and($merged->lines->first()->quantity)->toBe(3)
        ->and($guestCart->fresh()->status)->toBe(CartStatus::Abandoned);
});

it('logs out customer and redirects to login', function () {
    $ctx = createStoreContext('customer-store.test');
    $customer = Customer::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'email' => 'test@example.com',
        'name' => 'Test',
        'password_hash' => Hash::make('password'),
    ]);

    $response = $this->actingAs($customer, 'customer')
        ->post('http://customer-store.test/account/logout');

    $response->assertRedirect('/account/login');
    $this->assertGuest('customer');
});
