<?php

use App\Enums\PaymentMethod;
use App\Livewire\Storefront\Checkout\Confirmation;
use App\Models\Order;
use App\Models\OrderLine;
use Livewire\Livewire;

it('displays order confirmation for a valid order number', function () {
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $order = Order::factory()->for($store)->create([
        'order_number' => 'ORD-10001',
        'email' => 'customer@example.com',
        'payment_method' => PaymentMethod::CreditCard,
        'subtotal_amount' => 10000,
        'discount_amount' => 0,
        'shipping_amount' => 499,
        'tax_amount' => 1900,
        'total_amount' => 12399,
        'shipping_address_json' => [
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
            'address1' => 'Musterstrasse 1',
            'city' => 'Berlin',
            'postal_code' => '10115',
            'country_code' => 'DE',
        ],
    ]);

    OrderLine::factory()->create([
        'order_id' => $order->id,
        'title_snapshot' => 'Test Product',
        'quantity' => 2,
        'unit_price_amount' => 5000,
        'total_amount' => 10000,
    ]);

    Livewire::test(Confirmation::class, ['orderNumber' => 'ORD-10001'])
        ->assertSee('Thank you for your order!')
        ->assertSee('ORD-10001')
        ->assertSee('customer@example.com')
        ->assertSee('Test Product')
        ->assertSee('Credit Card');
});

it('returns 404 for non-existent order number', function () {
    createStoreContext();

    Livewire::test(Confirmation::class, ['orderNumber' => 'FAKE-99999'])
        ->assertStatus(404);
});

it('shows bank transfer details for bank transfer orders', function () {
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $order = Order::factory()->for($store)->create([
        'order_number' => 'ORD-20001',
        'email' => 'bank@example.com',
        'payment_method' => PaymentMethod::BankTransfer,
        'subtotal_amount' => 5000,
        'discount_amount' => 0,
        'shipping_amount' => 499,
        'tax_amount' => 950,
        'total_amount' => 6449,
        'shipping_address_json' => [
            'first_name' => 'Anna',
            'last_name' => 'Schmidt',
            'address1' => 'Berliner Str. 5',
            'city' => 'Munich',
            'postal_code' => '80331',
            'country_code' => 'DE',
        ],
    ]);

    Livewire::test(Confirmation::class, ['orderNumber' => 'ORD-20001'])
        ->assertSee('Bank Transfer Details')
        ->assertSee('Mock Bank AG')
        ->assertSee('DE89 3704 0044 0532 0130 00')
        ->assertSee('COBADEFFXXX')
        ->assertSee('ORD-20001');
});

it('displays order totals with discount', function () {
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $order = Order::factory()->for($store)->create([
        'order_number' => 'ORD-30001',
        'email' => 'discount@example.com',
        'payment_method' => PaymentMethod::CreditCard,
        'subtotal_amount' => 10000,
        'discount_amount' => 1000,
        'shipping_amount' => 499,
        'tax_amount' => 1710,
        'total_amount' => 11209,
        'shipping_address_json' => [
            'first_name' => 'Test',
            'last_name' => 'User',
            'address1' => 'Test St 1',
            'city' => 'Hamburg',
            'postal_code' => '20095',
            'country_code' => 'DE',
        ],
    ]);

    Livewire::test(Confirmation::class, ['orderNumber' => 'ORD-30001'])
        ->assertSee('Discount')
        ->assertSee('10.00');
});

it('displays shipping address on confirmation page', function () {
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $order = Order::factory()->for($store)->create([
        'order_number' => 'ORD-40001',
        'email' => 'address@example.com',
        'payment_method' => PaymentMethod::Paypal,
        'subtotal_amount' => 5000,
        'discount_amount' => 0,
        'shipping_amount' => 0,
        'tax_amount' => 0,
        'total_amount' => 5000,
        'shipping_address_json' => [
            'first_name' => 'Lisa',
            'last_name' => 'Weber',
            'address1' => 'Hauptstrasse 10',
            'address2' => 'Apt 3B',
            'city' => 'Frankfurt',
            'postal_code' => '60311',
            'country_code' => 'DE',
        ],
    ]);

    Livewire::test(Confirmation::class, ['orderNumber' => 'ORD-40001'])
        ->assertSee('Lisa')
        ->assertSee('Weber')
        ->assertSee('Hauptstrasse 10')
        ->assertSee('Apt 3B')
        ->assertSee('Frankfurt')
        ->assertSee('60311');
});
