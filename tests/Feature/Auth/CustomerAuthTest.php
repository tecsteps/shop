<?php

use App\Livewire\Storefront\Account\Auth\Login;
use App\Livewire\Storefront\Account\Auth\Register;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext(['hostname' => 'acme-fashion.test']);
});

it('renders the customer login page within the storefront layout', function () {
    $this->get(storefrontUrl('acme-fashion.test', '/account/login'))
        ->assertOk()
        ->assertSee('Log in to your account')
        // Storefront-branded layout chrome (skip link + footer), not the bare auth layout.
        ->assertSee('Skip to main content')
        ->assertSee('All rights reserved.');
});

it('renders the customer register page within the storefront layout', function () {
    $this->get(storefrontUrl('acme-fashion.test', '/account/register'))
        ->assertOk()
        ->assertSee('Create an account')
        ->assertSee('Skip to main content')
        ->assertSee('All rights reserved.');
});

it('authenticates a customer with valid credentials', function () {
    $customer = Customer::factory()->create([
        'store_id' => $this->context['store']->id,
        'password_hash' => Hash::make('secret-password'),
    ]);

    Livewire::test(Login::class)
        ->set('email', $customer->email)
        ->set('password', 'secret-password')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('account.dashboard'));

    expect(auth('customer')->check())->toBeTrue();
});

it('rejects invalid customer credentials', function () {
    $customer = Customer::factory()->create([
        'store_id' => $this->context['store']->id,
        'password_hash' => Hash::make('secret-password'),
    ]);

    Livewire::test(Login::class)
        ->set('email', $customer->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email');

    expect(auth('customer')->check())->toBeFalse();
});

it('scopes customer login to the current store', function () {
    $storeA = createStoreContext(['hostname' => 'store-a.test', 'bind' => false]);

    $customer = Customer::factory()->create([
        'store_id' => $storeA['store']->id,
        'password_hash' => Hash::make('secret-password'),
    ]);

    // Current store (from beforeEach) is the acme store, NOT store A.
    Livewire::test(Login::class)
        ->set('email', $customer->email)
        ->set('password', 'secret-password')
        ->call('login')
        ->assertHasErrors('email');

    expect(auth('customer')->check())->toBeFalse();
});

it('rate limits customer login attempts', function () {
    $customer = Customer::factory()->create([
        'store_id' => $this->context['store']->id,
        'password_hash' => Hash::make('secret-password'),
    ]);

    foreach (range(1, 5) as $attempt) {
        Livewire::test(Login::class)
            ->set('email', $customer->email)
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors('email');
    }

    Livewire::test(Login::class)
        ->set('email', $customer->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertSee('Too many attempts', false);
});

it('registers a new customer', function () {
    Livewire::test(Register::class)
        ->set('name', 'New Customer')
        ->set('email', 'new@example.test')
        ->set('password', 'secret-password')
        ->set('password_confirmation', 'secret-password')
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect(route('account.dashboard'));

    $this->assertDatabaseHas('customers', [
        'store_id' => $this->context['store']->id,
        'email' => 'new@example.test',
    ]);
    expect(auth('customer')->check())->toBeTrue();
});

it('rejects duplicate email registration in the same store', function () {
    Customer::factory()->create([
        'store_id' => $this->context['store']->id,
        'email' => 'dupe@example.test',
    ]);

    Livewire::test(Register::class)
        ->set('name', 'Dupe')
        ->set('email', 'dupe@example.test')
        ->set('password', 'secret-password')
        ->set('password_confirmation', 'secret-password')
        ->call('register')
        ->assertHasErrors('email');
});

it('allows same email in different stores', function () {
    $storeA = createStoreContext(['hostname' => 'store-a.test', 'bind' => false]);
    Customer::factory()->create([
        'store_id' => $storeA['store']->id,
        'email' => 'shared@example.test',
    ]);

    // Current store is still the acme store from beforeEach.
    Livewire::test(Register::class)
        ->set('name', 'Shared')
        ->set('email', 'shared@example.test')
        ->set('password', 'secret-password')
        ->set('password_confirmation', 'secret-password')
        ->call('register')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('customers', [
        'store_id' => $this->context['store']->id,
        'email' => 'shared@example.test',
    ]);
});

it('logs out customer and redirects to login', function () {
    $customer = Customer::factory()->create(['store_id' => $this->context['store']->id]);
    actingAsCustomer($customer);

    $this->post(storefrontUrl('acme-fashion.test', '/account/logout'))
        ->assertRedirect(route('account.login'));

    expect(auth('customer')->check())->toBeFalse();
});

it('merges guest cart into customer cart on login', function () {
    $store = $this->context['store'];
    bindCurrentStore($store);

    $customer = Customer::factory()->create([
        'store_id' => $store->id,
        'password_hash' => Hash::make('secret-password'),
    ]);

    $carts = app(App\Services\CartService::class);

    $variantA = App\Models\ProductVariant::factory()->create([
        'product_id' => App\Models\Product::factory()->create(['store_id' => $store->id, 'status' => 'active']),
        'price_amount' => 1000,
    ]);
    $variantA->inventoryItem->update(['quantity_on_hand' => 100, 'policy' => 'continue']);
    $variantB = App\Models\ProductVariant::factory()->create([
        'product_id' => App\Models\Product::factory()->create(['store_id' => $store->id, 'status' => 'active']),
        'price_amount' => 2000,
    ]);
    $variantB->inventoryItem->update(['quantity_on_hand' => 100, 'policy' => 'continue']);

    // Guest cart: variant A qty 2.
    $guestCart = $carts->create($store);
    $carts->addLine($guestCart, $variantA->id, 2);

    // Customer cart: variant A qty 1, variant B qty 3.
    $customerCart = $carts->create($store, $customer);
    $carts->addLine($customerCart, $variantA->id, 1);
    $carts->addLine($customerCart->fresh(), $variantB->id, 3);

    // The guest cart is the active session cart at login time.
    session()->put(App\Services\CartService::SESSION_KEY, $guestCart->id);

    Livewire::test(Login::class)
        ->set('email', $customer->email)
        ->set('password', 'secret-password')
        ->call('login')
        ->assertHasNoErrors();

    $merged = $customerCart->fresh('lines');
    $lineA = $merged->lines->firstWhere('variant_id', $variantA->id);
    $lineB = $merged->lines->firstWhere('variant_id', $variantB->id);

    expect($lineA->quantity)->toBe(2) // max(1, 2)
        ->and($lineB->quantity)->toBe(3)
        ->and($guestCart->fresh()->status)->toBe(App\Enums\CartStatus::Abandoned);
});
