<?php

use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed();
    $this->withServerVariables(['HTTP_HOST' => 'shop.test']);
});

function addDefaultProductToCart($test): void
{
    $variant = Product::withoutGlobalScopes()
        ->where('handle', 'classic-cotton-t-shirt')
        ->firstOrFail()
        ->defaultVariant()
        ->firstOrFail();

    $test->post('/cart/lines', ['variant_id' => $variant->id, 'quantity' => 1]);
}

test('credit card checkout creates a paid order exactly once', function (): void {
    addDefaultProductToCart($this);

    $checkoutId = (int) basename($this->post('/cart/checkout')->headers->get('Location'));

    $payload = [
        'email' => 'buyer@example.com',
        'name' => 'Buyer Example',
        'address1' => 'Main Street 1',
        'city' => 'Berlin',
        'postal_code' => '10115',
        'country_code' => 'DE',
        'payment_method' => 'credit_card',
        'card_number' => '4242 4242 4242 4242',
    ];

    $this->post("/checkout/{$checkoutId}", $payload)
        ->assertRedirectContains('/checkout/confirmation/');

    $order = Order::query()->where('email', 'buyer@example.com')->firstOrFail();

    expect($order->financial_status)->toBe('paid')
        ->and($order->lines)->toHaveCount(1);

    $this->post("/checkout/{$checkoutId}", $payload)->assertRedirectContains('/checkout/confirmation/');

    expect(Order::query()->where('email', 'buyer@example.com')->count())->toBe(1);
});

test('mock payment provider exposes decline and bank transfer flows', function (): void {
    addDefaultProductToCart($this);
    $checkoutId = (int) basename($this->post('/cart/checkout')->headers->get('Location'));

    $base = [
        'email' => 'declined@example.com',
        'name' => 'Declined Buyer',
        'address1' => 'Main Street 1',
        'city' => 'Berlin',
        'postal_code' => '10115',
        'country_code' => 'DE',
        'payment_method' => 'credit_card',
    ];

    $this->post("/checkout/{$checkoutId}", $base + ['card_number' => '4000 0000 0000 0002'])
        ->assertSessionHasErrors('payment');

    addDefaultProductToCart($this);
    $checkoutId = (int) basename($this->post('/cart/checkout')->headers->get('Location'));

    $this->post("/checkout/{$checkoutId}", array_merge($base, [
        'email' => 'bank@example.com',
        'payment_method' => 'bank_transfer',
    ]))->assertRedirectContains('/checkout/confirmation/');

    $this->get('/checkout/confirmation/'.Order::query()->where('email', 'bank@example.com')->value('order_number'))
        ->assertOk()
        ->assertSee('Bank transfer instructions')
        ->assertSee('DE89370400440532013000');
});
