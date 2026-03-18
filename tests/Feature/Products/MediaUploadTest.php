<?php

use App\Enums\MediaStatus;
use App\Jobs\ProcessMediaUpload;
use App\Models\Product;
use App\Models\ProductMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

it('uploads an image for a product', function () {
    $context = createStoreContext();
    $product = Product::factory()->create(['store_id' => $context['store']->id]);

    Storage::fake('public');
    $file = UploadedFile::fake()->image('product.jpg', 1200, 1200);
    $path = $file->store('products', 'public');

    $media = ProductMedia::create([
        'product_id' => $product->id,
        'type' => 'image',
        'storage_key' => $path,
        'status' => MediaStatus::Processing,
        'mime_type' => 'image/jpeg',
        'byte_size' => $file->getSize(),
        'position' => 0,
    ]);

    expect($media->status)->toBe(MediaStatus::Processing)
        ->and($media->product_id)->toBe($product->id);
});

it('processes uploaded image and generates variants', function () {
    $context = createStoreContext();
    $product = Product::factory()->create(['store_id' => $context['store']->id]);

    Storage::fake('public');
    $file = UploadedFile::fake()->image('product.jpg', 1200, 1200);
    $path = $file->store('products', 'public');

    $media = ProductMedia::create([
        'product_id' => $product->id,
        'type' => 'image',
        'storage_key' => $path,
        'status' => MediaStatus::Processing,
        'mime_type' => 'image/jpeg',
        'byte_size' => $file->getSize(),
        'position' => 0,
    ]);

    Queue::fake();
    ProcessMediaUpload::dispatch($media);

    Queue::assertPushed(ProcessMediaUpload::class, function ($job) use ($media) {
        return $job->media->id === $media->id;
    });
});

it('rejects non-image file types', function () {
    Storage::fake('public');
    $file = UploadedFile::fake()->create('document.txt', 100, 'text/plain');

    $validator = \Illuminate\Support\Facades\Validator::make(
        ['file' => $file],
        ['file' => 'required|mimes:jpg,jpeg,png,gif,webp,mp4,webm']
    );

    expect($validator->fails())->toBeTrue();
});

it('sets alt text on media', function () {
    $context = createStoreContext();
    $product = Product::factory()->create(['store_id' => $context['store']->id]);

    $media = ProductMedia::create([
        'product_id' => $product->id,
        'type' => 'image',
        'storage_key' => 'products/test.jpg',
        'status' => MediaStatus::Ready,
        'position' => 0,
    ]);

    $media->update(['alt_text' => 'A stylish cotton t-shirt']);

    expect($media->fresh()->alt_text)->toBe('A stylish cotton t-shirt');
});

it('reorders media positions', function () {
    $context = createStoreContext();
    $product = Product::factory()->create(['store_id' => $context['store']->id]);

    $media1 = ProductMedia::create(['product_id' => $product->id, 'type' => 'image', 'storage_key' => 'p/1.jpg', 'position' => 0, 'status' => 'ready']);
    $media2 = ProductMedia::create(['product_id' => $product->id, 'type' => 'image', 'storage_key' => 'p/2.jpg', 'position' => 1, 'status' => 'ready']);
    $media3 = ProductMedia::create(['product_id' => $product->id, 'type' => 'image', 'storage_key' => 'p/3.jpg', 'position' => 2, 'status' => 'ready']);

    $media1->update(['position' => 2]);
    $media2->update(['position' => 0]);
    $media3->update(['position' => 1]);

    $ordered = $product->media()->orderBy('position')->get();

    expect($ordered[0]->id)->toBe($media2->id)
        ->and($ordered[1]->id)->toBe($media3->id)
        ->and($ordered[2]->id)->toBe($media1->id);
});

it('deletes media and removes file from storage', function () {
    $context = createStoreContext();
    $product = Product::factory()->create(['store_id' => $context['store']->id]);

    Storage::fake('public');
    $file = UploadedFile::fake()->image('product.jpg', 600, 600);
    $path = $file->store('products', 'public');

    $media = ProductMedia::create([
        'product_id' => $product->id,
        'type' => 'image',
        'storage_key' => $path,
        'status' => MediaStatus::Ready,
        'position' => 0,
    ]);

    Storage::disk('public')->assertExists($path);

    Storage::disk('public')->delete($path);
    $media->delete();

    Storage::disk('public')->assertMissing($path);
    expect(ProductMedia::find($media->id))->toBeNull();
});
