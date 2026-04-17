<?php

use App\Enums\StoreDomainType;
use App\Livewire\Storefront\Account\Auth\ForgotPassword;
use App\Livewire\Storefront\Account\Auth\ResetPassword;
use App\Models\Customer;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Notifications\CustomerResetPasswordNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('runs the full password reset flow', function () {
    Notification::fake();

    $store = Store::factory()->create(['name' => 'Shop']);
    StoreDomain::factory()->create([
        'store_id' => $store->getKey(),
        'hostname' => 'shop.test',
        'type' => StoreDomainType::Storefront->value,
        'is_primary' => 1,
    ]);
    $customer = Customer::factory()->create([
        'store_id' => $store->getKey(),
        'email' => 'reset@example.com',
        'password_hash' => Hash::make('oldsecret1'),
    ]);

    $this->get('http://shop.test/account/forgot-password');

    Livewire::test(ForgotPassword::class)
        ->set('email', 'reset@example.com')
        ->call('sendLink');

    $token = null;
    Notification::assertSentTo($customer, CustomerResetPasswordNotification::class, function (CustomerResetPasswordNotification $n) use (&$token) {
        $token = $n->token;

        return true;
    });

    expect($token)->not->toBeNull();

    $this->get('http://shop.test/account/reset-password/'.$token.'?email=reset@example.com');

    Livewire::test(ResetPassword::class, ['token' => $token])
        ->set('email', 'reset@example.com')
        ->set('password', 'newsecret1')
        ->set('password_confirmation', 'newsecret1')
        ->call('resetPassword');

    $customer->refresh();
    expect(Hash::check('newsecret1', $customer->password_hash))->toBeTrue();
});
