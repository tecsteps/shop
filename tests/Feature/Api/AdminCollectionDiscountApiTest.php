<?php

use App\Enums\CollectionStatus;
use App\Enums\DiscountStatus;
use App\Models\Collection as ProductCollection;
use App\Models\Discount;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\ApiTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
    $this->seed();
    $this->store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $this->otherStore = Store::query()->where('handle', 'acme-electronics')->firstOrFail();
    $this->user = User::query()->where('email', 'admin@example.com')->firstOrFail();
});

function adminCatalogTokenFor($test, array $abilities): string
{
    return app(ApiTokenService::class)->create($test->store, $test->user, 'Catalog API test', $abilities)['plain_text_token'];
}

test('admin collection api lists creates updates and deletes collections', function (): void {
    $product = Product::query()
        ->where('store_id', $this->store->id)
        ->where('handle', 'linen-shirt')
        ->firstOrFail();

    $indexUrl = route('api.admin.collections.index', $this->store);

    $this->getJson($indexUrl)->assertUnauthorized();

    $this->withToken(adminCatalogTokenFor($this, ['write-collections']))
        ->getJson($indexUrl)
        ->assertForbidden();

    $this->withToken(adminCatalogTokenFor($this, ['read-collections']))
        ->getJson($indexUrl.'?query=summer&per_page=5')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Summer Essentials')
        ->assertJsonPath('data.0.store_id', $this->store->id)
        ->assertJsonPath('meta.per_page', 5);

    $response = $this->withToken(adminCatalogTokenFor($this, ['write-collections']))
        ->postJson(route('api.admin.collections.store', $this->store), [
            'title' => 'API Winter Collection',
            'description_html' => '<p>Warm layers<script>alert(1)</script></p>',
            'type' => 'manual',
            'status' => CollectionStatus::Active->value,
            'product_ids' => [$product->id],
        ])
        ->assertCreated()
        ->assertJsonPath('data.handle', 'api-winter-collection')
        ->assertJsonPath('data.products.0.id', $product->id);

    $collection = ProductCollection::query()->whereKey($response->json('data.id'))->firstOrFail();

    expect($collection->description_html)->toBe('<p>Warm layers</p>')
        ->and($collection->products()->whereKey($product->id)->exists())->toBeTrue();

    $this->withToken(adminCatalogTokenFor($this, ['write-collections']))
        ->putJson(route('api.admin.collections.update', [$this->store, $collection]), [
            'title' => 'API Winter Updated',
            'status' => CollectionStatus::Draft->value,
            'product_ids' => [],
        ])
        ->assertOk()
        ->assertJsonPath('data.title', 'API Winter Updated')
        ->assertJsonPath('data.status', CollectionStatus::Draft->value)
        ->assertJsonPath('data.products_count', 0);

    $this->withToken(adminCatalogTokenFor($this, ['write-collections']))
        ->deleteJson(route('api.admin.collections.destroy', [$this->store, $collection]))
        ->assertOk()
        ->assertJsonPath('message', 'Collection deleted');

    expect(ProductCollection::query()->whereKey($collection->id)->exists())->toBeFalse();
});

test('admin discount api lists creates updates and deletes discounts', function (): void {
    $indexUrl = route('api.admin.discounts.index', $this->store);

    $this->getJson($indexUrl)->assertUnauthorized();

    $this->withToken(adminCatalogTokenFor($this, ['write-discounts']))
        ->getJson($indexUrl)
        ->assertForbidden();

    $this->withToken(adminCatalogTokenFor($this, ['read-discounts']))
        ->getJson($indexUrl.'?type=code&per_page=5')
        ->assertOk()
        ->assertJsonFragment(['code' => 'WELCOME10'])
        ->assertJsonPath('meta.per_page', 5);

    $response = $this->withToken(adminCatalogTokenFor($this, ['write-discounts']))
        ->postJson(route('api.admin.discounts.store', $this->store), [
            'type' => 'code',
            'code' => 'api20',
            'value_type' => 'percent',
            'value_amount' => 20,
            'starts_at' => '2026-06-01T00:00:00Z',
            'ends_at' => '2026-08-31T23:59:59Z',
            'usage_limit' => 100,
            'rules_json' => ['minimum_purchase_amount' => 5000],
            'status' => DiscountStatus::Active->value,
        ])
        ->assertCreated()
        ->assertJsonPath('data.code', 'API20')
        ->assertJsonPath('data.value_amount', 20)
        ->assertJsonPath('data.rules_json.minimum_purchase_amount', 5000);

    $discount = Discount::query()->whereKey($response->json('data.id'))->firstOrFail();

    $this->withToken(adminCatalogTokenFor($this, ['write-discounts']))
        ->putJson(route('api.admin.discounts.update', [$this->store, $discount]), [
            'value_amount' => 25,
            'status' => DiscountStatus::Disabled->value,
        ])
        ->assertOk()
        ->assertJsonPath('data.value_amount', 25)
        ->assertJsonPath('data.status', DiscountStatus::Disabled->value);

    $this->withToken(adminCatalogTokenFor($this, ['write-discounts']))
        ->deleteJson(route('api.admin.discounts.destroy', [$this->store, $discount]))
        ->assertOk()
        ->assertJsonPath('message', 'Discount deleted');

    expect(Discount::query()->whereKey($discount->id)->exists())->toBeFalse();
});

test('admin collection and discount api enforce store scoped token access', function (): void {
    $otherStoreCollection = ProductCollection::query()
        ->where('store_id', $this->otherStore->id)
        ->firstOrFail();
    $otherStoreDiscount = Discount::query()
        ->where('store_id', $this->otherStore->id)
        ->first();

    $this->withToken(adminCatalogTokenFor($this, ['read-collections']))
        ->getJson(route('api.admin.collections.index', $this->otherStore))
        ->assertForbidden();

    $this->withToken(adminCatalogTokenFor($this, ['write-collections']))
        ->putJson(route('api.admin.collections.update', [$this->store, $otherStoreCollection]), [
            'title' => 'Cross tenant',
        ])
        ->assertNotFound();

    if ($otherStoreDiscount instanceof Discount) {
        $this->withToken(adminCatalogTokenFor($this, ['write-discounts']))
            ->deleteJson(route('api.admin.discounts.destroy', [$this->store, $otherStoreDiscount]))
            ->assertNotFound();
    }
});
