<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

it('renders the admin login page', function (): void {
    $this->get('http://shop.test/admin/login')
        ->assertOk()
        ->assertSee('Admin Login');
})->skip('requires primary domain setup'); // baseline smoke replaced below

it('renders the admin login page on a real store host', function (): void {
    $this->createStoreContext(['hostname' => 'admin-auth.test']);
    // admin/login is not store-scoped - the storefront middleware group still runs, so use a host with a store
    $this->get('http://admin-auth.test/admin/login')->assertOk()->assertSee('Admin Login');
});

it('authenticates an admin user with valid credentials', function (): void {
    $ctx = $this->createStoreContext(['hostname' => 'admin-login.test']);
    $user = $ctx['owner'];
    $user->forceFill(['password' => Hash::make('pa55word!')])->save();

    $this->from('http://admin-login.test/admin/login');

    \Livewire\Livewire::test(\App\Livewire\Admin\Auth\Login::class)
        ->set('email', $user->email)
        ->set('password', 'pa55word!')
        ->call('login')
        ->assertRedirect(route('admin.dashboard'));

    expect(Auth::guard('web')->check())->toBeTrue();
});

it('rejects invalid credentials', function (): void {
    $ctx = $this->createStoreContext(['hostname' => 'admin-bad.test']);

    \Livewire\Livewire::test(\App\Livewire\Admin\Auth\Login::class)
        ->set('email', $ctx['owner']->email)
        ->set('password', 'wrong')
        ->call('login')
        ->assertHasErrors(['email']);

    expect(Auth::guard('web')->check())->toBeFalse();
});

it('logs the last_login_at timestamp', function (): void {
    $ctx = $this->createStoreContext(['hostname' => 'admin-ll.test']);
    $user = $ctx['owner'];
    $user->forceFill(['password' => Hash::make('letmein123')])->save();

    \Livewire\Livewire::test(\App\Livewire\Admin\Auth\Login::class)
        ->set('email', $user->email)
        ->set('password', 'letmein123')
        ->call('login');

    expect($user->fresh()->last_login_at)->not->toBeNull();
});
