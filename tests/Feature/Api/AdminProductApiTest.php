<?php

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductVariant;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->user = $this->context['user'];
    $this->baseUrl = "/api/admin/v1/stores/{$this->store->getKey()}/products";
});

/**
 * Authorization headers for a token with the given abilities.
 *
 * @param  list<string>  $abilities
 * @return array<string, string>
 */
function productApiHeaders(array $abilities = ['read-products', 'write-products']): array
{
    return ['Authorization' => 'Bearer '.test()->user->createToken('test', $abilities)->plainTextToken];
}

it('lists products with authentication', function () {
    Product::factory()->count(3)->for($this->store)->create();

    $this->getJson($this->baseUrl, productApiHeaders())
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'per_page', 'total', 'last_page']]);
});

it('creates a product via API', function () {
    $this->postJson($this->baseUrl, [
        'title' => 'Classic T-Shirt',
        'description_html' => '<p>A comfortable cotton t-shirt.</p>',
        'vendor' => 'Acme Apparel',
        'status' => 'draft',
        'tags' => ['organic', 'cotton'],
        'options' => [
            ['name' => 'Size', 'position' => 1],
        ],
        'variants' => [
            [
                'sku' => 'TSH-S',
                'price_amount' => 2500,
                'is_default' => true,
                'option_values' => [['option_name' => 'Size', 'value' => 'Small']],
                'inventory' => ['quantity_on_hand' => 50, 'policy' => 'deny'],
            ],
            [
                'sku' => 'TSH-M',
                'price_amount' => 2500,
                'option_values' => [['option_name' => 'Size', 'value' => 'Medium']],
            ],
        ],
    ], productApiHeaders())
        ->assertCreated()
        ->assertJsonPath('data.title', 'Classic T-Shirt')
        ->assertJsonPath('data.handle', 'classic-t-shirt')
        ->assertJsonCount(2, 'data.variants')
        ->assertJsonPath('data.variants.0.inventory.quantity_on_hand', 50);

    $this->assertDatabaseHas('products', [
        'store_id' => $this->store->getKey(),
        'title' => 'Classic T-Shirt',
    ]);
});

it('updates a product via API', function () {
    $product = Product::factory()->for($this->store)->create(['title' => 'Old Title']);
    ProductVariant::factory()->asDefault()->priced(2500)->for($product)->create();

    $this->putJson("{$this->baseUrl}/{$product->getKey()}", [
        'title' => 'New Title',
        'tags' => ['bestseller'],
    ], productApiHeaders())
        ->assertOk()
        ->assertJsonPath('data.title', 'New Title')
        ->assertJsonPath('data.tags.0', 'bestseller');

    expect($product->refresh()->title)->toBe('New Title');
});

it('deletes a draft product via API', function () {
    $product = Product::factory()->for($this->store)->create(['status' => ProductStatus::Draft]);

    $this->deleteJson("{$this->baseUrl}/{$product->getKey()}", [], productApiHeaders())
        ->assertOk()
        ->assertJsonPath('data.status', 'archived');

    expect($product->refresh()->status)->toBe(ProductStatus::Archived);
});

it('requires write-products ability for mutations', function () {
    $this->postJson($this->baseUrl, [
        'title' => 'Forbidden Product',
        'variants' => [['sku' => 'FP-1', 'price_amount' => 1000]],
    ], productApiHeaders(['read-products']))
        ->assertForbidden();
});

it('returns 401 without token', function () {
    $this->getJson($this->baseUrl)->assertUnauthorized();
});

it('paginates results', function () {
    Product::factory()->count(25)->for($this->store)->create();

    $this->getJson($this->baseUrl, productApiHeaders())
        ->assertOk()
        ->assertJsonCount(15, 'data')
        ->assertJsonPath('meta.total', 25)
        ->assertJsonPath('meta.per_page', 15)
        ->assertJsonPath('meta.last_page', 2);
});
