<?php

use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
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

it('confirms a pending bank-transfer order and marks it paid', function (): void {
    WebhookSubscription::query()->withoutGlobalScopes()->delete();

    $store = Store::factory()->create();
    $user = User::factory()->create(['email_verified_at' => now()]);
    DB::table('store_users')->insert([
        'store_id' => $store->getKey(),
        'user_id' => $user->getKey(),
        'role' => StoreUserRole::Owner->value,
        'created_at' => now(),
    ]);

    $order = Order::factory()->create([
        'store_id' => $store->getKey(),
        'order_number' => '#3001',
        'status' => OrderStatus::Pending,
        'financial_status' => FinancialStatus::Pending,
        'payment_method' => PaymentMethod::BankTransfer,
    ]);

    OrderLine::factory()->create([
        'order_id' => $order->getKey(),
        'variant_id' => null,
        'quantity' => 1,
    ]);

    $this->actingAs($user);
    session(['current_store_id' => $store->getKey()]);
    app()->instance('current_store', $store);

    Livewire::test(\App\Livewire\Admin\Orders\Show::class, ['order' => $order->getKey()])
        ->call('confirmPayment')
        ->assertHasNoErrors();

    expect($order->fresh()->financial_status)->toBe(FinancialStatus::Paid);
});
