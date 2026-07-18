<?php

use App\Enums\StoreUserRole;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Policies\CustomerPolicy;
use App\Policies\DiscountPolicy;
use App\Policies\ProductPolicy;
use App\Policies\RefundPolicy;
use App\Policies\StorePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('applies the roadmap role matrix to policy stubs', function () {
    $store = Store::factory()->create();
    $product = Product::factory()->for($store)->create();
    $customer = Customer::factory()->for($store)->create();
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $staff = User::factory()->create();
    $support = User::factory()->create();

    $owner->stores()->attach($store, ['role' => StoreUserRole::Owner]);
    $admin->stores()->attach($store, ['role' => StoreUserRole::Admin]);
    $staff->stores()->attach($store, ['role' => StoreUserRole::Staff]);
    $support->stores()->attach($store, ['role' => StoreUserRole::Support]);
    app()->instance('current_store', $store);

    $productPolicy = new ProductPolicy;
    $customerPolicy = new CustomerPolicy;

    expect($productPolicy->update($staff, $product))->toBeTrue()
        ->and($productPolicy->delete($staff, $product))->toBeFalse()
        ->and($productPolicy->view($support, $product))->toBeFalse()
        ->and((new DiscountPolicy)->delete($staff, new stdClass))->toBeTrue()
        ->and($customerPolicy->view($support, $customer))->toBeTrue()
        ->and($customerPolicy->update($support, $customer))->toBeFalse()
        ->and((new RefundPolicy)->create($admin))->toBeTrue()
        ->and((new RefundPolicy)->create($staff))->toBeFalse()
        ->and((new StorePolicy)->update($admin, $store))->toBeTrue()
        ->and((new StorePolicy)->delete($admin, $store))->toBeFalse()
        ->and((new StorePolicy)->delete($owner, $store))->toBeTrue();
});
