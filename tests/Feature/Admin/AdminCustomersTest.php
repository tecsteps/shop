<?php

use App\Livewire\Admin\Customers\Index;
use App\Livewire\Admin\Customers\Show;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('customers index requires authentication', function () {
    $this->get(route('admin.customers.index'))
        ->assertRedirect(route('admin.login'));
});

test('customers index displays customers list', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create(['name' => 'Jane Smith']);

    $this->actingAs($user)
        ->get(route('admin.customers.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

test('customers index can search by name', function () {
    $user = User::factory()->create();
    Customer::factory()->create(['name' => 'Jane Smith']);
    Customer::factory()->create(['name' => 'John Doe']);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('search', 'Jane')
        ->assertSee('Jane Smith')
        ->assertDontSee('John Doe');
});

test('customers index can search by email', function () {
    $user = User::factory()->create();
    Customer::factory()->create(['name' => 'Jane', 'email' => 'jane@example.com']);
    Customer::factory()->create(['name' => 'John', 'email' => 'john@example.com']);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('search', 'jane@example')
        ->assertSee('jane@example.com')
        ->assertDontSee('john@example.com');
});

test('customer show requires authentication', function () {
    $customer = Customer::factory()->create();

    $this->get(route('admin.customers.show', $customer))
        ->assertRedirect(route('admin.login'));
});

test('customer show displays customer details', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create([
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['customer' => $customer])
        ->assertSee('Jane Smith')
        ->assertSee('jane@example.com');
});

test('customer show displays order history', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();
    Order::factory()->create([
        'customer_id' => $customer->id,
        'order_number' => '5001',
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['customer' => $customer])
        ->assertSee('5001');
});

test('customer show displays addresses', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();
    CustomerAddress::factory()->create([
        'customer_id' => $customer->id,
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'address1' => '123 Main St',
        'city' => 'Springfield',
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['customer' => $customer])
        ->assertSee('123 Main St')
        ->assertSee('Springfield');
});
