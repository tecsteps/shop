<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed();
    $this->withServerVariables(['HTTP_HOST' => 'shop.test']);
});

test('customer can register login view orders and manage addresses', function (): void {
    $this->post('/account/register', [
        'name' => 'New Customer',
        'email' => 'new@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect('/account');

    $this->post('/account/logout')->assertRedirect('/account/login');

    $this->post('/account/login', ['email' => 'customer@acme.test', 'password' => 'password'])
        ->assertRedirect('/account');

    $this->get('/account')->assertOk()->assertSee('customer@acme.test');
    $this->get('/account/orders')->assertOk()->assertSee('#1001');
    $this->get('/account/orders/1001')->assertOk()->assertSee('Classic Cotton T-Shirt');
    $this->get('/account/addresses')->assertOk()->assertSee('Main Street 1');

    $this->post('/account/addresses', [
        'name' => 'John Doe',
        'address1' => 'Second Street 2',
        'city' => 'Berlin',
        'postal_code' => '10117',
        'country_code' => 'DE',
    ])->assertRedirect();

    $this->get('/account/addresses')->assertSee('Second Street 2');
});

test('customer registration validates duplicate store email and password confirmation', function (): void {
    $this->post('/account/register', [
        'name' => 'John Doe',
        'email' => 'customer@acme.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertSessionHasErrors('email');

    $this->post('/account/register', [
        'name' => 'John Doe',
        'email' => 'unique@example.com',
        'password' => 'password123',
        'password_confirmation' => 'different456',
    ])->assertSessionHasErrors('password');
});
