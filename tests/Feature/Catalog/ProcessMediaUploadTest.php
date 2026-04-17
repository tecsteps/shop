<?php

use App\Enums\MediaStatus;
use App\Jobs\ProcessMediaUpload;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('job updates media status from processing to ready', function () {
    $store = Store::factory()->create();
    $product = Product::factory()->create(['store_id' => $store->id]);
    $media = ProductMedia::factory()->processing()->create(['product_id' => $product->id]);

    expect($media->status)->toBe(MediaStatus::Processing);

    (new ProcessMediaUpload($media->id))->handle();

    $media->refresh();
    expect($media->status)->toBe(MediaStatus::Ready);
});

test('job skips media that is not in processing status', function () {
    $store = Store::factory()->create();
    $product = Product::factory()->create(['store_id' => $store->id]);
    $media = ProductMedia::factory()->create([
        'product_id' => $product->id,
        'status' => MediaStatus::Ready,
    ]);

    (new ProcessMediaUpload($media->id))->handle();

    $media->refresh();
    expect($media->status)->toBe(MediaStatus::Ready);
});

test('job handles non-existent media gracefully', function () {
    (new ProcessMediaUpload(99999))->handle();

    // Should not throw
    expect(true)->toBeTrue();
});
