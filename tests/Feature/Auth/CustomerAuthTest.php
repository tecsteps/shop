<?php

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Customer;
use App\Models\CustomerPasswordResetToken;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Notifications\CustomerResetPasswordNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('renders the customer login page', function () {
    ['domain' => $domain] = $this->createStoreContext();

    $response = $this->get(storefrontUrl($domain->hostname, '/account/login'));

    $response->assertOk();
    $response->assertSee('Login');
});

it('authenticates a customer with valid credentials', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $store->id]);

    $response = $this->post(storefrontUrl($domain->hostname, '/account/login'), [
        'email' => $customer->email,
        'password' => 'password',
    ]);

    $response->assertRedirect('/account');
    $this->assertAuthenticatedAs($customer, 'customer');
});

it('rejects invalid customer credentials', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $store->id]);

    $response = $this->post(storefrontUrl($domain->hostname, '/account/login'), [
        'email' => $customer->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest('customer');
});

it('scopes customer login to the current store', function () {
    ['store' => $storeA] = $this->createStoreContext();
    ['domain' => $domainB] = $this->createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $storeA->id]);

    $response = $this->post(storefrontUrl($domainB->hostname, '/account/login'), [
        'email' => $customer->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest('customer');
});

it('rate limits customer login attempts', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $store->id]);

    for ($i = 0; $i < 5; $i++) {
        $this->post(storefrontUrl($domain->hostname, '/account/login'), [
            'email' => $customer->email,
            'password' => 'wrong-password',
        ]);
    }

    $this->post(storefrontUrl($domain->hostname, '/account/login'), [
        'email' => $customer->email,
        'password' => 'wrong-password',
    ])->assertStatus(429);
});

it('registers a new customer', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();

    $response = $this->post(storefrontUrl($domain->hostname, '/account/register'), [
        'name' => 'Sam Shopper',
        'email' => 'sam@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
        'marketing_opt_in' => true,
    ]);

    $response->assertRedirect('/account');
    $customer = Customer::query()->where('email', 'sam@example.com')->first();
    expect($customer)->not->toBeNull();
    expect($customer->store_id)->toBe($store->id);
    expect($customer->marketing_opt_in)->toBeTrue();
    $this->assertAuthenticatedAs($customer, 'customer');
});

it('rejects duplicate email registration in the same store', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    Customer::factory()->create(['store_id' => $store->id, 'email' => 'sam@example.com']);

    $response = $this->post(storefrontUrl($domain->hostname, '/account/register'), [
        'name' => 'Sam Shopper',
        'email' => 'sam@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest('customer');
});

it('allows same email in different stores', function () {
    ['store' => $storeA] = $this->createStoreContext();
    ['store' => $storeB, 'domain' => $domainB] = $this->createStoreContext();
    Customer::factory()->create(['store_id' => $storeA->id, 'email' => 'sam@example.com']);

    $response = $this->post(storefrontUrl($domainB->hostname, '/account/register'), [
        'name' => 'Sam Shopper',
        'email' => 'sam@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ]);

    $response->assertRedirect('/account');
    expect(Customer::query()->where('email', 'sam@example.com')->count())->toBe(1);
    expect(Customer::withoutGlobalScopes()->where('email', 'sam@example.com')->count())->toBe(2);
    expect(Customer::query()->first()->store_id)->toBe($storeB->id);
});

it('validates registration input', function () {
    ['domain' => $domain] = $this->createStoreContext();

    $response = $this->post(storefrontUrl($domain->hostname, '/account/register'), [
        'name' => '',
        'email' => 'not-an-email',
        'password' => 'short',
        'password_confirmation' => 'different',
    ]);

    $response->assertSessionHasErrors(['name', 'email', 'password']);
    $this->assertGuest('customer');
});

it('logs out customer and redirects to login', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $store->id]);

    $response = $this->actingAsCustomer($customer)
        ->post(storefrontUrl($domain->hostname, '/account/logout'));

    $response->assertRedirect('/account/login');
    $this->assertGuest('customer');
});

it('does not treat admin session as customer authentication', function () {
    ['store' => $store, 'domain' => $domain, 'user' => $user] = $this->createStoreContext();

    $response = $this->actingAsAdmin($user, $store)->get(storefrontUrl($domain->hostname, '/account'));

    $response->assertRedirect(storefrontUrl($domain->hostname, '/account/login'));
});

it('merges guest cart into customer cart on login', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $store->id]);
    $product = Product::factory()->create(['store_id' => $store->id]);
    $variantA = ProductVariant::factory()->create(['product_id' => $product->id]);
    $variantB = ProductVariant::factory()->create(['product_id' => $product->id]);

    $guestCart = Cart::factory()->create([
        'store_id' => $store->id, 'customer_id' => null, 'status' => CartStatus::Active,
    ]);
    CartLine::factory()->create([
        'cart_id' => $guestCart->id, 'variant_id' => $variantA->id, 'quantity' => 2,
        'unit_price_amount' => 1000, 'line_subtotal_amount' => 2000,
        'line_discount_amount' => 0, 'line_total_amount' => 2000,
    ]);

    $customerCart = Cart::factory()->create([
        'store_id' => $store->id, 'customer_id' => $customer->id, 'status' => CartStatus::Active,
    ]);
    CartLine::factory()->create([
        'cart_id' => $customerCart->id, 'variant_id' => $variantA->id, 'quantity' => 1,
        'unit_price_amount' => 1000, 'line_subtotal_amount' => 1000,
        'line_discount_amount' => 0, 'line_total_amount' => 1000,
    ]);
    CartLine::factory()->create([
        'cart_id' => $customerCart->id, 'variant_id' => $variantB->id, 'quantity' => 3,
        'unit_price_amount' => 500, 'line_subtotal_amount' => 1500,
        'line_discount_amount' => 0, 'line_total_amount' => 1500,
    ]);

    $this->withSession(['cart_id' => $guestCart->id])
        ->post(storefrontUrl($domain->hostname, '/account/login'), [
            'email' => $customer->email,
            'password' => 'password',
        ])
        ->assertRedirect('/account');

    expect($guestCart->fresh()->status)->toBe(CartStatus::Abandoned);
    expect($customerCart->cartLines()->where('variant_id', $variantA->id)->first()->quantity)->toBe(2);
    expect($customerCart->cartLines()->where('variant_id', $variantB->id)->first()->quantity)->toBe(3);
});

it('sends a password reset link with a generic response', function () {
    Notification::fake();
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $store->id]);

    $response = $this->post(storefrontUrl($domain->hostname, '/account/forgot-password'), [
        'email' => $customer->email,
    ]);

    $response->assertSessionHas('status', 'If that email exists, we sent a reset link.');
    Notification::assertSentTo($customer, CustomerResetPasswordNotification::class);
});

it('returns the same response for unknown reset emails', function () {
    Notification::fake();
    ['domain' => $domain] = $this->createStoreContext();

    $response = $this->post(storefrontUrl($domain->hostname, '/account/forgot-password'), [
        'email' => 'nobody@example.com',
    ]);

    $response->assertSessionHas('status', 'If that email exists, we sent a reset link.');
    Notification::assertNothingSent();
});

it('resets the password with a valid token', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $store->id]);
    CustomerPasswordResetToken::query()->create([
        'store_id' => $store->id,
        'email' => $customer->email,
        'token' => Hash::make('plain-token'),
        'created_at' => now(),
    ]);

    $response = $this->post(storefrontUrl($domain->hostname, '/account/reset-password'), [
        'token' => 'plain-token',
        'email' => $customer->email,
        'password' => 'new-secret-password',
        'password_confirmation' => 'new-secret-password',
    ]);

    $response->assertRedirect('/account/login');

    $this->post(storefrontUrl($domain->hostname, '/account/login'), [
        'email' => $customer->email,
        'password' => 'new-secret-password',
    ])->assertRedirect('/account');
});

it('rejects expired reset tokens', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $store->id]);
    CustomerPasswordResetToken::query()->create([
        'store_id' => $store->id,
        'email' => $customer->email,
        'token' => Hash::make('plain-token'),
        'created_at' => now()->subHours(2),
    ]);

    $response = $this->post(storefrontUrl($domain->hostname, '/account/reset-password'), [
        'token' => 'plain-token',
        'email' => $customer->email,
        'password' => 'new-secret-password',
        'password_confirmation' => 'new-secret-password',
    ]);

    $response->assertSessionHasErrors('email');
});
