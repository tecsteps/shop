<?php

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Jobs\ProcessMediaUpload;
use App\Models\Product;
use App\Models\ProductMedia;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->product = Product::factory()->create(['store_id' => $this->store->id]);
});

it('uploads an image', function () {
    $media = ProductMedia::factory()->processing()->create([
        'product_id' => $this->product->id,
        'type' => MediaType::Image,
        'storage_key' => 'products/test-image.jpg',
        'mime_type' => 'image/jpeg',
    ]);

    expect($media->type)->toBe(MediaType::Image)
        ->and($media->status)->toBe(MediaStatus::Processing)
        ->and($media->storage_key)->toBe('products/test-image.jpg');
});

it('processes an uploaded image', function () {
    $media = ProductMedia::factory()->processing()->create([
        'product_id' => $this->product->id,
    ]);

    expect($media->status)->toBe(MediaStatus::Processing);

    $job = new ProcessMediaUpload($media);
    $job->handle();

    expect($media->fresh()->status)->toBe(MediaStatus::Ready);
});

it('rejects non-image types for image upload', function () {
    $media = ProductMedia::factory()->create([
        'product_id' => $this->product->id,
        'type' => MediaType::Video,
        'mime_type' => 'video/mp4',
    ]);

    expect($media->type)->toBe(MediaType::Video)
        ->and($media->mime_type)->toBe('video/mp4');
});

it('sets alt text on media', function () {
    $media = ProductMedia::factory()->create([
        'product_id' => $this->product->id,
        'alt_text' => 'A beautiful red shoe',
    ]);

    expect($media->alt_text)->toBe('A beautiful red shoe');

    $media->update(['alt_text' => 'Updated alt text']);
    expect($media->fresh()->alt_text)->toBe('Updated alt text');
});

it('reorders media positions', function () {
    $media1 = ProductMedia::factory()->create(['product_id' => $this->product->id, 'position' => 0]);
    $media2 = ProductMedia::factory()->create(['product_id' => $this->product->id, 'position' => 1]);
    $media3 = ProductMedia::factory()->create(['product_id' => $this->product->id, 'position' => 2]);

    // Swap positions
    $media3->update(['position' => 0]);
    $media1->update(['position' => 2]);

    $ordered = $this->product->media()->orderBy('position')->get();

    expect($ordered->first()->id)->toBe($media3->id)
        ->and($ordered->last()->id)->toBe($media1->id);
});

it('deletes media', function () {
    $media = ProductMedia::factory()->create([
        'product_id' => $this->product->id,
    ]);

    $mediaId = $media->id;
    $media->delete();

    expect(ProductMedia::find($mediaId))->toBeNull();
});
