<?php

use App\Enums\StoreUserRole;
use App\Models\Collection;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;

beforeEach(function () {
    $this->ctx = createStoreContext();

    $this->otherStore = Store::factory()->for($this->ctx['org'])->create();
    $this->unauthorizedUser = User::factory()->create();
});

it('blocks unauthorized user from editing a product', function () {
    $product = Product::factory()->for($this->ctx['store'])->create();

    $response = test()->actingAs($this->unauthorizedUser)
        ->withHeader('Host', 'shop.test')
        ->get("/admin/products/{$product->id}/edit");

    $response->assertForbidden();
});

it('allows store owner to edit a product', function () {
    $product = Product::factory()->for($this->ctx['store'])->create();

    $response = actingAsAdmin($this->ctx['user'])
        ->withHeader('Host', 'shop.test')
        ->get("/admin/products/{$product->id}/edit");

    $response->assertStatus(200);
});

it('blocks unauthorized user from viewing order details', function () {
    $order = Order::factory()->for($this->ctx['store'])->create();

    $response = test()->actingAs($this->unauthorizedUser)
        ->withHeader('Host', 'shop.test')
        ->get("/admin/orders/{$order->id}");

    $response->assertForbidden();
});

it('blocks unauthorized user from editing a discount', function () {
    $discount = Discount::factory()->for($this->ctx['store'])->create();

    $response = test()->actingAs($this->unauthorizedUser)
        ->withHeader('Host', 'shop.test')
        ->get("/admin/discounts/{$discount->id}/edit");

    $response->assertForbidden();
});

it('blocks unauthorized user from editing a collection', function () {
    $collection = Collection::factory()->for($this->ctx['store'])->create();

    $response = test()->actingAs($this->unauthorizedUser)
        ->withHeader('Host', 'shop.test')
        ->get("/admin/collections/{$collection->id}/edit");

    $response->assertForbidden();
});

it('blocks unauthorized user from viewing customer details', function () {
    $customer = Customer::factory()->for($this->ctx['store'])->create();

    $response = test()->actingAs($this->unauthorizedUser)
        ->withHeader('Host', 'shop.test')
        ->get("/admin/customers/{$customer->id}");

    $response->assertForbidden();
});

it('blocks unauthorized user from editing a page', function () {
    $page = Page::factory()->create(['store_id' => $this->ctx['store']->id]);

    $response = test()->actingAs($this->unauthorizedUser)
        ->withHeader('Host', 'shop.test')
        ->get("/admin/pages/{$page->id}/edit");

    $response->assertForbidden();
});

it('allows staff role to view products', function () {
    $staffUser = User::factory()->create();
    $this->ctx['store']->users()->attach($staffUser, ['role' => StoreUserRole::Staff->value]);

    Product::factory()->for($this->ctx['store'])->create();

    $response = test()->actingAs($staffUser)
        ->withHeader('Host', 'shop.test')
        ->get('/admin/products');

    $response->assertStatus(200);
});
