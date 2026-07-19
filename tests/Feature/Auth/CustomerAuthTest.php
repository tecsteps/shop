<?php

use App\Enums\CartStatus;
use App\Livewire\Storefront\Account\Auth\ForgotPassword;
use App\Livewire\Storefront\Account\Auth\Login;
use App\Livewire\Storefront\Account\Auth\Register;
use App\Livewire\Storefront\Account\Auth\ResetPassword;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

test('renders the customer login page', function () {
    $store = $this->createStore();

    $this->get('http://'.$store->handle.'.test/account/login')
        ->assertOk()
        ->assertSee('Log in to your account');
});

test('renders the customer registration page', function () {
    $store = $this->createStore();

    $this->get('http://'.$store->handle.'.test/account/register')
        ->assertOk()
        ->assertSee('Create an account');
});

test('authenticates a customer with valid credentials', function () {
    $store = $this->createStore();
    $this->bindStore($store);

    $customer = Customer::factory()->create([
        'store_id' => $store->id,
        'password_hash' => Hash::make('secret123'),
    ]);

    Livewire::test(Login::class)
        ->set('email', $customer->email)
        ->set('password', 'secret123')
        ->call('login')
        ->assertRedirect('/account');

    $this->assertAuthenticatedAs($customer, 'customer');
});

test('rejects invalid customer credentials with a generic error', function () {
    $store = $this->createStore();
    $this->bindStore($store);

    $customer = Customer::factory()->create(['store_id' => $store->id]);

    Livewire::test(Login::class)
        ->set('email', $customer->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertSet('errorMessage', 'Invalid credentials.');

    $this->assertGuest('customer');
});

test('scopes customer login to the current store', function () {
    $storeA = $this->createStore();

    Customer::factory()->create([
        'store_id' => $storeA->id,
        'email' => 'jane@example.com',
        'password_hash' => Hash::make('secret123'),
    ]);

    $storeB = $this->createStore();
    $this->bindStore($storeB);

    Livewire::test(Login::class)
        ->set('email', 'jane@example.com')
        ->set('password', 'secret123')
        ->call('login')
        ->assertSet('errorMessage', 'Invalid credentials.');

    $this->assertGuest('customer');
});

test('rate limits customer login attempts', function () {
    $store = $this->createStore();
    $this->bindStore($store);

    $customer = Customer::factory()->create(['store_id' => $store->id]);

    for ($attempt = 0; $attempt < 5; $attempt++) {
        Livewire::test(Login::class)
            ->set('email', $customer->email)
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertSet('errorMessage', 'Invalid credentials.');
    }

    Livewire::test(Login::class)
        ->set('email', $customer->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertStatus(429);
});

test('registers a new customer and logs them in', function () {
    $store = $this->createStore();
    $this->bindStore($store);

    Livewire::test(Register::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->set('marketing_opt_in', true)
        ->call('register')
        ->assertRedirect('/account');

    $this->assertDatabaseHas('customers', [
        'store_id' => $store->id,
        'email' => 'jane@example.com',
        'name' => 'Jane Doe',
        'marketing_opt_in' => 1,
    ]);

    $this->assertAuthenticated('customer');
});

test('rejects duplicate email registration in the same store', function () {
    $store = $this->createStore();
    $this->bindStore($store);

    Customer::factory()->create([
        'store_id' => $store->id,
        'email' => 'jane@example.com',
    ]);

    Livewire::test(Register::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('register')
        ->assertHasErrors(['email']);

    $this->assertGuest('customer');
});

test('allows the same email in different stores', function () {
    $storeA = $this->createStore();

    Customer::factory()->create([
        'store_id' => $storeA->id,
        'email' => 'jane@example.com',
    ]);

    $storeB = $this->createStore();
    $this->bindStore($storeB);

    Livewire::test(Register::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('register')
        ->assertRedirect('/account');

    $this->assertDatabaseHas('customers', [
        'store_id' => $storeB->id,
        'email' => 'jane@example.com',
    ]);
});

test('logs out the customer and redirects to login', function () {
    $store = $this->createStore();
    $customer = Customer::factory()->create(['store_id' => $store->id]);

    $this->actingAs($customer, 'customer')
        ->post('http://'.$store->handle.'.test/account/logout')
        ->assertRedirect('http://'.$store->handle.'.test/account/login');

    $this->assertGuest('customer');
});

test('merges the guest cart into the customer cart on login', function () {
    $store = $this->createStore();
    $this->bindStore($store);

    $productA = Product::factory()->active()->create(['store_id' => $store->id]);
    $variantA = ProductVariant::factory()->withInventory(100)->create([
        'product_id' => $productA->id,
        'price_amount' => 1000,
    ]);
    $productB = Product::factory()->active()->create(['store_id' => $store->id]);
    $variantB = ProductVariant::factory()->withInventory(100)->create([
        'product_id' => $productB->id,
        'price_amount' => 2000,
    ]);

    $customer = Customer::factory()->create([
        'store_id' => $store->id,
        'password_hash' => Hash::make('secret123'),
    ]);

    $cartService = app(CartService::class);

    // Customer cart: variant A x1, variant B x3.
    $customerCart = $cartService->create($store, $customer);
    $cartService->addLine($customerCart, $variantA->id, 1);
    $cartService->addLine($customerCart, $variantB->id, 3);

    // Guest session cart: variant A x2.
    $guestCart = $cartService->create($store);
    $cartService->addLine($guestCart, $variantA->id, 2);
    session(['cart_id' => $guestCart->id]);

    Livewire::test(Login::class)
        ->set('email', $customer->email)
        ->set('password', 'secret123')
        ->call('login')
        ->assertRedirect('/account');

    $merged = $customerCart->refresh();

    expect($merged->findLineByVariant($variantA->id)->quantity)->toBe(3)
        ->and($merged->findLineByVariant($variantB->id)->quantity)->toBe(3)
        ->and($guestCart->refresh()->status)->toBe(CartStatus::Abandoned)
        ->and(session('cart_id'))->toBe($merged->id);
});

test('customer forgot password creates a store-scoped token', function () {
    Notification::fake();

    $store = $this->createStore();
    $this->bindStore($store);

    $customer = Customer::factory()->create(['store_id' => $store->id]);

    Livewire::test(ForgotPassword::class)
        ->set('email', $customer->email)
        ->call('sendResetLink')
        ->assertSet('linkSent', true);

    $this->assertDatabaseHas('customer_password_reset_tokens', [
        'store_id' => $store->id,
        'email' => $customer->email,
    ]);

    Notification::assertSentTo($customer, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($customer): bool {
        $mail = $notification->toMail($customer);

        return str_contains((string) $mail->actionUrl, '/reset-password/');
    });
});

test('customer forgot password response is generic for unknown emails', function () {
    Notification::fake();

    $store = $this->createStore();
    $this->bindStore($store);

    Livewire::test(ForgotPassword::class)
        ->set('email', 'nobody@example.com')
        ->call('sendResetLink')
        ->assertSet('linkSent', true);

    Notification::assertNothingSent();
});

test('customer reset password updates the password', function () {
    $store = $this->createStore();
    $this->bindStore($store);

    $customer = Customer::factory()->create(['store_id' => $store->id]);

    $token = Password::broker('customers')->createToken($customer);

    Livewire::test(ResetPassword::class, ['token' => $token])
        ->set('email', $customer->email)
        ->set('password', 'brand-new-password')
        ->set('password_confirmation', 'brand-new-password')
        ->call('resetPassword')
        ->assertRedirect(route('storefront.account.login'));

    expect(Auth::guard('customer')->attempt([
        'email' => $customer->email,
        'password' => 'brand-new-password',
    ]))->toBeTrue();
});

test('a reset token from another store is rejected', function () {
    $storeA = $this->createStore();
    $this->bindStore($storeA);

    $customer = Customer::factory()->create([
        'store_id' => $storeA->id,
        'password_hash' => Hash::make('secret123'),
    ]);

    $token = Password::broker('customers')->createToken($customer);

    $storeB = $this->createStore();
    $this->bindStore($storeB);

    Livewire::test(ResetPassword::class, ['token' => $token])
        ->set('email', $customer->email)
        ->set('password', 'brand-new-password')
        ->set('password_confirmation', 'brand-new-password')
        ->call('resetPassword')
        ->assertSet('errorMessage', 'This password reset link is invalid or has expired.');

    // The password is untouched and the token of store A still exists.
    expect(Hash::check('secret123', $customer->refresh()->password_hash))->toBeTrue();

    $this->assertDatabaseHas('customer_password_reset_tokens', [
        'store_id' => $storeA->id,
        'email' => $customer->email,
    ]);
});
