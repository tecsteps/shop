<?php

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Jobs\ProcessMediaUpload;
use App\Models\Product;
use App\Models\ProductMedia;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $ctx = createStoreContext();
    $this->store = $ctx['store'];
    Storage::fake('public');
});

test('uploads an image for a product', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);

    $media = ProductMedia::factory()->processing()->create([
        'product_id' => $product->id,
        'type' => MediaType::Image,
        'storage_key' => 'products/test-image.jpg',
        'mime_type' => 'image/jpeg',
        'position' => 0,
    ]);

    expect($media->status)->toBe(MediaStatus::Processing)
        ->and($media->product_id)->toBe($product->id);
});

test('processes uploaded image and generates variants', function () {
    Queue::fake();

    $product = Product::factory()->create(['store_id' => $this->store->id]);

    $media = ProductMedia::factory()->processing()->create([
        'product_id' => $product->id,
        'storage_key' => 'products/test-image.jpg',
        'mime_type' => 'image/jpeg',
    ]);

    ProcessMediaUpload::dispatch($media);

    Queue::assertPushed(ProcessMediaUpload::class, function ($job) use ($media) {
        return $job->media->id === $media->id;
    });
});

test('rejects non-image file types', function () {
    $invalidMimeTypes = ['text/plain', 'application/pdf', 'application/zip'];

    foreach ($invalidMimeTypes as $mimeType) {
        expect(in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4']))->toBeFalse();
    }
});

test('sets alt text on media', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);

    $media = ProductMedia::factory()->create([
        'product_id' => $product->id,
        'alt_text' => null,
    ]);

    $media->update(['alt_text' => 'A beautiful product photo']);

    expect($media->fresh()->alt_text)->toBe('A beautiful product photo');
});

test('reorders media positions', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);

    $media1 = ProductMedia::factory()->create(['product_id' => $product->id, 'position' => 0]);
    $media2 = ProductMedia::factory()->create(['product_id' => $product->id, 'position' => 1]);
    $media3 = ProductMedia::factory()->create(['product_id' => $product->id, 'position' => 2]);

    $media1->update(['position' => 2]);
    $media2->update(['position' => 0]);
    $media3->update(['position' => 1]);

    expect($media1->fresh()->position)->toBe(2)
        ->and($media2->fresh()->position)->toBe(0)
        ->and($media3->fresh()->position)->toBe(1);
});

test('deletes media and removes file from storage', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);

    Storage::disk('public')->put('products/to-delete.jpg', 'fake-image-content');

    $media = ProductMedia::factory()->create([
        'product_id' => $product->id,
        'storage_key' => 'products/to-delete.jpg',
    ]);

    Storage::disk('public')->delete($media->storage_key);
    $media->delete();

    expect(ProductMedia::find($media->id))->toBeNull()
        ->and(Storage::disk('public')->exists('products/to-delete.jpg'))->toBeFalse();
});
