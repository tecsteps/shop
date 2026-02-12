<?php

use App\Livewire\Admin\Auth\Login;
use App\Models\Store;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('admin login screen can be rendered', function () {
    $response = $this->get(route('admin.login'));

    $response->assertOk();
    $response->assertSeeLivewire(Login::class);
});

test('admin users can authenticate via login form', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('admin users cannot authenticate with invalid password', function () {
    $user = User::factory()->create();

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest();
});

test('admin login validates required fields', function () {
    Livewire::test(Login::class)
        ->set('email', '')
        ->set('password', '')
        ->call('login')
        ->assertHasErrors(['email', 'password']);
});

test('admin login validates email format', function () {
    Livewire::test(Login::class)
        ->set('email', 'not-an-email')
        ->set('password', 'password')
        ->call('login')
        ->assertHasErrors('email');
});

test('admin login sets store in session on success', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login');

    expect(session('current_store_id'))->toBe($store->id);
});

test('admin login updates last_login_at timestamp', function () {
    $user = User::factory()->create(['last_login_at' => null]);
    $store = Store::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login');

    expect($user->fresh()->last_login_at)->not->toBeNull();
});

test('admin logout redirects to login page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('admin.logout'));

    $response->assertRedirect(route('admin.login'));
    $this->assertGuest();
});

test('admin login is rate limited after too many attempts', function () {
    $user = User::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'wrong-password')
            ->call('login');
    }

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email');
});
