<?php

use App\Models\Customer;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
    $this->seed();
});

test('seeders provide deterministic stores domains users and customers', function (): void {
    $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

    expect(Store::query()->count())->toBe(2)
        ->and(StoreDomain::query()->where('hostname', 'shop.test')->where('store_id', $fashion->id)->exists())->toBeTrue()
        ->and(StoreDomain::query()->where('hostname', 'acme-fashion.test')->where('store_id', $fashion->id)->exists())->toBeTrue()
        ->and(StoreDomain::query()->where('hostname', 'acme-electronics.test')->where('store_id', $electronics->id)->exists())->toBeTrue()
        ->and(User::query()->where('email', 'admin@example.com')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'admin@acme.test')->exists())->toBeTrue()
        ->and(Customer::query()->where('store_id', $fashion->id)->count())->toBe(10)
        ->and(Customer::query()->where('store_id', $electronics->id)->count())->toBe(2)
        ->and(Customer::query()->where('store_id', $fashion->id)->where('email', 'customer@acme.test')->exists())->toBeTrue();
});

test('seeders provide catalog fixtures for browsing inventory and tenant isolation', function (): void {
    $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

    $soldOut = Product::query()->where('store_id', $fashion->id)->where('handle', 'sold-out-canvas-sneaker')->firstOrFail();
    $backorder = Product::query()->where('store_id', $fashion->id)->where('handle', 'backorder-utility-vest')->firstOrFail();
    $draft = Product::query()->where('store_id', $fashion->id)->where('handle', 'archive-sample-parka')->firstOrFail();

    expect(Product::query()->where('store_id', $fashion->id)->count())->toBe(20)
        ->and(Product::query()->where('store_id', $electronics->id)->count())->toBe(5)
        ->and(Product::query()->where('store_id', $fashion->id)->where('handle', 'classic-cotton-t-shirt')->firstOrFail()->options()->count())->toBe(2)
        ->and($soldOut->variants()->firstOrFail()->inventoryItem->quantity_on_hand)->toBe(0)
        ->and($soldOut->variants()->firstOrFail()->inventoryItem->policy->value)->toBe('deny')
        ->and($backorder->variants()->firstOrFail()->inventoryItem->quantity_on_hand)->toBe(0)
        ->and($backorder->variants()->firstOrFail()->inventoryItem->policy->value)->toBe('continue')
        ->and($draft->status->value)->toBe('draft');
});

test('seeders provide discount and order workflow fixtures', function (): void {
    $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

    expect(Discount::query()->where('store_id', $fashion->id)->pluck('code')->sort()->values()->all())->toBe([
        'EXPIRED20',
        'FLAT5',
        'FREESHIP',
        'MAXED',
        'WELCOME10',
    ])
        ->and(Discount::query()->where('store_id', $fashion->id)->where('code', 'MAXED')->firstOrFail()->usage_count)->toBe(5)
        ->and(Order::withoutGlobalScopes()->where('store_id', $fashion->id)->count())->toBe(15)
        ->and(Order::withoutGlobalScopes()->where('store_id', $electronics->id)->count())->toBe(3)
        ->and(Order::withoutGlobalScopes()->where('store_id', $fashion->id)->where('order_number', '#1005')->firstOrFail()->financial_status->value)->toBe('pending')
        ->and(Order::withoutGlobalScopes()->where('store_id', $electronics->id)->where('order_number', '#5003')->firstOrFail()->payment_method->value)->toBe('bank_transfer');
});
