<?php

use App\Models\Collection as CatalogCollection;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Fulfillment;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\Refund;
use App\Models\Store;
use App\Models\Theme;
use App\Models\User;
use App\Policies\CollectionPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\DiscountPolicy;
use App\Policies\FulfillmentPolicy;
use App\Policies\OrderPolicy;
use App\Policies\PagePolicy;
use App\Policies\ProductPolicy;
use App\Policies\RefundPolicy;
use App\Policies\StorePolicy;
use App\Policies\ThemePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('store role helper returns the role for the selected store', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();

    $store->users()->attach($user, ['role' => 'staff', 'created_at' => now()]);

    expect($user->roleForStore($store)?->value)->toBe('staff');
});

test('product policy allows support to view but not mutate products', function () {
    $store = Store::factory()->create();
    $support = User::factory()->create();
    $store->users()->attach($support, ['role' => 'support', 'created_at' => now()]);

    app()->instance('current_store', $store);
    $product = Product::factory()->for($store)->create();
    $policy = new ProductPolicy;

    expect($policy->viewAny($support))->toBeTrue()
        ->and($policy->view($support, $product))->toBeTrue()
        ->and($policy->create($support))->toBeFalse()
        ->and($policy->update($support, $product))->toBeFalse()
        ->and($policy->delete($support, $product))->toBeFalse();
});

test('staff can manage discounts but only owner or admin can delete them', function () {
    $store = Store::factory()->create();
    $staff = User::factory()->create();
    $store->users()->attach($staff, ['role' => 'staff', 'created_at' => now()]);

    app()->instance('current_store', $store);
    $discount = Discount::factory()->for($store)->create();
    $policy = new DiscountPolicy;

    expect($policy->create($staff))->toBeTrue()
        ->and($policy->update($staff, $discount))->toBeTrue()
        ->and($policy->delete($staff, $discount))->toBeFalse();
});

test('only owners can delete stores', function () {
    $store = Store::factory()->create();
    $owner = User::factory()->create();
    $admin = User::factory()->create();

    $store->users()->attach($owner, ['role' => 'owner', 'created_at' => now()]);
    $store->users()->attach($admin, ['role' => 'admin', 'created_at' => now()]);

    $policy = new StorePolicy;

    expect($policy->delete($owner, $store))->toBeTrue()
        ->and($policy->delete($admin, $store))->toBeFalse()
        ->and($policy->update($admin, $store))->toBeTrue();
});

test('resource policies type model parameters with concrete resources', function (): void {
    $policyMethods = [
        CollectionPolicy::class => [
            'view' => CatalogCollection::class,
            'update' => CatalogCollection::class,
            'delete' => CatalogCollection::class,
        ],
        CustomerPolicy::class => [
            'view' => Customer::class,
            'update' => Customer::class,
        ],
        DiscountPolicy::class => [
            'view' => Discount::class,
            'update' => Discount::class,
            'delete' => Discount::class,
        ],
        FulfillmentPolicy::class => [
            'update' => Fulfillment::class,
            'cancel' => Fulfillment::class,
        ],
        OrderPolicy::class => [
            'view' => Order::class,
            'update' => Order::class,
            'cancel' => Order::class,
            'createFulfillment' => Order::class,
            'createRefund' => Order::class,
        ],
        PagePolicy::class => [
            'view' => Page::class,
            'update' => Page::class,
            'delete' => Page::class,
        ],
        ProductPolicy::class => [
            'view' => Product::class,
            'update' => Product::class,
            'delete' => Product::class,
            'archive' => Product::class,
            'restore' => Product::class,
        ],
        RefundPolicy::class => [
            'view' => Refund::class,
        ],
        ThemePolicy::class => [
            'view' => Theme::class,
            'update' => Theme::class,
            'publish' => Theme::class,
            'delete' => Theme::class,
        ],
    ];

    foreach ($policyMethods as $policyClass => $methods) {
        foreach ($methods as $method => $modelClass) {
            $parameter = (new \ReflectionMethod($policyClass, $method))->getParameters()[1] ?? null;
            $type = $parameter?->getType();

            expect($type)
                ->toBeInstanceOf(\ReflectionNamedType::class)
                ->and($type->getName())->toBe($modelClass);
        }
    }
});
