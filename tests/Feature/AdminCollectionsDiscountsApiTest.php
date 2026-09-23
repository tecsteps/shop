<?php

use App\Http\Controllers\Api\AdminController;
use App\Models\Collection;
use App\Models\Discount;
use App\Models\Store;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

function invokeCollectionsDiscountsAdminAction(string $action, int $storeId, array $routeParameters = [], array $input = [], string $method = 'POST'): JsonResponse
{
    $request = Request::create('/api/admin/v1/stores/'.$storeId, $method, $input);
    $request->setUserResolver(fn (): ?User => auth()->user());
    app()->instance('request', $request);

    return app()->call([app(AdminController::class), $action], [
        'request' => $request,
        'storeId' => $storeId,
        ...$routeParameters,
    ]);
}

function attachStoreRole(User $user, Store $store, string $role = 'owner'): void
{
    $user->stores()->attach($store->getKey(), ['role' => $role]);
    app()->instance('current_store', $store);
    test()->actingAs($user);
}

function adminApiCrudToken(User $user, Store $store, array $abilities): string
{
    $plainTextToken = Str::random(48);
    $user->tokens()->create([
        'store_id' => $store->getKey(),
        'name' => 'collections-discounts-api-test',
        'token' => hash('sha256', $plainTextToken),
        'abilities' => $abilities,
    ]);

    return $plainTextToken;
}

it('lists only the selected store collections with supported filters and response metadata', function () {
    $store = shopStore();
    $otherStore = shopStore('other-shop.test');
    $user = User::factory()->create();
    attachStoreRole($user, $store, 'support');
    $product = shopProduct($store)['product'];
    $collection = Collection::query()->create([
        'store_id' => $store->id,
        'title' => 'Summer Picks',
        'handle' => 'summer-picks',
        'type' => 'manual',
        'status' => 'active',
    ]);
    $collection->products()->attach($product->id);
    Collection::query()->create([
        'store_id' => $store->id,
        'title' => 'Draft Summer',
        'handle' => 'draft-summer',
        'type' => 'manual',
        'status' => 'draft',
    ]);
    Collection::withoutGlobalScopes()->create([
        'store_id' => $otherStore->id,
        'title' => 'Summer Other Store',
        'handle' => 'summer-other-store',
        'type' => 'manual',
        'status' => 'active',
    ]);

    $response = invokeCollectionsDiscountsAdminAction('collections', $store->id, input: [
        'query' => 'Summer',
        'status' => 'active',
        'per_page' => 1,
    ], method: 'GET');

    expect($response->getData(true)['data'][0])->toMatchArray([
        'id' => $collection->id,
        'store_id' => $store->id,
        'title' => 'Summer Picks',
        'handle' => 'summer-picks',
        'description_html' => null,
        'type' => 'manual',
        'status' => 'active',
        'products_count' => 1,
    ])->and($response->getData(true)['meta'])->toBe(['current_page' => 1, 'per_page' => 1, 'total' => 1, 'last_page' => 1])
        ->and(array_keys($response->getData(true)['data'][0]))->toBe([
            'id', 'store_id', 'title', 'handle', 'description_html', 'type', 'status', 'products_count', 'created_at', 'updated_at',
        ]);
});

it('creates updates and deletes collections while validating tenant-owned product associations', function () {
    $store = shopStore();
    $otherStore = shopStore('other-shop.test');
    $user = User::factory()->create();
    attachStoreRole($user, $store);
    $firstProduct = shopProduct($store)['product'];
    $secondProduct = shopProduct($store)['product'];
    $otherProduct = shopProduct($otherStore)['product'];

    $created = invokeCollectionsDiscountsAdminAction('createCollection', $store->id, input: [
        'title' => 'Summer Collection',
        'description_html' => '<p>Bright basics.</p>',
        'type' => 'manual',
        'product_ids' => [$firstProduct->id],
    ]);
    $collectionData = $created->getData(true);
    $collection = Collection::query()->findOrFail($collectionData['id']);

    expect($created->getStatusCode())->toBe(201)
        ->and($collectionData)->toMatchArray([
            'store_id' => $store->id,
            'title' => 'Summer Collection',
            'handle' => 'summer-collection',
            'type' => 'manual',
            'status' => 'active',
            'products_count' => 1,
        ])
        ->and($collection->products()->pluck('products.id')->all())->toBe([$firstProduct->id]);

    $updated = invokeCollectionsDiscountsAdminAction('updateCollection', $store->id, ['collectionId' => $collection->id], [
        'title' => 'Updated Summer',
        'add_product_ids' => [$secondProduct->id],
        'remove_product_ids' => [$firstProduct->id],
    ], 'PUT');

    expect($updated->getStatusCode())->toBe(200)
        ->and($updated->getData(true)['title'])->toBe('Updated Summer')
        ->and($updated->getData(true)['products_count'])->toBe(1)
        ->and($collection->fresh()->products()->pluck('products.id')->all())->toBe([$secondProduct->id]);

    expect(fn () => invokeCollectionsDiscountsAdminAction('updateCollection', $store->id, ['collectionId' => $collection->id], [
        'product_ids' => [$firstProduct->id],
        'add_product_ids' => [$secondProduct->id],
    ], 'PUT'))->toThrow(ValidationException::class);

    expect(fn () => invokeCollectionsDiscountsAdminAction('updateCollection', $store->id, ['collectionId' => $collection->id], [
        'product_ids' => [$otherProduct->id],
    ], 'PUT'))->toThrow(ValidationException::class);

    expect(fn () => invokeCollectionsDiscountsAdminAction('updateCollection', $store->id, ['collectionId' => $collection->id + 1000], [], 'PUT'))
        ->toThrow(ModelNotFoundException::class);

    $deleted = invokeCollectionsDiscountsAdminAction('deleteCollection', $store->id, ['collectionId' => $collection->id], [], 'DELETE');
    expect($deleted->getStatusCode())->toBe(200)
        ->and($deleted->getData(true))->toBe(['message' => 'Collection deleted'])
        ->and(Collection::query()->whereKey($collection->id)->exists())->toBeFalse();
});

it('creates filters updates and deletes discounts using the documented API resource contract', function () {
    $store = shopStore();
    $otherStore = shopStore('other-shop.test');
    $user = User::factory()->create();
    attachStoreRole($user, $store);
    $product = shopProduct($store)['product'];
    $collection = Collection::query()->create([
        'store_id' => $store->id,
        'title' => 'Eligible Collection',
        'handle' => 'eligible-collection',
        'type' => 'manual',
        'status' => 'active',
    ]);
    $otherProduct = shopProduct($otherStore)['product'];

    $created = invokeCollectionsDiscountsAdminAction('createDiscount', $store->id, input: [
        'type' => 'code',
        'code' => '  welcome10 ',
        'value_type' => 'percent',
        'value_amount' => 10,
        'usage_limit' => 50,
        'rules_json' => [
            'minimum_purchase_amount' => 1500,
            'applicable_product_ids' => [$product->id],
            'applicable_collection_ids' => [$collection->id],
            'customer_eligibility' => 'all',
            'once_per_customer' => true,
        ],
    ]);
    $discountData = $created->getData(true);
    $discount = Discount::query()->findOrFail($discountData['id']);

    expect($created->getStatusCode())->toBe(201)
        ->and($discountData)->toMatchArray([
            'store_id' => $store->id,
            'type' => 'code',
            'code' => 'WELCOME10',
            'value_type' => 'percent',
            'value_amount' => 10,
            'usage_limit' => 50,
            'usage_count' => 0,
            'rules_json' => [
                'minimum_purchase_amount' => 1500,
                'applicable_product_ids' => [$product->id],
                'applicable_collection_ids' => [$collection->id],
                'customer_eligibility' => 'all',
                'once_per_customer' => true,
            ],
        ])
        ->and($discount->type)->toBe('percentage')
        ->and($discount->minimum_subtotal_amount)->toBe(1500)
        ->and($discount->rules_json['product_ids'])->toBe([$product->id])
        ->and($discount->rules_json['collection_ids'])->toBe([$collection->id])
        ->and($discount->rules_json['one_per_customer'])->toBeTrue();

    $automatic = invokeCollectionsDiscountsAdminAction('createDiscount', $store->id, input: [
        'type' => 'automatic',
        'value_type' => 'free_shipping',
        'value_amount' => 0,
        'starts_at' => now()->addDay()->toISOString(),
    ]);
    expect($automatic->getStatusCode())->toBe(201)
        ->and($automatic->getData(true)['type'])->toBe('automatic')
        ->and($automatic->getData(true)['code'])->toBeNull()
        ->and($automatic->getData(true)['value_type'])->toBe('free_shipping')
        ->and($automatic->getData(true)['value_amount'])->toBe(0);

    $expired = invokeCollectionsDiscountsAdminAction('createDiscount', $store->id, input: [
        'type' => 'code',
        'code' => 'EXPIRED10',
        'value_type' => 'fixed',
        'value_amount' => 100,
        'starts_at' => now()->subDays(3)->toISOString(),
        'ends_at' => now()->subDay()->toISOString(),
    ]);
    $largeFixed = invokeCollectionsDiscountsAdminAction('createDiscount', $store->id, input: [
        'type' => 'code',
        'code' => 'LARGEFIXED',
        'value_type' => 'fixed',
        'value_amount' => 500,
        'starts_at' => now()->addDays(5)->toISOString(),
    ]);

    expect(invokeCollectionsDiscountsAdminAction('discounts', $store->id, input: ['type' => 'automatic', 'status' => 'scheduled'], method: 'GET')->getData(true)['data'][0]['id'])
        ->toBe($automatic->getData(true)['id'])
        ->and(invokeCollectionsDiscountsAdminAction('discounts', $store->id, input: ['status' => 'expired'], method: 'GET')->getData(true)['data'][0]['id'])
        ->toBe($expired->getData(true)['id']);

    expect(fn () => invokeCollectionsDiscountsAdminAction('updateDiscount', $store->id, ['discountId' => $largeFixed->getData(true)['id']], [
        'value_type' => 'percent',
    ], 'PUT'))->toThrow(ValidationException::class);

    expect(fn () => invokeCollectionsDiscountsAdminAction('createDiscount', $store->id, input: [
        'type' => 'code',
        'code' => 'TOO-MUCH',
        'value_type' => 'percent',
        'value_amount' => 101,
    ]))->toThrow(ValidationException::class);

    expect(fn () => invokeCollectionsDiscountsAdminAction('createDiscount', $store->id, input: [
        'type' => 'code',
        'code' => 'BAD-DATES',
        'value_type' => 'fixed',
        'value_amount' => 100,
        'starts_at' => now()->addDay()->toISOString(),
        'ends_at' => now()->toISOString(),
    ]))->toThrow(ValidationException::class);

    expect(fn () => invokeCollectionsDiscountsAdminAction('createDiscount', $store->id, input: [
        'type' => 'code',
        'code' => 'BAD-RULES',
        'value_type' => 'fixed',
        'value_amount' => 100,
        'rules_json' => ['unsupported' => true],
    ]))->toThrow(ValidationException::class);

    expect(fn () => invokeCollectionsDiscountsAdminAction('createDiscount', $store->id, input: [
        'type' => 'code',
        'code' => 'WELCOME10',
        'value_type' => 'fixed',
        'value_amount' => 500,
    ]))->toThrow(ValidationException::class);

    expect(fn () => invokeCollectionsDiscountsAdminAction('createDiscount', $store->id, input: [
        'type' => 'code',
        'code' => 'CROSS-TENANT',
        'value_type' => 'fixed',
        'value_amount' => 500,
        'rules_json' => ['applicable_product_ids' => [$otherProduct->id]],
    ]))->toThrow(ValidationException::class);

    $listed = invokeCollectionsDiscountsAdminAction('discounts', $store->id, input: [
        'type' => 'code',
        'status' => 'active',
        'per_page' => 1,
    ], method: 'GET')->getData(true);
    expect($listed['data'])->toHaveCount(1)
        ->and($listed['data'][0]['id'])->toBe($discount->id)
        ->and($listed['meta'])->toBe(['current_page' => 1, 'per_page' => 1, 'total' => 1, 'last_page' => 1]);

    $updated = invokeCollectionsDiscountsAdminAction('updateDiscount', $store->id, ['discountId' => $discount->id], [
        'rules_json' => ['minimum_purchase_amount' => 2500],
        'usage_limit' => null,
    ], 'PUT');
    expect($updated->getStatusCode())->toBe(200)
        ->and($updated->getData(true)['rules_json']['minimum_purchase_amount'])->toBe(2500)
        ->and($updated->getData(true)['rules_json']['applicable_product_ids'])->toBe([$product->id])
        ->and($updated->getData(true)['usage_limit'])->toBeNull()
        ->and($discount->fresh()->minimum_subtotal_amount)->toBe(2500);

    expect(fn () => invokeCollectionsDiscountsAdminAction('updateDiscount', $store->id, ['discountId' => $discount->id], [
        'code' => 'CHANGED',
    ], 'PUT'))->toThrow(ValidationException::class);

    expect(fn () => invokeCollectionsDiscountsAdminAction('updateDiscount', $store->id, ['discountId' => $discount->id + 1000], [], 'PUT'))
        ->toThrow(ModelNotFoundException::class);

    $deleted = invokeCollectionsDiscountsAdminAction('deleteDiscount', $store->id, ['discountId' => $discount->id], [], 'DELETE');
    expect($deleted->getStatusCode())->toBe(200)
        ->and($deleted->getData(true))->toBe(['message' => 'Discount deleted'])
        ->and(Discount::query()->whereKey($discount->id)->exists())->toBeFalse();
});

it('allows support reads but rejects collection and discount writes', function () {
    $store = shopStore();
    $user = User::factory()->create();
    attachStoreRole($user, $store, 'support');

    expect(fn () => invokeCollectionsDiscountsAdminAction('createCollection', $store->id, input: [
        'title' => 'Forbidden collection',
        'type' => 'manual',
    ]))->toThrow(AuthorizationException::class);

    expect(fn () => invokeCollectionsDiscountsAdminAction('createDiscount', $store->id, input: [
        'type' => 'automatic',
        'value_type' => 'percent',
        'value_amount' => 10,
    ]))->toThrow(AuthorizationException::class);
});

it('enforces the documented API token abilities on collection and discount routes', function () {
    $store = shopStore();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    $readToken = adminApiCrudToken($user, $store, ['read-collections', 'read-discounts']);
    $collectionWriteToken = adminApiCrudToken($user, $store, ['write-collections']);
    $discountWriteToken = adminApiCrudToken($user, $store, ['write-discounts']);
    $basePath = "/api/admin/v1/stores/{$store->id}";

    $this->withToken($readToken)->getJson("{$basePath}/collections")
        ->assertOk()
        ->assertJsonPath('data', [])
        ->assertJsonPath('meta', ['current_page' => 1, 'per_page' => 25, 'total' => 0, 'last_page' => 1]);
    $this->withToken($readToken)->getJson("{$basePath}/discounts")
        ->assertOk()
        ->assertJsonPath('data', [])
        ->assertJsonPath('meta.per_page', 25);
    $this->withToken($readToken)->postJson("{$basePath}/collections", [
        'title' => 'Read only',
        'type' => 'manual',
    ])->assertForbidden();
    $this->withToken($collectionWriteToken)->getJson("{$basePath}/collections")->assertForbidden();

    $collection = $this->withToken($collectionWriteToken)->postJson("{$basePath}/collections", [
        'title' => 'Route tested collection',
        'type' => 'manual',
    ])->assertCreated()
        ->assertJsonPath('title', 'Route tested collection')
        ->assertJsonPath('handle', 'route-tested-collection')
        ->json();
    $this->withToken($collectionWriteToken)->putJson("{$basePath}/collections/{$collection['id']}", [
        'status' => 'archived',
    ])->assertOk()->assertJsonPath('status', 'archived');
    $this->withToken($collectionWriteToken)->deleteJson("{$basePath}/collections/{$collection['id']}")
        ->assertOk()->assertJsonPath('message', 'Collection deleted');

    $this->withToken($collectionWriteToken)->postJson("{$basePath}/discounts", [
        'type' => 'automatic',
        'value_type' => 'percent',
        'value_amount' => 10,
    ])->assertForbidden();
    $discount = $this->withToken($discountWriteToken)->postJson("{$basePath}/discounts", [
        'type' => 'code',
        'code' => 'ROUTE10',
        'value_type' => 'percent',
        'value_amount' => 10,
    ])->assertCreated()
        ->assertJsonPath('type', 'code')
        ->assertJsonPath('code', 'ROUTE10')
        ->json();
    $this->withToken($discountWriteToken)->putJson("{$basePath}/discounts/{$discount['id']}", [
        'usage_limit' => 10,
    ])->assertOk()->assertJsonPath('usage_limit', 10);
    $this->withToken($discountWriteToken)->deleteJson("{$basePath}/discounts/{$discount['id']}")
        ->assertOk()->assertJsonPath('message', 'Discount deleted');
});
