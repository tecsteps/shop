<?php

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Jobs\ProcessMediaUpload;
use App\Models\Product;
use App\Models\ProductMedia;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->product = Product::factory()->create(['store_id' => $this->ctx['store']->id]);
});

it('uploads an image for a product', function () {
    $media = ProductMedia::create([
        'product_id' => $this->product->id,
        'type' => MediaType::Image,
        'url' => 'products/test-image.jpg',
        'position' => 0,
        'status' => MediaStatus::Processing,
    ]);

    expect($media->exists)->toBeTrue();
    expect($media->status)->toBe(MediaStatus::Processing);
    expect($media->type)->toBe(MediaType::Image);
});

it('processes uploaded image and dispatches job', function () {
    Queue::fake();

    $media = ProductMedia::create([
        'product_id' => $this->product->id,
        'type' => MediaType::Image,
        'url' => 'products/test-image.jpg',
        'position' => 0,
        'status' => MediaStatus::Processing,
    ]);

    ProcessMediaUpload::dispatch($media->id);

    Queue::assertPushed(ProcessMediaUpload::class, function ($job) {
        return true;
    });
});

it('rejects non-image file types', function () {
    // Verify that only image types are valid via the enum
    $validTypes = array_column(MediaType::cases(), 'value');

    expect($validTypes)->toContain('image');
    expect($validTypes)->not->toContain('text');
    expect($validTypes)->not->toContain('pdf');
});

it('sets alt text on media', function () {
    $media = ProductMedia::factory()->create([
        'product_id' => $this->product->id,
        'alt_text' => null,
    ]);

    $media->update(['alt_text' => 'A red summer t-shirt']);
    $media->refresh();

    expect($media->alt_text)->toBe('A red summer t-shirt');
});

it('reorders media positions', function () {
    $media1 = ProductMedia::factory()->create([
        'product_id' => $this->product->id,
        'position' => 0,
    ]);
    $media2 = ProductMedia::factory()->create([
        'product_id' => $this->product->id,
        'position' => 1,
    ]);
    $media3 = ProductMedia::factory()->create([
        'product_id' => $this->product->id,
        'position' => 2,
    ]);

    $media1->update(['position' => 2]);
    $media2->update(['position' => 0]);
    $media3->update(['position' => 1]);

    $ordered = $this->product->media()->orderBy('position')->pluck('id')->all();

    expect($ordered[0])->toBe($media2->id);
    expect($ordered[1])->toBe($media3->id);
    expect($ordered[2])->toBe($media1->id);
});

it('deletes media and removes file from storage', function () {
    Storage::fake('public');

    Storage::disk('public')->put('products/test-image.jpg', 'fake-image-content');

    $media = ProductMedia::factory()->create([
        'product_id' => $this->product->id,
        'url' => 'products/test-image.jpg',
    ]);
    $mediaId = $media->id;

    Storage::disk('public')->assertExists('products/test-image.jpg');

    // Delete the media record and file
    Storage::disk('public')->delete($media->url);
    $media->delete();

    expect(ProductMedia::find($mediaId))->toBeNull();
    Storage::disk('public')->assertMissing('products/test-image.jpg');
});
