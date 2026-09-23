<?php

use App\Jobs\ProcessMediaUpload;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
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
    $image = new Imagick;
    $image->newImage(1, 1, new ImagickPixel('white'));
    $image->setImageFormat('png');
    $bytes = $image->getImageBlob();
    $image->clear();
    $image->destroy();

    return $bytes;
}

it('creates a store-scoped signed upload and stores verified media once', function () {
    Storage::fake('public');
    Queue::fake();
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
        ->assertJsonPath('media.status', 'processing');

    Storage::disk('public')->assertExists($media->storage_key);
    Queue::assertPushed(ProcessMediaUpload::class, fn (ProcessMediaUpload $job): bool => $job->mediaId === $mediaId);
    (new ProcessMediaUpload($mediaId))->handle();
    $media->refresh();
    expect($media->status)->toBe('ready')->and($media->width)->toBe(1)->and($media->height)->toBe(1);

    foreach (['thumbnail', 'small', 'medium', 'large'] as $size) {
        Storage::disk('public')->assertExists("media/{$product->id}/{$mediaId}/{$size}.png");
        Storage::disk('public')->assertExists("media/{$product->id}/{$mediaId}/{$size}.webp");
    }

    expect($media->thumbnail_url)->toContain("media/{$product->id}/{$mediaId}/thumbnail.webp");

    $this->call('PUT', $uploadPath.'?'.http_build_query($query), [], [], [], [
        'HTTP_HOST' => parse_url($uploadUrl, PHP_URL_HOST),
        'CONTENT_TYPE' => 'image/png',
        'HTTP_ACCEPT' => 'application/json',
    ], tinyPngBytes())->assertConflict();
});

it('removes the original and generated image sizes when product media is deleted', function () {
    Storage::fake('public');
    $store = shopStore();
    $product = Product::factory()->create(['store_id' => $store->id]);
    $media = ProductMedia::query()->create([
        'product_id' => $product->id,
        'type' => 'image',
        'storage_key' => 'stores/1/products/1/media/example.png',
        'mime_type' => 'image/png',
        'byte_size' => 10,
        'status' => 'ready',
    ]);
    Storage::disk('public')->put($media->storage_key, 'original');
    Storage::disk('public')->put("media/{$product->id}/{$media->id}/thumbnail.webp", 'thumb');

    $media->delete();

    Storage::disk('public')->assertMissing($media->storage_key);
    Storage::disk('public')->assertMissing("media/{$product->id}/{$media->id}/thumbnail.webp");
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
