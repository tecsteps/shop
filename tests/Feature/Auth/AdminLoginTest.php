<?php

use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('admin login screen can be rendered', function () {
    $response = $this->get(route('admin.login'));

    $response->assertOk();
});

test('admin users can authenticate via livewire login', function () {
    $user = User::factory()->create();

    Livewire::test(\App\Livewire\Admin\Auth\Login::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('admin users cannot authenticate with invalid password', function () {
    $user = User::factory()->create();

    Livewire::test(\App\Livewire\Admin\Auth\Login::class)
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest();
});

test('admin login is rate limited after 5 attempts', function () {
    $user = User::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        Livewire::test(\App\Livewire\Admin\Auth\Login::class)
            ->set('email', $user->email)
            ->set('password', 'wrong-password')
            ->call('login');
    }

    Livewire::test(\App\Livewire\Admin\Auth\Login::class)
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email');
});

test('admin users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('admin.logout'));

    $response->assertRedirect(route('admin.login'));
    $this->assertGuest();
});

test('admin dashboard requires authentication and redirects to admin login', function () {
    $response = $this->get(route('admin.dashboard'));

    $response->assertRedirect(route('admin.login'));
});

test('admin login updates last_login_at', function () {
    $user = User::factory()->create(['last_login_at' => null]);

    Livewire::test(\App\Livewire\Admin\Auth\Login::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login');

    expect($user->fresh()->last_login_at)->not->toBeNull();
});
