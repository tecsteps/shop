<?php

use App\Enums\FinancialStatus;
use App\Enums\StoreUserRole;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Store;
use App\Models\StoreUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    app()->forgetInstance('current_store');
    $this->store = Store::factory()->create();
    $this->user = User::factory()->create();
    StoreUser::query()->create(['store_id' => $this->store->id, 'user_id' => $this->user->id, 'role' => StoreUserRole::Owner, 'created_at' => now()]);
    app()->instance('current_store', $this->store);
    $this->order = Order::factory()->for($this->store)->create(['financial_status' => FinancialStatus::Paid]);
    $this->line = OrderLine::factory()->for($this->order)->create(['quantity' => 2]);
    app()->forgetInstance('current_store');
});

it('lists order details and creates a fulfillment', function () {
    Sanctum::actingAs($this->user, ['read-orders', 'write-orders']);

    $this->getJson("/api/admin/v1/stores/{$this->store->id}/orders")
        ->assertSuccessful()->assertJsonCount(1, 'data');
    $this->getJson("/api/admin/v1/stores/{$this->store->id}/orders/{$this->order->id}")
        ->assertSuccessful()->assertJsonPath('data.order_number', $this->order->order_number);
    $this->postJson("/api/admin/v1/stores/{$this->store->id}/orders/{$this->order->id}/fulfillments", ['lines' => [$this->line->id => 2]])
        ->assertCreated()->assertJsonPath('data.lines.0.quantity', 2);
});

it('enforces write order abilities', function () {
    Sanctum::actingAs($this->user, ['read-orders']);

    $this->postJson("/api/admin/v1/stores/{$this->store->id}/orders/{$this->order->id}/fulfillments", ['lines' => [$this->line->id => 1]])
        ->assertForbidden();
});
