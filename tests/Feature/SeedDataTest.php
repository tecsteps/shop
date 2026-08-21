<?php

use App\Models\Customer;
use App\Models\Discount;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreDomain;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ShopSeeder;

uses(\Illuminate\Foundation\Testing\LazilyRefreshDatabase::class);

beforeEach(function (): void {
    app()->forgetInstance('current_store');
});

it('seeds the complete deterministic tenant fixture through DatabaseSeeder', function (): void {
    $this->seed(DatabaseSeeder::class);

    $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();
    $fashionProducts = Product::withoutGlobalScopes()->where('store_id', $fashion->getKey());
    $electronicsProducts = Product::withoutGlobalScopes()->where('store_id', $electronics->getKey());
    $fashionCustomers = Customer::withoutGlobalScopes()->where('store_id', $fashion->getKey());
    $electronicsCustomers = Customer::withoutGlobalScopes()->where('store_id', $electronics->getKey());
    $fashionOrders = Order::withoutGlobalScopes()->where('store_id', $fashion->getKey());
    $electronicsOrders = Order::withoutGlobalScopes()->where('store_id', $electronics->getKey());

    expect(Organization::query()->count())->toBe(1)
        ->and(Store::query()->count())->toBe(2)
        ->and(StoreDomain::query()->where('store_id', $fashion->getKey())->count())->toBe(2)
        ->and(StoreDomain::query()->where('store_id', $electronics->getKey())->count())->toBe(1)
        ->and($fashionProducts->count())->toBe(20)
        ->and($electronicsProducts->count())->toBe(5)
        ->and((clone $fashionProducts)->where('handle', 'limited-edition-sneakers')->exists())->toBeTrue()
        ->and((clone $fashionProducts)->where('handle', 'sold-out-limited-tee')->exists())->toBeFalse()
        ->and($fashionCustomers->count())->toBe(10)
        ->and($electronicsCustomers->count())->toBe(2)
        ->and($fashionOrders->count())->toBe(15)
        ->and($electronicsOrders->count())->toBe(3)
        ->and(Discount::withoutGlobalScopes()->where('store_id', $fashion->getKey())->count())->toBe(5);

    $classic = (clone $fashionProducts)->where('handle', 'classic-cotton-t-shirt')->firstOrFail();
    $pendingOrder = (clone $fashionOrders)->where('order_number', '#1005')->firstOrFail();
    $digitalOrder = (clone $fashionOrders)->where('order_number', '#1014')->firstOrFail();
    $discountOrder = (clone $fashionOrders)->where('order_number', '#1015')->firstOrFail();

    expect($classic->variants()->count())->toBe(12)
        ->and($classic->variants()->where('is_default', true)->count())->toBe(1)
        ->and(InventoryItem::withoutGlobalScopes()->whereIn('variant_id', $classic->variants()->pluck('id'))->count())->toBe(12)
        ->and($pendingOrder->financial_status->value)->toBe('pending')
        ->and($pendingOrder->payments()->firstOrFail()->status->value)->toBe('pending')
        ->and($digitalOrder->fulfillment_status->value)->toBe('fulfilled')
        ->and($digitalOrder->lines()->firstOrFail()->variant->requires_shipping)->toBeFalse()
        ->and($discountOrder->discount_amount)->toBe(550)
        ->and($discountOrder->lines()->sum('line_discount_amount'))->toBe(550);

    $countsBeforeReseed = [
        'stores' => Store::query()->count(),
        'products' => Product::withoutGlobalScopes()->count(),
        'customers' => Customer::withoutGlobalScopes()->count(),
        'orders' => Order::withoutGlobalScopes()->count(),
        'inventory' => InventoryItem::withoutGlobalScopes()->count(),
    ];

    $this->seed(DatabaseSeeder::class);

    expect([
        'stores' => Store::query()->count(),
        'products' => Product::withoutGlobalScopes()->count(),
        'customers' => Customer::withoutGlobalScopes()->count(),
        'orders' => Order::withoutGlobalScopes()->count(),
        'inventory' => InventoryItem::withoutGlobalScopes()->count(),
    ])->toBe($countsBeforeReseed);
});

it('keeps ShopSeeder legacy CommerceFlow compatibility isolated from DatabaseSeeder', function (): void {
    $this->seed(ShopSeeder::class);

    $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $products = Product::withoutGlobalScopes()->where('store_id', $fashion->getKey());
    $legacyProduct = (clone $products)->where('handle', 'sold-out-limited-tee')->firstOrFail();
    $classic = (clone $products)->where('handle', 'classic-cotton-t-shirt')->firstOrFail();
    $classicVariant = $classic->variants()->orderBy('position')->firstOrFail();

    expect($products->count())->toBe(20)
        ->and($products->where('handle', 'limited-edition-sneakers')->exists())->toBeFalse()
        ->and($legacyProduct->title)->toBe('Sold Out Limited Tee')
        ->and(InventoryItem::withoutGlobalScopes()->where('variant_id', $legacyProduct->defaultVariant()->getKey())->value('quantity_on_hand'))->toBe(0)
        ->and(InventoryItem::withoutGlobalScopes()->where('variant_id', $classicVariant->getKey())->value('quantity_on_hand'))->toBe(80)
        ->and(StoreDomain::query()->where('hostname', 'shop.test')->where('store_id', $fashion->getKey())->exists())->toBeTrue();
});
