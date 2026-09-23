<?php

use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

function productMediaApiToken(User $user, int $storeId, array $abilities): string
{
    $plainTextToken = 'shop_'.Str::random(64);
    $user->tokens()->create([
        'store_id' => $storeId,
        'name' => 'media-upload-test',
        'token' => hash('sha256', $plainTextToken),
        'abilities' => $abilities,
        'expires_at' => now()->addHour(),
    ]);

    return $plainTextToken;
}

function tinyPngBytes(): string
{
    return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/pQAAAABJRU5ErkJggg==', true);
}

it('creates a store-scoped signed upload and stores verified media once', function () {
    Storage::fake('public');
    $store = shopStore();
    $product = Product::factory()->create(['store_id' => $store->id]);
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    $token = productMediaApiToken($user, $store->id, ['write-products']);

    $presign = $this->withToken($token)->postJson("/api/admin/v1/stores/{$store->id}/products/{$product->id}/media/presign-upload", [
        'filename' => 'small.png',
        'content_type' => 'image/png',
        'byte_size' => strlen(tinyPngBytes()),
    ])->assertCreated()
        ->assertJsonPath('method', 'PUT')
        ->assertJsonPath('headers.Content-Type', 'image/png');

    $mediaId = $presign->json('media_id');
    $media = ProductMedia::query()->findOrFail($mediaId);
    expect($media->storage_key)->toStartWith("stores/{$store->id}/products/{$product->id}/media/")
        ->and($media->status)->toBe('processing');

    $uploadUrl = $presign->json('upload_url');
    parse_str((string) parse_url($uploadUrl, PHP_URL_QUERY), $query);
    $uploadPath = (string) parse_url($uploadUrl, PHP_URL_PATH);
    $this->call('PUT', $uploadPath.'?'.http_build_query($query), [], [], [], [
        'HTTP_HOST' => parse_url($uploadUrl, PHP_URL_HOST),
        'CONTENT_TYPE' => 'image/png',
        'HTTP_ACCEPT' => 'application/json',
    ], tinyPngBytes())->assertOk()
        ->assertJsonPath('media.status', 'ready');

    Storage::disk('public')->assertExists($media->storage_key);
    $this->call('PUT', $uploadPath.'?'.http_build_query($query), [], [], [], [
        'HTTP_HOST' => parse_url($uploadUrl, PHP_URL_HOST),
        'CONTENT_TYPE' => 'image/png',
        'HTTP_ACCEPT' => 'application/json',
    ], tinyPngBytes())->assertConflict();
});

it('rejects invalid media metadata, missing abilities, and tampered upload URLs', function () {
    $store = shopStore();
    $product = Product::factory()->create(['store_id' => $store->id]);
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    $readToken = productMediaApiToken($user, $store->id, ['read-products']);

    $this->withToken($readToken)->postJson("/api/admin/v1/stores/{$store->id}/products/{$product->id}/media/presign-upload", [
        'filename' => 'small.png',
        'content_type' => 'image/png',
        'byte_size' => 67,
    ])->assertForbidden();

    $writeToken = productMediaApiToken($user, $store->id, ['write-products']);
    $this->withToken($writeToken)->postJson("/api/admin/v1/stores/{$store->id}/products/{$product->id}/media/presign-upload", [
        'filename' => 'small.jpg',
        'content_type' => 'image/png',
        'byte_size' => 67,
    ])->assertUnprocessable();

    $presign = $this->withToken($writeToken)->postJson("/api/admin/v1/stores/{$store->id}/products/{$product->id}/media/presign-upload", [
        'filename' => 'small.png',
        'content_type' => 'image/png',
        'byte_size' => 67,
    ])->assertCreated();
    $uploadUrl = $presign->json('upload_url').'&tampered=1';
    parse_str((string) parse_url($uploadUrl, PHP_URL_QUERY), $query);
    $uploadPath = (string) parse_url($uploadUrl, PHP_URL_PATH);

    $this->call('PUT', $uploadPath.'?'.http_build_query($query), [], [], [], [
        'HTTP_HOST' => parse_url($uploadUrl, PHP_URL_HOST),
        'CONTENT_TYPE' => 'image/png',
        'HTTP_ACCEPT' => 'application/json',
    ], tinyPngBytes())->assertForbidden();
});

it('applies the configured image size limit when issuing signed upload URLs', function () {
    config()->set('shop.media.image_max_bytes', 66);
    $store = shopStore();
    $product = Product::factory()->create(['store_id' => $store->id]);
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    $token = productMediaApiToken($user, $store->id, ['write-products']);

    $this->withToken($token)->postJson("/api/admin/v1/stores/{$store->id}/products/{$product->id}/media/presign-upload", [
        'filename' => 'small.png',
        'content_type' => 'image/png',
        'byte_size' => 67,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('byte_size');
});
