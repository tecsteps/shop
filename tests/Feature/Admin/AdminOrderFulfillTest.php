<?php

use App\Enums\FulfillmentStatus;
use App\Enums\StoreUserRole;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Store;
use App\Models\User;
use App\Models\WebhookSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('fulfills a paid order and updates fulfillment status', function (): void {
    WebhookSubscription::query()->withoutGlobalScopes()->delete();

    $store = Store::factory()->create();
    $user = User::factory()->create(['email_verified_at' => now()]);
    DB::table('store_users')->insert([
        'store_id' => $store->getKey(),
        'user_id' => $user->getKey(),
        'role' => StoreUserRole::Owner->value,
        'created_at' => now(),
    ]);

    $order = Order::factory()->paid()->create(['store_id' => $store->getKey()]);
    $line = OrderLine::factory()->create([
        'order_id' => $order->getKey(),
        'variant_id' => null,
        'quantity' => 2,
    ]);

    $this->actingAs($user);
    session(['current_store_id' => $store->getKey()]);
    app()->instance('current_store', $store);

    Livewire::test(\App\Livewire\Admin\Orders\Show::class, ['order' => $order->getKey()])
        ->call('openFulfillmentModal')
        ->set('fulfillmentLineQuantities.'.$line->getKey(), 2)
        ->call('createFulfillment')
        ->assertHasNoErrors();

    expect($order->fresh()->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled);
});
