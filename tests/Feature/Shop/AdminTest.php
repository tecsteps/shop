<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed();
});

test('admin can log in and navigate core admin pages', function (): void {
    $this->post('/admin/login', ['email' => 'admin@acme.test', 'password' => 'password'])
        ->assertRedirect(route('admin.dashboard', absolute: false));

    $this->get('/admin')->assertOk()->assertSee('Dashboard');
    $this->get('/admin/products')->assertOk()->assertSee('Classic Cotton T-Shirt');
    $this->get('/admin/orders')->assertOk()->assertSee('#1001');
    $this->get('/admin/customers')->assertOk()->assertSee('customer@acme.test');
    $this->get('/admin/discounts')->assertOk()->assertSee('WELCOME10');
    $this->get('/admin/settings')->assertOk()->assertSee('shop.test')->assertSee('Shipping zones');
});

test('admin can create edit and archive products', function (): void {
    $user = User::query()->where('email', 'admin@acme.test')->firstOrFail();

    $this->actingAs($user)
        ->withSession(['current_store_id' => $user->stores()->first()->id])
        ->post('/admin/products', [
            'title' => 'QA Jacket',
            'price_amount' => '42.50',
            'status' => 'active',
            'description_html' => '<p>QA Jacket</p>',
        ])
        ->assertRedirect('/admin/products');

    $product = Product::query()->where('title', 'QA Jacket')->firstOrFail();

    $this->actingAs($user)
        ->withSession(['current_store_id' => $user->stores()->first()->id])
        ->patch("/admin/products/{$product->id}", [
            'title' => 'QA Jacket Updated',
            'price_amount' => '44.00',
            'status' => 'active',
            'description_html' => '<p>Updated</p>',
        ])
        ->assertRedirect('/admin/products');

    $this->actingAs($user)
        ->withSession(['current_store_id' => $user->stores()->first()->id])
        ->patch("/admin/products/{$product->id}/archive")
        ->assertRedirect();

    expect($product->refresh()->status->value)->toBe('archived');
});

test('admin order actions handle bank transfer fulfillment and refunds', function (): void {
    $user = User::query()->where('email', 'admin@acme.test')->firstOrFail();
    $storeId = $user->stores()->first()->id;
    $order = Order::query()->where('order_number', '1005')->firstOrFail();

    $this->actingAs($user)->withSession(['current_store_id' => $storeId])
        ->post("/admin/orders/{$order->id}/fulfillments")
        ->assertSessionHasErrors('order');

    $this->actingAs($user)->withSession(['current_store_id' => $storeId])
        ->post("/admin/orders/{$order->id}/confirm-payment")
        ->assertRedirect();

    expect($order->refresh()->financial_status)->toBe('paid');

    $this->actingAs($user)->withSession(['current_store_id' => $storeId])
        ->post("/admin/orders/{$order->id}/fulfillments")
        ->assertRedirect();

    $fulfillment = $order->refresh()->fulfillments()->firstOrFail();

    $this->actingAs($user)->withSession(['current_store_id' => $storeId])
        ->patch("/admin/fulfillments/{$fulfillment->id}/ship")
        ->assertRedirect();

    expect($fulfillment->refresh()->status)->toBe('shipped');

    $this->actingAs($user)->withSession(['current_store_id' => $storeId])
        ->post("/admin/orders/{$order->id}/refunds", ['amount' => '5.00'])
        ->assertRedirect();

    expect($order->refresh()->financial_status)->toBe('partially_refunded');
});
