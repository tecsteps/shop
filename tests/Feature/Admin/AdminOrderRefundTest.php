<?php

use App\Enums\FinancialStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\StoreUserRole;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Store;
use App\Models\User;
use App\Models\WebhookSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('refunds a paid order fully and marks it refunded', function (): void {
    WebhookSubscription::query()->withoutGlobalScopes()->delete();

    $store = Store::factory()->create();
    $user = User::factory()->create(['email_verified_at' => now()]);
    DB::table('store_users')->insert([
        'store_id' => $store->getKey(),
        'user_id' => $user->getKey(),
        'role' => StoreUserRole::Owner->value,
        'created_at' => now(),
    ]);

    $order = Order::factory()->paid()->create([
        'store_id' => $store->getKey(),
        'total_amount' => 5000,
    ]);
    OrderLine::factory()->create([
        'order_id' => $order->getKey(),
        'variant_id' => null,
        'quantity' => 1,
        'unit_price_amount' => 5000,
        'total_amount' => 5000,
    ]);
    Payment::factory()->create([
        'order_id' => $order->getKey(),
        'method' => PaymentMethod::CreditCard->value,
        'status' => PaymentStatus::Captured->value,
        'amount' => 5000,
        'currency' => 'USD',
    ]);

    $this->actingAs($user);
    session(['current_store_id' => $store->getKey()]);
    app()->instance('current_store', $store);

    Livewire::test(\App\Livewire\Admin\Orders\Show::class, ['order' => $order->getKey()])
        ->call('openRefundModal')
        ->set('refundAmount', 5000)
        ->set('refundReason', 'Customer request')
        ->call('createRefund')
        ->assertHasNoErrors();

    expect($order->fresh()->financial_status)->toBe(FinancialStatus::Refunded);
});
