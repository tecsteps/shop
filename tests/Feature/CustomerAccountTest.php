<?php

use App\Livewire\Storefront\Account\Addresses\Index as AddressIndex;
use App\Livewire\Storefront\Account\Auth\Login;
use App\Livewire\Storefront\Account\Auth\Register;
use App\Models\Cart;
use App\Models\Customer;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

it('registers a store-scoped customer and starts an authenticated session', function () {
    $store = shopStore();
    app()->instance('current_store', $store);

    Livewire::test(Register::class)
        ->set('name', 'Morgan Customer')
        ->set('email', 'morgan@example.test')
        ->set('password', 'secure-password-123')
        ->set('password_confirmation', 'secure-password-123')
        ->set('marketingOptIn', true)
        ->call('register')
        ->assertRedirect(route('storefront.account.dashboard'));

    $customer = Customer::withoutGlobalScopes()->where('email', 'morgan@example.test')->firstOrFail();
    $this->assertAuthenticatedAs($customer, 'customer');
    expect($customer->store_id)->toBe($store->id)
        ->and($customer->marketing_opt_in)->toBeTrue()
        ->and(Hash::check('secure-password-123', $customer->password))->toBeTrue();
});

it('resolves the store on customer login and registration pages', function () {
    shopStore('shop.test');

    $this->get('http://shop.test/account/login')->assertOk();
    $this->get('http://shop.test/account/register')->assertOk();
    $this->get('http://unknown-shop.test/account/login')->assertNotFound();
});

it('allows the same customer email in separate stores and keeps login tenant scoped', function () {
    $firstStore = shopStore();
    $firstCustomer = Customer::factory()->create([
        'store_id' => $firstStore->id,
        'email' => 'shared@example.test',
        'password' => 'first-password',
    ]);
    $secondStore = shopStore('second.test');
    $secondCustomer = Customer::factory()->create([
        'store_id' => $secondStore->id,
        'email' => 'shared@example.test',
        'password' => 'second-password',
    ]);
    expect($firstCustomer->id)->not->toBe($secondCustomer->id);

    app()->instance('current_store', $firstStore);
    Livewire::test(Login::class)
        ->set('email', 'shared@example.test')
        ->set('password', 'second-password')
        ->call('login')
        ->assertHasErrors('email');

    app('auth')->guard('customer')->logout();
    app()->instance('current_store', $secondStore);
    Livewire::test(Login::class)
        ->set('email', 'shared@example.test')
        ->set('password', 'second-password')
        ->call('login')
        ->assertRedirect(route('storefront.account.dashboard'));

    $this->assertAuthenticatedAs($secondCustomer, 'customer');
});

it('merges guest cart lines on login and prefers the higher duplicate quantity', function () {
    $store = shopStore();
    $product = shopProduct($store);
    $customer = Customer::factory()->create(['store_id' => $store->id, 'email' => 'buyer@example.test', 'password' => 'shop-password']);
    $guestCart = Cart::factory()->create(['store_id' => $store->id]);
    $guestCart->lines()->create([
        'variant_id' => $product['variant']->id, 'quantity' => 3, 'unit_price_amount' => 2500,
        'line_subtotal_amount' => 7500, 'line_discount_amount' => 0, 'line_total_amount' => 7500,
    ]);
    $customerCart = Cart::factory()->create(['store_id' => $store->id, 'customer_id' => $customer->id]);
    $customerCart->lines()->create([
        'variant_id' => $product['variant']->id, 'quantity' => 2, 'unit_price_amount' => 2500,
        'line_subtotal_amount' => 5000, 'line_discount_amount' => 0, 'line_total_amount' => 5000,
    ]);
    session()->put('cart_id', $guestCart->id);
    app()->instance('current_store', $store);

    Livewire::test(Login::class)
        ->set('email', 'buyer@example.test')
        ->set('password', 'shop-password')
        ->call('login')
        ->assertRedirect(route('storefront.account.dashboard'));

    expect($customerCart->lines()->firstOrFail()->quantity)->toBe(3)
        ->and($guestCart->refresh()->status)->toBe('abandoned')
        ->and(session()->has('cart_id'))->toBeFalse();
});

it('saves addresses for the signed-in customer and prevents editing another customer address', function () {
    $store = shopStore();
    app()->instance('current_store', $store);
    $customer = Customer::factory()->create(['store_id' => $store->id]);
    $otherCustomer = Customer::factory()->create(['store_id' => $store->id]);
    $foreignAddress = $otherCustomer->addresses()->create([
        'label' => 'Home',
        'address_json' => ['first_name' => 'Other'],
        'is_default' => true,
    ]);

    Livewire::actingAs($customer, 'customer')->test(AddressIndex::class)
        ->set('address.first_name', 'Taylor')
        ->set('address.last_name', 'Buyer')
        ->set('address.address_line_1', '12 Example Road')
        ->set('address.city', 'Berlin')
        ->set('address.country', 'DE')
        ->set('address.postal_code', '10115')
        ->set('label', 'Home')
        ->set('isDefault', true)
        ->call('saveAddress')
        ->assertHasNoErrors();

    $savedAddress = $customer->addresses()->firstOrFail();
    expect($savedAddress->is_default)->toBeTrue()
        ->and($savedAddress->address_json['address1'])->toBe('12 Example Road');

    expect(fn () => Livewire::actingAs($customer, 'customer')->test(AddressIndex::class)
        ->call('editAddress', $foreignAddress->id))
        ->toThrow(ModelNotFoundException::class);
});

it('stores and validates customer reset tokens within the current store', function () {
    $firstStore = shopStore();
    $firstCustomer = Customer::factory()->create(['store_id' => $firstStore->id, 'email' => 'shared@example.test']);
    $secondStore = shopStore('second.test');
    $secondCustomer = Customer::factory()->create(['store_id' => $secondStore->id, 'email' => 'shared@example.test']);
    app()->instance('current_store', $firstStore);

    $token = \Illuminate\Support\Facades\Password::broker('customers')->createToken($firstCustomer);
    $this->assertDatabaseHas('customer_password_reset_tokens', [
        'store_id' => $firstStore->id,
        'email' => 'shared@example.test',
    ]);
    expect(\Illuminate\Support\Facades\Password::broker('customers')->tokenExists($firstCustomer, $token))->toBeTrue();

    app()->instance('current_store', $secondStore);
    expect(\Illuminate\Support\Facades\Password::broker('customers')->tokenExists($secondCustomer, $token))->toBeFalse();
});

it('shows the same password reset acknowledgement for known and unknown customer emails', function () {
    $store = shopStore();
    $customer = Customer::factory()->create(['store_id' => $store->id, 'email' => 'known@example.test']);
    app()->instance('current_store', $store);
    \Illuminate\Support\Facades\Notification::fake();

    Livewire::test(\App\Livewire\Storefront\Account\Auth\ForgotPassword::class)
        ->set('email', $customer->email)
        ->call('sendResetLink')
        ->assertSet('statusMessage', 'If an account with that email exists, we sent a password reset link.');
    Livewire::test(\App\Livewire\Storefront\Account\Auth\ForgotPassword::class)
        ->set('email', 'unknown@example.test')
        ->call('sendResetLink')
        ->assertSet('statusMessage', 'If an account with that email exists, we sent a password reset link.');

    \Illuminate\Support\Facades\Notification::assertSentTo($customer, \Illuminate\Auth\Notifications\ResetPassword::class);
    expect(\Illuminate\Support\Facades\DB::table('customer_password_reset_tokens')->count())->toBe(1);
});
