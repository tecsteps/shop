<?php

use App\Enums\MediaStatus;
use App\Models\ProductMedia;
use App\Services\ProductService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CatalogTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

function createMediaProduct(): array
{
    ['store' => $store, 'user' => $user] = test()->createStoreContext();
    $product = app(ProductService::class)->create($store, CatalogTestHelper::productPayload());

    return ['store' => $store, 'user' => $user, 'product' => $product];
}

it('uploads an image for a product', function () {
    ['store' => $store, 'user' => $user, 'product' => $product] = createMediaProduct();

    $response = $this->postJson("/api/admin/v1/stores/{$store->id}/products/{$product->id}/media", [
        'file' => UploadedFile::fake()->image('shirt.jpg', 800, 1000),
        'alt_text' => 'Classic T-Shirt in Blue',
    ], CatalogTestHelper::adminHeaders($user));

    $response->assertCreated();
    $response->assertJsonPath('data.type', 'image');
    $response->assertJsonPath('data.alt_text', 'Classic T-Shirt in Blue');
    $response->assertJsonPath('data.status', 'ready');
    $response->assertJsonPath('data.position', 0);
    expect($response->json('data.storage_key'))->toStartWith("media/{$product->id}/");
    expect($response->json('data.url'))->toContain('/storage/');

    $media = ProductMedia::query()->findOrFail($response->json('data.id'));
    expect($media->status)->toBe(MediaStatus::Ready);
    expect($media->mime_type)->toBe('image/jpeg');
    expect($media->byte_size)->toBeGreaterThan(0);
    expect($media->width)->toBe(800);
    expect($media->height)->toBe(1000);

    Storage::disk('public')->assertExists($media->storage_key);
    Storage::disk('public')->assertExists("media/{$product->id}/{$media->id}/thumbnail.jpg");
    Storage::disk('public')->assertExists("media/{$product->id}/{$media->id}/large.jpg");
});

it('assigns incremental positions to uploaded media', function () {
    ['store' => $store, 'user' => $user, 'product' => $product] = createMediaProduct();
    $headers = CatalogTestHelper::adminHeaders($user);

    $first = $this->postJson("/api/admin/v1/stores/{$store->id}/products/{$product->id}/media", [
        'file' => UploadedFile::fake()->image('one.jpg'),
    ], $headers);
    $second = $this->postJson("/api/admin/v1/stores/{$store->id}/products/{$product->id}/media", [
        'file' => UploadedFile::fake()->image('two.jpg'),
    ], $headers);

    $first->assertCreated()->assertJsonPath('data.position', 0);
    $second->assertCreated()->assertJsonPath('data.position', 1);
});

it('rejects non-image file types', function () {
    ['store' => $store, 'user' => $user, 'product' => $product] = createMediaProduct();

    $this->postJson("/api/admin/v1/stores/{$store->id}/products/{$product->id}/media", [
        'file' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
    ], CatalogTestHelper::adminHeaders($user))
        ->assertUnprocessable()->assertJsonValidationErrors(['file']);

    expect(ProductMedia::query()->count())->toBe(0);
});

it('requires a file for media upload', function () {
    ['store' => $store, 'user' => $user, 'product' => $product] = createMediaProduct();

    $this->postJson("/api/admin/v1/stores/{$store->id}/products/{$product->id}/media", [
        'alt_text' => 'Missing file',
    ], CatalogTestHelper::adminHeaders($user))
        ->assertUnprocessable()->assertJsonValidationErrors(['file']);
});

it('updates media alt text and position', function () {
    ['store' => $store, 'user' => $user, 'product' => $product] = createMediaProduct();
    $headers = CatalogTestHelper::adminHeaders($user);

    $created = $this->postJson("/api/admin/v1/stores/{$store->id}/products/{$product->id}/media", [
        'file' => UploadedFile::fake()->image('shirt.jpg'),
    ], $headers);
    $created->assertCreated();

    $response = $this->putJson(
        "/api/admin/v1/stores/{$store->id}/products/{$product->id}/media/{$created->json('data.id')}",
        ['alt_text' => 'Updated alt', 'position' => 3],
        $headers
    );

    $response->assertOk();
    $response->assertJsonPath('data.alt_text', 'Updated alt');
    $response->assertJsonPath('data.position', 3);
});

it('deletes media and removes files from storage', function () {
    ['store' => $store, 'user' => $user, 'product' => $product] = createMediaProduct();
    $headers = CatalogTestHelper::adminHeaders($user);

    $created = $this->postJson("/api/admin/v1/stores/{$store->id}/products/{$product->id}/media", [
        'file' => UploadedFile::fake()->image('shirt.jpg'),
    ], $headers);
    $created->assertCreated();

    $media = ProductMedia::query()->findOrFail($created->json('data.id'));
    Storage::disk('public')->assertExists($media->storage_key);

    $this->deleteJson(
        "/api/admin/v1/stores/{$store->id}/products/{$product->id}/media/{$media->id}",
        [], $headers
    )->assertOk()->assertJsonPath('message', 'Media deleted');

    expect(ProductMedia::query()->whereKey($media->id)->exists())->toBeFalse();
    Storage::disk('public')->assertMissing($media->storage_key);
    expect(Storage::disk('public')->allFiles("media/{$product->id}/{$media->id}"))->toBe([]);
});

it('returns 404 for foreign media records', function () {
    ['store' => $storeA, 'user' => $userA] = $this->createStoreContext();
    ['store' => $storeB] = $this->createStoreContext();
    $headersA = CatalogTestHelper::adminHeaders($userA);

    $productA = app(ProductService::class)->create($storeA, CatalogTestHelper::productPayload());
    $productB = app(ProductService::class)->create($storeB, CatalogTestHelper::productPayload());
    $mediaB = $productB->productMedia()->create([
        'type' => 'image', 'storage_key' => 'media/foreign.jpg', 'position' => 0, 'status' => 'ready',
    ]);

    $this->postJson("/api/admin/v1/stores/{$storeA->id}/products/{$productB->id}/media", [
        'file' => UploadedFile::fake()->image('shirt.jpg'),
    ], $headersA)->assertNotFound();

    $this->deleteJson("/api/admin/v1/stores/{$storeA->id}/products/{$productA->id}/media/{$mediaB->id}", [], $headersA)->assertNotFound();
});

it('enforces authentication and abilities on media endpoints', function () {
    ['store' => $store, 'user' => $user, 'product' => $product] = createMediaProduct();

    $this->postJson("/api/admin/v1/stores/{$store->id}/products/{$product->id}/media", [
        'file' => UploadedFile::fake()->image('shirt.jpg'),
    ])->assertUnauthorized();

    $this->app['auth']->forgetGuards();
    $this->postJson("/api/admin/v1/stores/{$store->id}/products/{$product->id}/media", [
        'file' => UploadedFile::fake()->image('shirt.jpg'),
    ], CatalogTestHelper::adminHeaders($user, ['read-products']))->assertForbidden();
});
