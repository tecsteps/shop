<?php

use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

it('renders the admin login page', function () {
    $this->get('/admin/login')->assertStatus(200)->assertSee('Login');
});

it('authenticates an admin user with valid credentials', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create(['password_hash' => bcrypt('password')]);
    $user->stores()->attach($store->id, ['role' => 'owner']);

    $this->post('/admin/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('admin.dashboard'));

    expect(Auth::check())->toBeTrue();
});

it('rejects invalid credentials', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    $this->from('/admin/login')->post('/admin/login', ['email' => $user->email, 'password' => 'wrong'])
        ->assertRedirect('/admin/login')
        ->assertSessionHasErrors('email');
});

it('does not reveal whether email or password is incorrect', function () {
    $this->from('/admin/login')->post('/admin/login', ['email' => 'nobody@example.com', 'password' => 'wrong'])
        ->assertRedirect('/admin/login')
        ->assertSessionHasErrors('email', 'Invalid credentials.');
});

it('rate limits login attempts', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->post('/admin/login', ['email' => 'x@example.com', 'password' => 'wrong']);
    }

    $this->post('/admin/login', ['email' => 'x@example.com', 'password' => 'wrong'])->assertStatus(429);
});

it('logs out and invalidates session', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    bindCurrentStore($store);
    $this->actingAs($user);

    $this->post('/admin/logout')->assertRedirect(route('admin.login'));

    expect(Auth::check())->toBeFalse();
});

it('redirects unauthenticated users to login', function () {
    $this->get('/admin')->assertRedirect(route('admin.login'));
});

it('supports remember me functionality', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create(['password_hash' => bcrypt('password')]);
    $user->stores()->attach($store->id, ['role' => 'owner']);

    $response = $this->post('/admin/login', ['email' => $user->email, 'password' => 'password', 'remember' => '1']);

    $response->assertRedirect(route('admin.dashboard'));
    expect($response->headers->getCookies())->not->toBeEmpty();
});

it('records last_login_at on successful login', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create(['password_hash' => bcrypt('password')]);
    $user->stores()->attach($store->id, ['role' => 'owner']);

    $this->post('/admin/login', ['email' => $user->email, 'password' => 'password']);

    expect($user->fresh()->last_login_at)->not->toBeNull();
});
