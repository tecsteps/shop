<?php

use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Str;

function productApiToken(User $user, Store $store): string
{
    $plainTextToken = 'shop_'.Str::random(64);
    $user->tokens()->create([
        'store_id' => $store->getKey(),
        'name' => 'product-api-test',
        'token' => hash('sha256', $plainTextToken),
        'abilities' => ['read-products', 'write-products'],
        'expires_at' => now()->addHour(),
    ]);

    return $plainTextToken;
}

it('creates a complete product resource with options, variants, inventory, and collections', function () {
    $store = shopStore();
    $collection = Collection::query()->create([
        'store_id' => $store->id,
        'title' => 'Summer',
        'handle' => 'summer',
        'type' => 'manual',
        'status' => 'active',
    ]);
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    $token = productApiToken($user, $store);

    $response = $this->withToken($token)->postJson("/api/admin/v1/stores/{$store->id}/products", [
        'title' => 'Classic Tee',
        'handle' => 'classic-tee',
        'vendor' => 'Acme',
        'product_type' => 'Apparel',
        'status' => 'active',
        'tags' => ['organic', 'cotton'],
        'options' => [
            ['name' => 'Color', 'position' => 1],
            ['name' => 'Size', 'position' => 2],
        ],
        'variants' => [
            [
                'sku' => 'TEE-BLU-S',
                'barcode' => '123456',
                'price_amount' => 2500,
                'compare_at_amount' => 3500,
                'currency' => 'EUR',
                'position' => 2,
                'weight_g' => 200,
                'requires_shipping' => true,
                'is_default' => true,
                'option_values' => [
                    ['option_name' => 'Color', 'value' => 'Blue'],
                    ['option_name' => 'Size', 'value' => 'Small'],
                ],
                'inventory' => ['quantity_on_hand' => 50, 'policy' => 'deny'],
            ],
            [
                'sku' => 'TEE-RED-S',
                'price_amount' => 2600,
                'position' => 1,
                'option_values' => [
                    ['option_name' => 'Color', 'value' => 'Red'],
                    ['option_name' => 'Size', 'value' => 'Small'],
                ],
                'inventory' => ['quantity_on_hand' => 12, 'policy' => 'continue'],
            ],
        ],
        'collections' => [$collection->id],
    ])->assertCreated()
        ->assertJsonPath('data.title', 'Classic Tee')
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.options.0.name', 'Color')
        ->assertJsonPath('data.options.0.values.1.value', 'Red')
        ->assertJsonPath('data.variants.0.sku', 'TEE-RED-S')
        ->assertJsonPath('data.variants.0.position', 1)
        ->assertJsonPath('data.variants.0.inventory.policy', 'continue')
        ->assertJsonPath('data.variants.1.sku', 'TEE-BLU-S')
        ->assertJsonPath('data.variants.1.position', 2)
        ->assertJsonPath('data.variants.1.option_values.1.value', 'Small')
        ->assertJsonPath('data.variants.1.inventory.quantity_on_hand', 50)
        ->assertJsonPath('data.collections.0.id', $collection->id);

    expect(Product::query()->where('store_id', $store->id)->where('handle', 'classic-tee')->exists())->toBeTrue();
    expect($response->json('data.variants'))->toHaveCount(2);
});

it('filters and paginates products by title, vendor, sku, collection, and requested sort', function () {
    $store = shopStore();
    $collection = Collection::query()->create([
        'store_id' => $store->id,
        'title' => 'Summer',
        'handle' => 'summer',
        'type' => 'manual',
        'status' => 'active',
    ]);
    $product = Product::factory()->create(['store_id' => $store->id, 'title' => 'Cotton Tee', 'vendor' => 'Acme']);
    $variant = ProductVariant::factory()->for($product)->create(['sku' => 'COTTON-TEE-RED']);
    $variant->inventoryItem()->create(['store_id' => $store->id, 'quantity_on_hand' => 8, 'policy' => 'deny']);
    $product->collections()->attach($collection->id);
    Product::factory()->create(['store_id' => $store->id, 'title' => 'Linen Pants', 'vendor' => 'Other']);
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    $token = productApiToken($user, $store);

    $this->withToken($token)->getJson("/api/admin/v1/stores/{$store->id}/products?query=COTTON-TEE-RED&collection_id={$collection->id}&sort=title_asc&page=1&per_page=1")
        ->assertOk()
        ->assertJsonPath('meta.current_page', 1)
        ->assertJsonPath('meta.per_page', 1)
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.title', 'Cotton Tee')
        ->assertJsonPath('data.0.variants_count', 1)
        ->assertJsonPath('data.0.total_inventory', 8);

    $this->withToken($token)->getJson("/api/admin/v1/stores/{$store->id}/products?query=Cotton")
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.title', 'Cotton Tee');

    $this->withToken($token)->getJson("/api/admin/v1/stores/{$store->id}/products?query=Acme")
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.vendor', 'Acme');
});

it('updates variant inventory and archives a product through the documented delete endpoint', function () {
    $store = shopStore();
    $productData = shopProduct($store, price: 2500, stock: 10);
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    $token = productApiToken($user, $store);
    $baseUrl = "/api/admin/v1/stores/{$store->id}/products/{$productData['product']->id}";

    $this->withToken($token)->putJson($baseUrl, [
        'title' => 'Updated Shirt',
        'variants' => [[
            'id' => $productData['variant']->id,
            'sku' => 'UPDATED-SKU',
            'price_amount' => 2700,
            'compare_at_amount' => 3500,
            'inventory' => ['quantity_on_hand' => 16, 'policy' => 'continue'],
        ]],
    ])
        ->assertOk()
        ->assertJsonPath('data.title', 'Updated Shirt')
        ->assertJsonPath('data.variants.0.sku', 'UPDATED-SKU')
        ->assertJsonPath('data.variants.0.price_amount', 2700)
        ->assertJsonPath('data.variants.0.inventory.quantity_on_hand', 16)
        ->assertJsonPath('data.variants.0.inventory.policy', 'continue');

    $this->withToken($token)->putJson($baseUrl, [
        'variants' => [[
            'id' => $productData['variant']->id,
            'inventory' => ['quantity_on_hand' => 18],
        ]],
    ])
        ->assertOk()
        ->assertJsonPath('data.variants.0.sku', 'UPDATED-SKU')
        ->assertJsonPath('data.variants.0.price_amount', 2700)
        ->assertJsonPath('data.variants.0.inventory.quantity_on_hand', 18)
        ->assertJsonPath('data.variants.0.inventory.policy', 'continue');

    $this->withToken($token)->putJson($baseUrl, [
        'variants' => [[
            'id' => $productData['variant']->id,
            'price_amount' => 3600,
            'compare_at_amount' => null,
        ]],
    ])
        ->assertOk()
        ->assertJsonPath('data.variants.0.price_amount', 3600)
        ->assertJsonPath('data.variants.0.compare_at_amount', null);

    $this->withToken($token)->deleteJson($baseUrl)
        ->assertOk()
        ->assertJsonPath('data.id', $productData['product']->id)
        ->assertJsonPath('data.status', 'archived');
});

it('returns validation errors for malformed variants and hides products owned by other stores', function () {
    $store = shopStore();
    $otherStore = shopStore('other-products-api.test');
    $otherProduct = Product::factory()->create(['store_id' => $otherStore->id]);
    $user = User::factory()->create();
    $user->stores()->attach([$store->id => ['role' => 'owner'], $otherStore->id => ['role' => 'owner']]);
    $token = productApiToken($user, $store);
    $baseUrl = "/api/admin/v1/stores/{$store->id}/products";

    $this->withToken($token)->postJson($baseUrl, ['title' => 'Bad', 'variants' => 'not-an-array'])
        ->assertUnprocessable()
        ->assertInvalid(['variants']);

    $this->withToken($token)->getJson("{$baseUrl}/{$otherProduct->id}")->assertNotFound();
    $this->withToken($token)->putJson("{$baseUrl}/{$otherProduct->id}", ['title' => 'Changed'])->assertNotFound();
    $this->withToken($token)->deleteJson("{$baseUrl}/{$otherProduct->id}")->assertNotFound();
    expect($otherProduct->refresh()->title)->not->toBe('Changed');
});

it('rejects duplicate store skus, invalid collections, and invalid compare-at prices', function () {
    $store = shopStore();
    $otherStore = shopStore('other-products.test');
    $existing = shopProduct($store);
    $foreignCollection = Collection::query()->create([
        'store_id' => $otherStore->id,
        'title' => 'Foreign',
        'handle' => 'foreign',
        'type' => 'manual',
        'status' => 'active',
    ]);
    $user = User::factory()->create();
    $user->stores()->attach([$store->id => ['role' => 'owner'], $otherStore->id => ['role' => 'owner']]);
    $token = productApiToken($user, $store);
    $url = "/api/admin/v1/stores/{$store->id}/products";
    $payload = [
        'title' => 'New Shirt',
        'variants' => [['sku' => $existing['variant']->sku, 'price_amount' => 2500, 'is_default' => true]],
    ];

    $this->withToken($token)->postJson($url, $payload)->assertUnprocessable()->assertInvalid(['variants.sku']);

    $payload['variants'][0]['sku'] = 'NEW-SKU';
    $payload['variants'][0]['compare_at_amount'] = 2000;
    $payload['collections'] = [$foreignCollection->id];
    $this->withToken($token)->postJson($url, $payload)->assertUnprocessable()->assertInvalid(['collections.0']);

    unset($payload['collections']);
    $this->withToken($token)->postJson($url, $payload)->assertUnprocessable()->assertInvalid(['variants.0.compare_at_amount']);
});
