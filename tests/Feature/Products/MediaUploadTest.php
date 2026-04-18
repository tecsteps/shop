<?php

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Jobs\ProcessMediaUpload;
use App\Models\Product;
use App\Models\ProductMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $context = $this->createStoreContext();
    $this->store = $context['store'];
    Storage::fake('public');
});

it('creates a media row for an uploaded image', function (): void {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $file = UploadedFile::fake()->image('product.jpg', 1200, 1200);
    $path = $file->store('products', 'public');

    $media = ProductMedia::query()->create([
        'product_id' => $product->id,
        'type' => MediaType::Image,
        'storage_key' => $path,
        'mime_type' => 'image/jpeg',
        'byte_size' => $file->getSize(),
        'position' => 0,
        'status' => MediaStatus::Processing,
        'created_at' => now(),
    ]);

    expect($media->status)->toBe(MediaStatus::Processing);
    expect(Storage::disk('public')->exists($path))->toBeTrue();
});

it('processes an uploaded image and marks status ready', function (): void {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $file = UploadedFile::fake()->image('product.jpg');
    $path = $file->store('products', 'public');

    $media = ProductMedia::query()->create([
        'product_id' => $product->id,
        'type' => MediaType::Image,
        'storage_key' => $path,
        'status' => MediaStatus::Processing,
        'created_at' => now(),
    ]);

    (new ProcessMediaUpload($media))->handle();

    expect($media->fresh()->status)->toBe(MediaStatus::Ready);
});

it('marks media as failed when the source file is missing', function (): void {
    $product = Product::factory()->create(['store_id' => $this->store->id]);

    $media = ProductMedia::query()->create([
        'product_id' => $product->id,
        'type' => MediaType::Image,
        'storage_key' => 'products/missing.jpg',
        'status' => MediaStatus::Processing,
        'created_at' => now(),
    ]);

    (new ProcessMediaUpload($media))->handle();

    expect($media->fresh()->status)->toBe(MediaStatus::Failed);
});

it('allows setting alt text on media', function (): void {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $media = ProductMedia::factory()->create(['product_id' => $product->id, 'alt_text' => null]);

    $media->update(['alt_text' => 'Front view']);

    expect($media->fresh()->alt_text)->toBe('Front view');
});

it('reorders media positions', function (): void {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $media = collect([0, 1, 2])->map(fn ($i) => ProductMedia::factory()->create([
        'product_id' => $product->id,
        'position' => $i,
    ]));

    $media[0]->update(['position' => 2]);
    $media[1]->update(['position' => 0]);
    $media[2]->update(['position' => 1]);

    $ordered = $product->media()->orderBy('position')->pluck('id')->all();
    expect($ordered)->toBe([$media[1]->id, $media[2]->id, $media[0]->id]);
});

it('deletes media and removes the file from disk', function (): void {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $file = UploadedFile::fake()->image('del.jpg');
    $path = $file->store('products', 'public');

    $media = ProductMedia::query()->create([
        'product_id' => $product->id,
        'type' => MediaType::Image,
        'storage_key' => $path,
        'status' => MediaStatus::Ready,
        'created_at' => now(),
    ]);

    Storage::disk('public')->delete($media->storage_key);
    $media->delete();

    expect(ProductMedia::query()->find($media->id))->toBeNull();
    expect(Storage::disk('public')->exists($path))->toBeFalse();
});
