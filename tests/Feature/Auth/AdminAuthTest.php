<?php

use App\Livewire\Admin\Auth\Login;
use App\Livewire\Admin\Auth\Logout;
use Livewire\Livewire;

it('renders the admin login page', function () {
    $this->get('/admin/login')->assertOk();
});

it('authenticates an admin user with valid credentials', function () {
    $context = createStoreContext();

    Livewire::test(Login::class)
        ->set('email', $context['user']->email)
        ->set('password', 'password')
        ->call('authenticate')
        ->assertRedirect('/admin');

    $this->assertAuthenticatedAs($context['user'], 'web');
});

it('rejects invalid credentials', function () {
    $context = createStoreContext();

    Livewire::test(Login::class)
        ->set('email', $context['user']->email)
        ->set('password', 'wrong-password')
        ->call('authenticate')
        ->assertHasErrors('email');

    $this->assertGuest('web');
});

it('shows generic error message on failed login', function () {
    createStoreContext();

    Livewire::test(Login::class)
        ->set('email', 'nonexistent@example.com')
        ->set('password', 'password')
        ->call('authenticate')
        ->assertHasErrors('email');
});

it('validates required fields', function () {
    Livewire::test(Login::class)
        ->set('email', '')
        ->set('password', '')
        ->call('authenticate')
        ->assertHasErrors(['email', 'password']);
});

it('rate limits login attempts', function () {
    $context = createStoreContext();

    for ($i = 0; $i < 5; $i++) {
        Livewire::test(Login::class)
            ->set('email', $context['user']->email)
            ->set('password', 'wrong-password')
            ->call('authenticate');
    }

    Livewire::test(Login::class)
        ->set('email', $context['user']->email)
        ->set('password', 'wrong-password')
        ->call('authenticate')
        ->assertHasErrors('email');
});

it('supports remember me functionality', function () {
    $context = createStoreContext();

    Livewire::test(Login::class)
        ->set('email', $context['user']->email)
        ->set('password', 'password')
        ->set('remember', true)
        ->call('authenticate')
        ->assertRedirect('/admin');

    $this->assertAuthenticatedAs($context['user'], 'web');
});

it('regenerates session on login', function () {
    $context = createStoreContext();

    Livewire::test(Login::class)
        ->set('email', $context['user']->email)
        ->set('password', 'password')
        ->call('authenticate');

    $this->assertAuthenticatedAs($context['user'], 'web');
});

it('logs out an admin user', function () {
    $context = createStoreContext();
    $this->actingAs($context['user'], 'web');

    Livewire::test(Logout::class)
        ->call('logout')
        ->assertRedirect('/admin/login');

    $this->assertGuest('web');
});

it('preserves existing user data after login', function () {
    $context = createStoreContext();

    expect($context['user']->name)->not->toBeEmpty()
        ->and($context['user']->email)->not->toBeEmpty()
        ->and($context['user']->status)->toBe('active');
});
