<?php

use App\Livewire\Admin\Auth\Login;
use App\Models\User;
use Livewire\Livewire;

it('shows admin login page', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSeeLivewire(Login::class);
});

it('authenticates admin user with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'admin@example.com',
        'password_hash' => 'password',
    ]);

    Livewire::test(Login::class)
        ->set('email', 'admin@example.com')
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($user);
});

it('fails authentication with invalid credentials', function () {
    User::factory()->create([
        'email' => 'admin@example.com',
        'password_hash' => 'password',
    ]);

    Livewire::test(Login::class)
        ->set('email', 'admin@example.com')
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest();
});

it('validates email is required', function () {
    Livewire::test(Login::class)
        ->set('email', '')
        ->set('password', 'password')
        ->call('login')
        ->assertHasErrors(['email' => 'required']);
});

it('validates password is required', function () {
    Livewire::test(Login::class)
        ->set('email', 'admin@example.com')
        ->set('password', '')
        ->call('login')
        ->assertHasErrors(['password' => 'required']);
});

it('rate limits login attempts', function () {
    User::factory()->create([
        'email' => 'admin@example.com',
        'password_hash' => 'password',
    ]);

    for ($i = 0; $i < 5; $i++) {
        Livewire::test(Login::class)
            ->set('email', 'admin@example.com')
            ->set('password', 'wrong-password')
            ->call('login');
    }

    Livewire::test(Login::class)
        ->set('email', 'admin@example.com')
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email');
});
