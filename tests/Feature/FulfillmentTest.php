<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentOrderStatus;
use App\Exceptions\FulfillmentGuardException;
use App\Models\Order;
use App\Models\OrderLine;
use App\Services\FulfillmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('tracks partial and complete fulfillment quantities', function () {
    $order = Order::factory()->create(['financial_status' => FinancialStatus::Paid]);
    $line = OrderLine::factory()->create(['order_id' => $order->id, 'quantity' => 2]);
    $service = app(FulfillmentService::class);

    $service->create($order, [$line->id => 1]);
    expect($order->refresh()->fulfillment_status)->toBe(FulfillmentOrderStatus::Partial);

    $service->create($order, [$line->id => 1]);
    expect($order->refresh()->fulfillment_status)->toBe(FulfillmentOrderStatus::Fulfilled);
});

it('blocks fulfillment before payment', function () {
    $order = Order::factory()->create(['financial_status' => FinancialStatus::Pending]);
    $line = OrderLine::factory()->create(['order_id' => $order->id]);

    app(FulfillmentService::class)->create($order, [$line->id => 1]);
})->throws(FulfillmentGuardException::class);
