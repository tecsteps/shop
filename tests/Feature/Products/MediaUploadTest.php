<?php

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Jobs\ProcessMediaUpload;
use App\Models\Product;
use App\Models\ProductMedia;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->ctx = createStoreContext();
    Storage::fake('public');
});

it('creates a media record for a product', function () {
    $product = Product::factory()->create(['store_id' => $this->ctx['store']->id]);

    $media = ProductMedia::factory()->processing()->create([
        'product_id' => $product->id,
    ]);

    expect($media->exists)->toBeTrue()
        ->and($media->status)->toBe(MediaStatus::Processing)
        ->and($media->type)->toBe(MediaType::Image);
});

it('processes media and updates status to ready', function () {
    $product = Product::factory()->create(['store_id' => $this->ctx['store']->id]);

    Storage::disk('public')->put('products/test-image.jpg', file_get_contents(
        base_path('tests/fixtures/test-image.jpg')
    ));

    $media = ProductMedia::factory()->processing()->create([
        'product_id' => $product->id,
        'storage_key' => 'products/test-image.jpg',
    ]);

    $job = new ProcessMediaUpload($media->id);
    $job->handle();

    expect($media->fresh()->status)->toBe(MediaStatus::Ready);
});

it('marks media as failed when file does not exist', function () {
    $product = Product::factory()->create(['store_id' => $this->ctx['store']->id]);

    $media = ProductMedia::factory()->processing()->create([
        'product_id' => $product->id,
        'storage_key' => 'products/nonexistent.jpg',
    ]);

    $job = new ProcessMediaUpload($media->id);
    $job->handle();

    expect($media->fresh()->status)->toBe(MediaStatus::Failed);
});

it('sets alt text on media', function () {
    $product = Product::factory()->create(['store_id' => $this->ctx['store']->id]);

    $media = ProductMedia::factory()->create([
        'product_id' => $product->id,
        'alt_text' => null,
    ]);

    $media->update(['alt_text' => 'A beautiful product image']);

    expect($media->fresh()->alt_text)->toBe('A beautiful product image');
});

it('reorders media positions', function () {
    $product = Product::factory()->create(['store_id' => $this->ctx['store']->id]);

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

it('deletes media record', function () {
    $product = Product::factory()->create(['store_id' => $this->ctx['store']->id]);

    Storage::disk('public')->put('products/to-delete.jpg', 'dummy');

    $media = ProductMedia::factory()->create([
        'product_id' => $product->id,
        'storage_key' => 'products/to-delete.jpg',
    ]);

    $storageKey = $media->storage_key;
    $media->delete();
    Storage::disk('public')->delete($storageKey);

    expect(ProductMedia::find($media->id))->toBeNull()
        ->and(Storage::disk('public')->exists($storageKey))->toBeFalse();
});
