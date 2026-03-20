<?php

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\Theme;
use App\Models\ThemeSettings;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

beforeEach(function () {
    $this->store = Store::factory()->create(['name' => 'Test Store']);
    StoreDomain::factory()->create([
        'store_id' => $this->store->id,
        'hostname' => 'test-store.test',
        'type' => 'storefront',
    ]);
    $theme = Theme::factory()->published()->create(['store_id' => $this->store->id]);
    ThemeSettings::factory()->create(['theme_id' => $theme->id]);
    app()->instance('current_store', $this->store);

    $this->customer = Customer::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'email' => 'customer@test.com',
        'password' => bcrypt('password'),
        'name' => 'John Doe',
    ]);
});

it('lists saved addresses', function () {
    Auth::guard('customer')->login($this->customer);

    $addr1 = CustomerAddress::factory()->create([
        'customer_id' => $this->customer->id,
        'address_json' => ['first_name' => 'John', 'last_name' => 'Doe', 'address1' => '123 Main St', 'city' => 'New York', 'zip' => '10001', 'country' => 'US'],
    ]);
    $addr2 = CustomerAddress::factory()->create([
        'customer_id' => $this->customer->id,
        'address_json' => ['first_name' => 'John', 'last_name' => 'Doe', 'address1' => '456 Oak Ave', 'city' => 'Chicago', 'zip' => '60601', 'country' => 'US'],
    ]);

    $response = $this->get('https://test-store.test/account/addresses');

    $response->assertOk();
    $response->assertSee('123 Main St');
    $response->assertSee('456 Oak Ave');
});

it('creates a new address', function () {
    Auth::guard('customer')->login($this->customer);

    Livewire::test(\App\Livewire\Storefront\Account\Addresses\Index::class)
        ->set('form.first_name', 'John')
        ->set('form.last_name', 'Doe')
        ->set('form.address1', 'New Street 42')
        ->set('form.city', 'Hamburg')
        ->set('form.zip', '20095')
        ->set('form.country', 'DE')
        ->call('saveAddress')
        ->assertHasNoErrors();

    expect(CustomerAddress::where('customer_id', $this->customer->id)->count())->toBe(1);
    $address = CustomerAddress::where('customer_id', $this->customer->id)->first();
    expect($address->address_json['address1'])->toBe('New Street 42');
    expect($address->address_json['city'])->toBe('Hamburg');
});

it('updates an existing address', function () {
    Auth::guard('customer')->login($this->customer);

    $address = CustomerAddress::factory()->create([
        'customer_id' => $this->customer->id,
        'address_json' => ['first_name' => 'John', 'last_name' => 'Doe', 'address1' => '123 Main St', 'city' => 'New York', 'zip' => '10001', 'country' => 'US'],
    ]);

    Livewire::test(\App\Livewire\Storefront\Account\Addresses\Index::class)
        ->call('editAddress', $address->id)
        ->set('form.city', 'Frankfurt')
        ->call('saveAddress')
        ->assertHasNoErrors();

    $address->refresh();
    expect($address->address_json['city'])->toBe('Frankfurt');
});

it('deletes an address', function () {
    Auth::guard('customer')->login($this->customer);

    $address = CustomerAddress::factory()->create([
        'customer_id' => $this->customer->id,
    ]);

    Livewire::test(\App\Livewire\Storefront\Account\Addresses\Index::class)
        ->call('deleteAddress', $address->id);

    expect(CustomerAddress::find($address->id))->toBeNull();
});

it('sets a default address', function () {
    Auth::guard('customer')->login($this->customer);

    $addr1 = CustomerAddress::factory()->default()->create([
        'customer_id' => $this->customer->id,
    ]);
    $addr2 = CustomerAddress::factory()->create([
        'customer_id' => $this->customer->id,
    ]);

    Livewire::test(\App\Livewire\Storefront\Account\Addresses\Index::class)
        ->call('setDefault', $addr2->id);

    $addr1->refresh();
    $addr2->refresh();
    expect($addr2->is_default)->toBeTrue();
    expect($addr1->is_default)->toBeFalse();
});

it('validates required address fields', function () {
    Auth::guard('customer')->login($this->customer);

    Livewire::test(\App\Livewire\Storefront\Account\Addresses\Index::class)
        ->set('form.first_name', '')
        ->set('form.last_name', '')
        ->set('form.address1', '')
        ->set('form.city', '')
        ->set('form.zip', '')
        ->set('form.country', '')
        ->call('saveAddress')
        ->assertHasErrors(['form.first_name', 'form.last_name', 'form.address1', 'form.city', 'form.zip', 'form.country']);
});

it('prevents managing another customers addresses', function () {
    Auth::guard('customer')->login($this->customer);

    $otherCustomer = Customer::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'email' => 'other@test.com',
        'password' => bcrypt('password'),
        'name' => 'Other Customer',
    ]);

    $otherAddress = CustomerAddress::factory()->create([
        'customer_id' => $otherCustomer->id,
    ]);

    Livewire::test(\App\Livewire\Storefront\Account\Addresses\Index::class)
        ->call('editAddress', $otherAddress->id)
        ->assertForbidden();
});
