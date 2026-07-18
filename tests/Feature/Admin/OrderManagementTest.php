<?php

use App\Enums\StoreUserRole;
use App\Livewire\Admin\Orders\Show;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->store = Store::factory()->create();
    $this->user->stores()->attach($this->store, ['role' => StoreUserRole::Owner]);
    app()->instance('current_store', $this->store);
});

it('fulfills a paid order through the domain service', function () {
    $order = Order::factory()->create(['store_id' => $this->store->id, 'total_amount' => 2000]);
    $line = OrderLine::factory()->create(['order_id' => $order->id, 'quantity' => 2, 'unit_price_amount' => 1000, 'total_amount' => 2000]);
    Payment::factory()->create(['order_id' => $order->id, 'amount' => 2000]);

    Livewire::actingAs($this->user)->test(Show::class, ['order' => $order])
        ->set("fulfillmentLines.{$line->id}", 2)
        ->set('trackingCompany', 'DHL')
        ->call('createFulfillment')
        ->assertHasNoErrors();

    expect($order->fulfillments()->count())->toBe(1)
        ->and($order->fresh()->fulfillment_status->value)->toBe('fulfilled');
});

it('refunds a paid order through the domain service', function () {
    $order = Order::factory()->create(['store_id' => $this->store->id, 'total_amount' => 2000]);
    OrderLine::factory()->create(['order_id' => $order->id, 'quantity' => 2, 'unit_price_amount' => 1000, 'total_amount' => 2000]);
    Payment::factory()->create(['order_id' => $order->id, 'amount' => 2000]);

    Livewire::actingAs($this->user)->test(Show::class, ['order' => $order])
        ->set('refundAmount', 500)
        ->set('refundReason', 'Customer request')
        ->call('createRefund')
        ->assertHasNoErrors();

    expect($order->refunds()->count())->toBe(1)
        ->and($order->fresh()->financial_status->value)->toBe('partially_refunded');
});

it('marks a fulfillment as shipped and delivered', function () {
    $order = Order::factory()->create(['store_id' => $this->store->id, 'total_amount' => 2000]);
    $line = OrderLine::factory()->create(['order_id' => $order->id, 'quantity' => 1, 'unit_price_amount' => 2000, 'total_amount' => 2000]);
    Payment::factory()->create(['order_id' => $order->id, 'amount' => 2000]);

    $component = Livewire::actingAs($this->user)->test(Show::class, ['order' => $order])
        ->set("fulfillmentLines.{$line->id}", 1)
        ->call('createFulfillment')
        ->assertHasNoErrors();

    $fulfillmentId = $order->fulfillments()->first()->id;

    $component->call('markAsShipped', $fulfillmentId)->assertHasNoErrors();
    expect($order->fulfillments()->first()->status->value)->toBe('shipped');

    $component->call('markAsDelivered', $fulfillmentId)->assertHasNoErrors();
    expect($order->fulfillments()->first()->status->value)->toBe('delivered');
});
