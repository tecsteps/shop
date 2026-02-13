<?php

use App\Livewire\Storefront\Account\Auth\Login;
use App\Livewire\Storefront\Account\Auth\Register;
use Livewire\Livewire;

it('shows customer login page', function () {
    $this->get('/account/login')
        ->assertOk()
        ->assertSeeLivewire(Login::class);
});

it('shows customer register page', function () {
    $this->get('/account/register')
        ->assertOk()
        ->assertSeeLivewire(Register::class);
});

it('validates login email is required', function () {
    Livewire::test(Login::class)
        ->set('email', '')
        ->set('password', 'password')
        ->call('login')
        ->assertHasErrors(['email' => 'required']);
});

it('validates login password is required', function () {
    Livewire::test(Login::class)
        ->set('email', 'customer@example.com')
        ->set('password', '')
        ->call('login')
        ->assertHasErrors(['password' => 'required']);
});

it('validates registration fields are required', function () {
    Livewire::test(Register::class)
        ->set('first_name', '')
        ->set('last_name', '')
        ->set('email', '')
        ->set('password', '')
        ->set('password_confirmation', '')
        ->call('register')
        ->assertHasErrors(['first_name', 'last_name', 'email', 'password']);
});

it('validates registration password confirmation', function () {
    Livewire::test(Register::class)
        ->set('first_name', 'John')
        ->set('last_name', 'Doe')
        ->set('email', 'john@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'different')
        ->call('register')
        ->assertHasErrors(['password']);
});
