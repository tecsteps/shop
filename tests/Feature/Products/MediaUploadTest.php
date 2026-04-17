<?php

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Jobs\ProcessMediaUpload;
use App\Models\Product;
use App\Models\ProductMedia;
use Illuminate\Support\Facades\Storage;

it('uploads an image for a product', function () {
    $context = createStoreContext();

    $product = Product::factory()->create(['store_id' => $context['store']->id]);

    $media = ProductMedia::factory()->processing()->create([
        'product_id' => $product->id,
        'type' => MediaType::Image,
        'status' => MediaStatus::Processing,
    ]);

    expect($media->status)->toBe(MediaStatus::Processing);
    expect($media->product_id)->toBe($product->id);
});

it('processes uploaded image and updates status to ready', function () {
    $context = createStoreContext();

    Storage::fake('public');

    $product = Product::factory()->create(['store_id' => $context['store']->id]);

    // Create a real test image
    $image = imagecreatetruecolor(100, 100);
    ob_start();
    imagejpeg($image);
    $imageData = ob_get_clean();
    imagedestroy($image);

    $storageKey = 'products/test-image.jpg';
    Storage::disk('public')->put($storageKey, $imageData);

    $media = ProductMedia::factory()->processing()->create([
        'product_id' => $product->id,
        'storage_key' => $storageKey,
        'status' => MediaStatus::Processing,
    ]);

    $job = new ProcessMediaUpload($media->id);
    $job->handle();

    $media->refresh();
    expect($media->status)->toBe(MediaStatus::Ready);
    expect($media->width)->toBe(100);
    expect($media->height)->toBe(100);
});

it('sets status to failed when file does not exist', function () {
    $context = createStoreContext();

    Storage::fake('public');

    $product = Product::factory()->create(['store_id' => $context['store']->id]);

    $media = ProductMedia::factory()->processing()->create([
        'product_id' => $product->id,
        'storage_key' => 'products/nonexistent.jpg',
        'status' => MediaStatus::Processing,
    ]);

    $job = new ProcessMediaUpload($media->id);
    $job->handle();

    $media->refresh();
    expect($media->status)->toBe(MediaStatus::Failed);
});

it('sets alt text on media', function () {
    $context = createStoreContext();

    $product = Product::factory()->create(['store_id' => $context['store']->id]);

    $media = ProductMedia::factory()->create([
        'product_id' => $product->id,
        'alt_text' => null,
    ]);

    $media->update(['alt_text' => 'A beautiful red dress']);

    expect($media->fresh()->alt_text)->toBe('A beautiful red dress');
});

it('reorders media positions', function () {
    $context = createStoreContext();

    $product = Product::factory()->create(['store_id' => $context['store']->id]);

    $media1 = ProductMedia::factory()->create(['product_id' => $product->id, 'position' => 0]);
    $media2 = ProductMedia::factory()->create(['product_id' => $product->id, 'position' => 1]);
    $media3 = ProductMedia::factory()->create(['product_id' => $product->id, 'position' => 2]);

    // Reorder
    $media3->update(['position' => 0]);
    $media1->update(['position' => 1]);
    $media2->update(['position' => 2]);

    $ordered = $product->media()->orderBy('position')->get();

    expect($ordered[0]->id)->toBe($media3->id);
    expect($ordered[1]->id)->toBe($media1->id);
    expect($ordered[2]->id)->toBe($media2->id);
});

it('deletes media and removes file from storage', function () {
    $context = createStoreContext();

    Storage::fake('public');

    $product = Product::factory()->create(['store_id' => $context['store']->id]);

    $storageKey = 'products/deletable-image.jpg';
    Storage::disk('public')->put($storageKey, 'fake image data');

    $media = ProductMedia::factory()->create([
        'product_id' => $product->id,
        'storage_key' => $storageKey,
    ]);

    $mediaId = $media->id;

    Storage::disk('public')->delete($storageKey);
    $media->delete();

    expect(ProductMedia::find($mediaId))->toBeNull();
    Storage::disk('public')->assertMissing($storageKey);
});
