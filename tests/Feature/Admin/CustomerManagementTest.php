<?php

use App\Livewire\Admin\Customers\Index as CustomersIndex;
use App\Livewire\Admin\Customers\Show as CustomersShow;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

it('lists customers for the current store', function (): void {
    [$user, $store] = loginAsAdmin();

    Customer::factory()->create([
        'store_id' => $store->id,
        'email' => 'one@example.com',
    ]);

    Livewire::test(CustomersIndex::class)
        ->assertSee('one@example.com');
});

it('shows customer detail', function (): void {
    [$user, $store] = loginAsAdmin();

    $customer = Customer::factory()->create([
        'store_id' => $store->id,
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);

    Livewire::test(CustomersShow::class, ['customer' => $customer])
        ->assertSee('Jane Doe')
        ->assertSee('jane@example.com');
});
