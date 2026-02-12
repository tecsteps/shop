<?php

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Store;

it('supports customer register login and account order pages', function () {
    $store = Store::factory()->create();

    $this->post('/account/register', [
        'name' => 'Jane Customer',
        'email' => 'jane.customer@gmail.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'marketing_opt_in' => true,
    ])->assertRedirect(route('storefront.account.dashboard'));

    $customer = Customer::query()->where('email', 'jane.customer@gmail.com')->firstOrFail();

    $this->assertAuthenticated('customer');

    $this->post('/account/logout')
        ->assertRedirect(route('storefront.account.login'));

    $this->post('/account/login', [
        'email' => 'jane.customer@gmail.com',
        'password' => 'password123',
    ])->assertRedirect(route('storefront.account.dashboard'));

    $order = Order::factory()
        ->for($store)
        ->for($customer)
        ->create([
            'order_number' => 'ORD-2001',
        ]);

    $this->get('/account')
        ->assertOk();

    $this->get('/account/orders')
        ->assertOk()
        ->assertViewHas('orders', fn ($orders): bool => $orders->getCollection()->contains('id', $order->id));

    $this->get("/account/orders/{$order->order_number}")
        ->assertOk()
        ->assertViewHas('order', fn (Order $resolved): bool => $resolved->id === $order->id);
});

it('lets authenticated customers create update default and delete addresses', function () {
    $store = Store::factory()->create();
    $customer = Customer::factory()->for($store)->create();

    $this->actingAs($customer, 'customer');

    $this->post('/account/addresses', [
        'label' => 'Home',
        'is_default' => true,
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'address1' => 'Main Street 1',
        'address2' => '',
        'city' => 'Berlin',
        'province' => '',
        'province_code' => '',
        'country' => 'Germany',
        'country_code' => 'DE',
        'zip' => '10115',
        'phone' => '+4930123456',
    ])->assertRedirect(route('storefront.account.addresses.index'));

    $firstAddress = CustomerAddress::query()->where('customer_id', $customer->id)->firstOrFail();

    $this->put("/account/addresses/{$firstAddress->id}", [
        'label' => 'Home Updated',
        'is_default' => true,
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'address1' => 'Main Street 2',
        'address2' => '',
        'city' => 'Hamburg',
        'province' => '',
        'province_code' => '',
        'country' => 'Germany',
        'country_code' => 'DE',
        'zip' => '20095',
        'phone' => '+4940123456',
    ])->assertRedirect(route('storefront.account.addresses.index'));

    $this->post('/account/addresses', [
        'label' => 'Office',
        'is_default' => false,
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'address1' => 'Office Street 1',
        'address2' => '',
        'city' => 'Munich',
        'province' => '',
        'province_code' => '',
        'country' => 'Germany',
        'country_code' => 'DE',
        'zip' => '80331',
        'phone' => '+4989123456',
    ])->assertRedirect(route('storefront.account.addresses.index'));

    $secondAddress = CustomerAddress::query()
        ->where('customer_id', $customer->id)
        ->where('label', 'Office')
        ->firstOrFail();

    $this->patch("/account/addresses/{$secondAddress->id}/default")
        ->assertRedirect(route('storefront.account.addresses.index'));

    expect($secondAddress->fresh()->is_default)->toBeTrue()
        ->and($firstAddress->fresh()->is_default)->toBeFalse();

    $this->delete("/account/addresses/{$secondAddress->id}")
        ->assertRedirect(route('storefront.account.addresses.index'));

    $this->assertDatabaseMissing('customer_addresses', [
        'id' => $secondAddress->id,
    ]);
});
