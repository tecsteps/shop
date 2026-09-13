<?php

use App\Enums\DiscountStatus;
use App\Enums\DiscountValueType;
use App\Enums\StoreUserRole;
use App\Models\Discount;
use App\Models\StoreUser;
use Tests\Support\CatalogTestHelper;
use Tests\Support\CommerceTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(Illuminate\Routing\Middleware\ThrottleRequests::class);
});

it('lists discounts', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();
    Discount::factory()->count(3)->create(['store_id' => $store->id]);

    $response = $this->getJson(
        "/api/admin/v1/stores/{$store->id}/discounts",
        CatalogTestHelper::adminHeaders($user, ['read-discounts'])
    );

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(3);
});

it('creates a percent discount', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();

    $response = $this->postJson(
        "/api/admin/v1/stores/{$store->id}/discounts",
        [
            'type' => 'code',
            'code' => 'SAVE10',
            'value_type' => 'percent',
            'value_amount' => 10,
            'rules_json' => ['minimum_purchase_amount' => 2000],
        ],
        CatalogTestHelper::adminHeaders($user, ['write-discounts'])
    );

    $response->assertCreated();
    $response->assertJsonPath('data.code', 'SAVE10');
    $response->assertJsonPath('data.value_amount', 10);
});

it('validates discount code uniqueness within store', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();
    Discount::factory()->create(['store_id' => $store->id, 'code' => 'SAVE10']);

    $response = $this->postJson(
        "/api/admin/v1/stores/{$store->id}/discounts",
        ['type' => 'code', 'code' => 'SAVE10', 'value_type' => 'percent', 'value_amount' => 10],
        CatalogTestHelper::adminHeaders($user, ['write-discounts'])
    );

    $response->assertUnprocessable();
});

it('edits a discount', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();
    $discount = Discount::factory()->create([
        'store_id' => $store->id,
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
    ]);

    $response = $this->putJson(
        "/api/admin/v1/stores/{$store->id}/discounts/{$discount->id}",
        ['value_amount' => 15],
        CatalogTestHelper::adminHeaders($user, ['write-discounts'])
    );

    $response->assertOk();
    $response->assertJsonPath('data.value_amount', 15);
});

it('disables a discount', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();
    $discount = Discount::factory()->create(['store_id' => $store->id, 'status' => DiscountStatus::Active]);

    $response = $this->putJson(
        "/api/admin/v1/stores/{$store->id}/discounts/{$discount->id}",
        ['status' => 'disabled'],
        CatalogTestHelper::adminHeaders($user, ['write-discounts'])
    );

    $response->assertOk();
    $response->assertJsonPath('data.status', 'disabled');
});

it('deletes a discount', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();
    $discount = Discount::factory()->create(['store_id' => $store->id]);

    $response = $this->deleteJson(
        "/api/admin/v1/stores/{$store->id}/discounts/{$discount->id}",
        [],
        CatalogTestHelper::adminHeaders($user, ['write-discounts'])
    );

    $response->assertOk();
    expect(Discount::query()->whereKey($discount->id)->exists())->toBeFalse();
});

it('prevents staff from deleting discounts', function () {
    ['store' => $store] = $this->createStoreContext();
    $discount = Discount::factory()->create(['store_id' => $store->id]);

    $staff = App\Models\User::factory()->create();
    StoreUser::factory()->create(['store_id' => $store->id, 'user_id' => $staff->id, 'role' => StoreUserRole::Staff]);

    $response = $this->deleteJson(
        "/api/admin/v1/stores/{$store->id}/discounts/{$discount->id}",
        [],
        CatalogTestHelper::adminHeaders($staff, ['write-discounts'])
    );

    $response->assertForbidden();
});

it('lists shipping zones with rates', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();
    CommerceTestHelper::flatShippingRate($store, ['DE'], 499);

    $response = $this->getJson(
        "/api/admin/v1/stores/{$store->id}/shipping/zones",
        CatalogTestHelper::adminHeaders($user, ['read-settings'])
    );

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.rates'))->toHaveCount(1);
});

it('creates a shipping zone', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();

    $response = $this->postJson(
        "/api/admin/v1/stores/{$store->id}/shipping/zones",
        ['name' => 'Europe', 'countries_json' => ['DE', 'FR'], 'regions_json' => []],
        CatalogTestHelper::adminHeaders($user, ['write-settings'])
    );

    $response->assertCreated();
    $response->assertJsonPath('data.countries_json', ['DE', 'FR']);
});

it('updates a shipping zone', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();
    $rate = CommerceTestHelper::flatShippingRate($store);

    $response = $this->putJson(
        "/api/admin/v1/stores/{$store->id}/shipping/zones/{$rate->zone_id}",
        ['name' => 'DACH'],
        CatalogTestHelper::adminHeaders($user, ['write-settings'])
    );

    $response->assertOk();
    $response->assertJsonPath('data.name', 'DACH');
});

it('adds a shipping rate to a zone', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();
    $rate = CommerceTestHelper::flatShippingRate($store);

    $response = $this->postJson(
        "/api/admin/v1/stores/{$store->id}/shipping/zones/{$rate->zone_id}/rates",
        [
            'name' => 'Express',
            'type' => 'flat',
            'config_json' => ['amount' => 1200],
            'is_active' => true,
        ],
        CatalogTestHelper::adminHeaders($user, ['write-settings'])
    );

    $response->assertCreated();
    $response->assertJsonPath('data.name', 'Express');
});

it('restricts shipping management to owner and admin', function () {
    ['store' => $store] = $this->createStoreContext();

    $staff = App\Models\User::factory()->create();
    StoreUser::factory()->create(['store_id' => $store->id, 'user_id' => $staff->id, 'role' => StoreUserRole::Staff]);

    $response = $this->getJson(
        "/api/admin/v1/stores/{$store->id}/shipping/zones",
        CatalogTestHelper::adminHeaders($staff, ['read-settings'])
    );

    $response->assertForbidden();
});

it('gets tax settings', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();

    $response = $this->getJson(
        "/api/admin/v1/stores/{$store->id}/tax/settings",
        CatalogTestHelper::adminHeaders($user, ['read-settings'])
    );

    $response->assertOk();
    $response->assertJsonPath('data.store_id', $store->id);
    $response->assertJsonPath('data.mode', 'manual');
});

it('updates tax settings', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();

    $response = $this->putJson(
        "/api/admin/v1/stores/{$store->id}/tax/settings",
        [
            'mode' => 'manual',
            'provider' => 'none',
            'prices_include_tax' => true,
            'config_json' => ['default_tax_rate' => 1900],
        ],
        CatalogTestHelper::adminHeaders($user, ['write-settings'])
    );

    $response->assertOk();
    $response->assertJsonPath('data.prices_include_tax', true);
    $response->assertJsonPath('data.config_json.default_tax_rate', 1900);
});

it('restricts tax management to owner and admin', function () {
    ['store' => $store] = $this->createStoreContext();

    $staff = App\Models\User::factory()->create();
    StoreUser::factory()->create(['store_id' => $store->id, 'user_id' => $staff->id, 'role' => StoreUserRole::Staff]);

    $response = $this->putJson(
        "/api/admin/v1/stores/{$store->id}/tax/settings",
        ['mode' => 'manual', 'prices_include_tax' => false, 'config_json' => ['default_tax_rate' => 0]],
        CatalogTestHelper::adminHeaders($staff, ['write-settings'])
    );

    $response->assertForbidden();
});
