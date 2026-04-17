<?php

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Jobs\ProcessMediaUpload;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\Store;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
});

it('creates product media with processing status', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);

    $media = ProductMedia::create([
        'product_id' => $product->id,
        'type' => MediaType::Image,
        'storage_key' => 'products/test.jpg',
        'position' => 0,
        'status' => MediaStatus::Processing,
    ]);

    expect($media->status)->toBe(MediaStatus::Processing)
        ->and($media->type)->toBe(MediaType::Image);
});

it('lists media for a product ordered by position', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);

    ProductMedia::factory()->create(['product_id' => $product->id, 'position' => 2]);
    ProductMedia::factory()->create(['product_id' => $product->id, 'position' => 0]);
    ProductMedia::factory()->create(['product_id' => $product->id, 'position' => 1]);

    $media = $product->media()->orderBy('position')->get();
    expect($media)->toHaveCount(3)
        ->and($media->first()->position)->toBe(0)
        ->and($media->last()->position)->toBe(2);
});

it('marks media as failed when file does not exist', function () {
    Storage::fake('public');

    $product = Product::factory()->create(['store_id' => $this->store->id]);

    $media = ProductMedia::create([
        'product_id' => $product->id,
        'type' => MediaType::Image,
        'storage_key' => 'products/nonexistent.jpg',
        'position' => 0,
        'status' => MediaStatus::Processing,
    ]);

    $job = new ProcessMediaUpload($media);
    $job->handle();

    expect($media->fresh()->status)->toBe(MediaStatus::Failed);
});

it('creates video media type', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);

    $media = ProductMedia::factory()->video()->create(['product_id' => $product->id]);

    expect($media->type)->toBe(MediaType::Video)
        ->and($media->mime_type)->toBe('video/mp4');
});

it('uses the factory to create ready media', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);

    $media = ProductMedia::factory()->create(['product_id' => $product->id]);

    expect($media->status)->toBe(MediaStatus::Ready)
        ->and($media->width)->toBe(1200)
        ->and($media->height)->toBe(1200);
});
