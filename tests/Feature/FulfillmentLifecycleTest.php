<?php

use App\Livewire\Admin\Orders\Show;
use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Store;
use App\Models\User;
use App\Services\FulfillmentService;
use Livewire\Livewire;

/** @return array{order: Order, lines: array<int, OrderLine>} */
function fulfillmentLifecycleOrder(Store $store, array $lineQuantities): array
{
    $order = Order::query()->create([
        'store_id' => $store->id,
        'order_number' => 'FUL-'.fake()->unique()->numerify('#####'),
        'payment_method' => 'credit_card',
        'status' => 'paid',
        'financial_status' => 'paid',
        'fulfillment_status' => 'unfulfilled',
    ]);
    $lines = [];

    foreach ($lineQuantities as $index => $quantity) {
        $lines[] = $order->lines()->create([
            'title_snapshot' => 'Fulfillment item '.($index + 1),
            'variant_title_snapshot' => 'Default',
            'quantity' => $quantity,
            'unit_price_amount' => 1200,
            'total_amount' => 1200 * $quantity,
        ]);
    }

    return ['order' => $order, 'lines' => $lines];
}

function fulfillmentLifecycleStaff(Store $store): User
{
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'staff']);

    return $user;
}

it('keeps a fully allocated pending shipment partial until it is delivered', function () {
    $store = shopStore();
    ['order' => $order, 'lines' => [$line]] = fulfillmentLifecycleOrder($store, [3]);
    $service = app(FulfillmentService::class);

    $fulfillment = $service->create($order, [$line->id => 3]);

    expect($fulfillment->status)->toBe('pending')
        ->and($order->refresh()->fulfillment_status)->toBe('partial')
        ->and($order->status)->toBe('paid');

    $service->markShipped($fulfillment);

    expect($fulfillment->refresh()->status)->toBe('shipped')
        ->and($fulfillment->shipped_at)->not->toBeNull()
        ->and($order->refresh()->fulfillment_status)->toBe('partial')
        ->and($order->status)->toBe('paid');

    $service->markDelivered($fulfillment);

    expect($fulfillment->refresh()->status)->toBe('delivered')
        ->and($fulfillment->delivered_at)->not->toBeNull()
        ->and($order->refresh()->fulfillment_status)->toBe('fulfilled')
        ->and($order->status)->toBe('fulfilled');
});

it('fulfills an order line across multiple partial shipments only after every quantity is delivered', function () {
    $store = shopStore();
    ['order' => $order, 'lines' => [$line]] = fulfillmentLifecycleOrder($store, [3]);
    $service = app(FulfillmentService::class);

    $firstShipment = $service->create($order, [$line->id => 1]);

    expect($order->refresh()->fulfillment_status)->toBe('partial');

    $service->markShipped($firstShipment);
    $service->markDelivered($firstShipment);

    expect($order->refresh()->fulfillment_status)->toBe('partial')
        ->and($order->status)->toBe('paid');

    $secondShipment = $service->create($order, [$line->id => 2]);

    expect($order->refresh()->fulfillment_status)->toBe('partial')
        ->and(FulfillmentLine::query()->where('order_line_id', $line->id)->sum('quantity'))->toBe(3);

    $service->markShipped($secondShipment);

    expect($order->refresh()->fulfillment_status)->toBe('partial');

    $service->markDelivered($secondShipment);

    expect($order->refresh()->fulfillment_status)->toBe('fulfilled')
        ->and($order->status)->toBe('fulfilled');
});

it('lets staff select partial quantities and shows shipment transition actions on the order page', function () {
    $store = shopStore();
    ['order' => $order, 'lines' => [$line]] = fulfillmentLifecycleOrder($store, [4]);
    $user = fulfillmentLifecycleStaff($store);
    app()->instance('current_store', $store);

    $page = Livewire::actingAs($user)->test(Show::class, ['order' => $order->id])
        ->assertSet("fulfillmentLines.{$line->id}", 4)
        ->set("fulfillmentLines.{$line->id}", 2)
        ->set('trackingCompany', 'DHL')
        ->set('trackingNumber', 'DHL-5488')
        ->set('trackingUrl', 'https://tracking.example/DHL-5488')
        ->call('fulfill')
        ->assertHasNoErrors()
        ->assertSee('Mark as shipped')
        ->assertDontSee('Mark as delivered');

    $fulfillment = Fulfillment::query()->where('order_id', $order->id)->firstOrFail();

    expect($fulfillment->lines()->sum('quantity'))->toBe(2)
        ->and($fulfillment->tracking_company)->toBe('DHL')
        ->and($fulfillment->tracking_number)->toBe('DHL-5488')
        ->and($fulfillment->tracking_url)->toBe('https://tracking.example/DHL-5488')
        ->and($order->refresh()->fulfillment_status)->toBe('partial');

    $page->call('markAsShipped', $fulfillment->id)
        ->assertSee('Mark as delivered')
        ->call('markAsDelivered', $fulfillment->id)
        ->assertSee('Partial');

    expect($fulfillment->refresh()->status)->toBe('delivered')
        ->and($order->refresh()->fulfillment_status)->toBe('partial')
        ->and($order->status)->toBe('paid');
});

it('prevents support users from changing fulfillment status', function () {
    $store = shopStore();
    ['order' => $order, 'lines' => [$line]] = fulfillmentLifecycleOrder($store, [1]);
    $support = User::factory()->create();
    $support->stores()->attach($store->id, ['role' => 'support']);
    app()->instance('current_store', $store);
    $fulfillment = app(FulfillmentService::class)->create($order, [$line->id => 1]);

    Livewire::actingAs($support)
        ->test(Show::class, ['order' => $order->id])
        ->assertDontSee('Mark as shipped')
        ->call('markAsShipped', $fulfillment->id);

    expect($fulfillment->refresh()->status)->toBe('pending')
        ->and($fulfillment->shipped_at)->toBeNull();
});

it('does not mark an order fulfilled while another allocated fulfillment is still pending or shipped', function () {
    $store = shopStore();
    ['order' => $order, 'lines' => [$firstLine, $secondLine]] = fulfillmentLifecycleOrder($store, [1, 1]);
    $service = app(FulfillmentService::class);
    $firstShipment = $service->create($order, [$firstLine->id => 1]);
    $secondShipment = $service->create($order, [$secondLine->id => 1]);

    expect($order->refresh()->fulfillment_status)->toBe('partial');

    $service->markShipped($firstShipment);
    $service->markShipped($secondShipment);
    $service->markDelivered($firstShipment);

    expect($order->refresh()->fulfillment_status)->toBe('partial')
        ->and($order->status)->toBe('paid');

    $service->markDelivered($secondShipment);

    expect($order->refresh()->fulfillment_status)->toBe('fulfilled')
        ->and($order->status)->toBe('fulfilled');
});
