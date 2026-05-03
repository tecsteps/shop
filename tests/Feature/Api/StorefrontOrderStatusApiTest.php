<?php

use App\Models\Order;
use App\Models\Store;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
    $this->seed();
    $this->store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $this->order = Order::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('order_number', '#1001')
        ->firstOrFail();
});

test('storefront order status api requires a valid order access token', function (): void {
    $url = 'http://shop.test/api/storefront/v1/orders/'.ltrim($this->order->order_number, '#');

    $this->getJson($url)->assertUnauthorized();

    $this->getJson($url.'?token=wrong-token')->assertUnauthorized();
});

test('storefront order status api returns order status for a valid token', function (): void {
    $token = app(OrderService::class)->accessToken($this->order);

    $this->getJson('http://shop.test/api/storefront/v1/orders/'.ltrim($this->order->order_number, '#').'?token='.$token)
        ->assertOk()
        ->assertJsonPath('order_number', '#1001')
        ->assertJsonPath('status', $this->order->status->value)
        ->assertJsonPath('financial_status', $this->order->financial_status->value)
        ->assertJsonPath('currency', $this->order->currency)
        ->assertJsonPath('totals.total_amount', $this->order->total_amount)
        ->assertJsonCount($this->order->lines()->count(), 'lines');
});
