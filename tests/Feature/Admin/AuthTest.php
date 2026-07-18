<?php

use App\Enums\StoreUserRole;
use App\Livewire\Admin\Auth\Login;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows the admin login page', function () {
    $this->get('/admin/login')->assertSuccessful()->assertSee('Admin sign in');
});

it('authenticates an admin and selects their first store', function () {
    $user = User::factory()->create(['email' => 'owner@example.com', 'password' => 'password']);
    $store = Store::factory()->create();
    $user->stores()->attach($store, ['role' => StoreUserRole::Owner]);

    Livewire::test(Login::class)
        ->set('email', 'owner@example.com')
        ->set('password', 'password')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirectToRoute('admin.dashboard');

    $this->assertAuthenticatedAs($user);
    expect(session('current_store_id'))->toBe($store->id);
});

it('rejects invalid admin credentials', function () {
    Livewire::test(Login::class)->set('email', 'missing@example.com')->set('password', 'wrong')->call('login')->assertHasErrors('email');
    $this->assertGuest();
});

it('logs an admin out', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create();
    $user->stores()->attach($store, ['role' => StoreUserRole::Owner]);

    $this->actingAs($user)
        ->withSession(['current_store_id' => $store->id])
        ->post(route('admin.logout'))
        ->assertRedirectToRoute('admin.login');

    $this->assertGuest();
});
