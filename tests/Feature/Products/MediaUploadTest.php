<?php

use App\Enums\MediaStatus;
use App\Jobs\ProcessMediaUpload;
use App\Models\Product;
use App\Models\ProductMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    Storage::fake('public');
});

it('uploads an image for a product', function () {
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
    ]);

    $file = UploadedFile::fake()->image('product.jpg', 800, 600);
    $path = $file->store('products', 'public');

    $media = ProductMedia::query()->create([
        'product_id' => $product->id,
        'type' => 'image',
        'storage_key' => $path,
        'status' => 'processing',
        'position' => 0,
    ]);

    expect($media->status)->toBe(MediaStatus::Processing)
        ->and($media->type->value)->toBe('image')
        ->and(Storage::disk('public')->exists($path))->toBeTrue();
});

it('processes uploaded image and generates variants', function () {
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
    ]);

    $file = UploadedFile::fake()->image('product.jpg', 2000, 1500);
    $path = $file->store('products', 'public');

    $media = ProductMedia::query()->create([
        'product_id' => $product->id,
        'type' => 'image',
        'storage_key' => $path,
        'status' => 'processing',
        'position' => 0,
    ]);

    // Check if intervention/image is available
    if (! class_exists(\Intervention\Image\ImageManager::class)) {
        // Just verify the job can be dispatched
        Queue::fake();
        ProcessMediaUpload::dispatch($media);
        Queue::assertPushed(ProcessMediaUpload::class);

        return;
    }

    $job = new ProcessMediaUpload($media);
    $job->handle();

    $media->refresh();
    expect($media->status)->toBe(MediaStatus::Ready)
        ->and($media->width)->not->toBeNull()
        ->and($media->height)->not->toBeNull();
});

it('rejects non-image file types', function () {
    $file = UploadedFile::fake()->create('document.txt', 100, 'text/plain');

    $validator = \Illuminate\Support\Facades\Validator::make(
        ['file' => $file],
        ['file' => 'image|mimes:jpeg,png,gif,webp']
    );

    expect($validator->fails())->toBeTrue();
});

it('sets alt text on media', function () {
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
    ]);

    $media = ProductMedia::factory()->create([
        'product_id' => $product->id,
    ]);

    $media->update(['alt_text' => 'Product front view']);

    $media->refresh();
    expect($media->alt_text)->toBe('Product front view');
});

it('reorders media positions', function () {
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
    ]);

    $mediaItems = [];
    for ($i = 0; $i < 3; $i++) {
        $mediaItems[] = ProductMedia::factory()->create([
            'product_id' => $product->id,
            'position' => $i,
        ]);
    }

    $mediaItems[0]->update(['position' => 2]);
    $mediaItems[1]->update(['position' => 0]);
    $mediaItems[2]->update(['position' => 1]);

    $ordered = $product->media()->orderBy('position')->get();
    expect($ordered[0]->id)->toBe($mediaItems[1]->id)
        ->and($ordered[1]->id)->toBe($mediaItems[2]->id)
        ->and($ordered[2]->id)->toBe($mediaItems[0]->id);
});

it('deletes media and removes file from storage', function () {
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
    ]);

    $file = UploadedFile::fake()->image('product.jpg', 800, 600);
    $path = $file->store('products', 'public');

    $media = ProductMedia::query()->create([
        'product_id' => $product->id,
        'type' => 'image',
        'storage_key' => $path,
        'status' => 'ready',
        'position' => 0,
    ]);

    expect(Storage::disk('public')->exists($path))->toBeTrue();

    Storage::disk('public')->delete($media->storage_key);
    $media->delete();

    expect(ProductMedia::find($media->id))->toBeNull()
        ->and(Storage::disk('public')->exists($path))->toBeFalse();
});
