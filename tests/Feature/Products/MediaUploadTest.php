<?php

use App\Enums\MediaStatus;
use App\Jobs\ProcessMediaUpload;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('public');
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
});

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

it('creates product media records', function (): void {
    $product = Product::factory()->for($this->store)->create();

    $media = ProductMedia::factory()->for($product)->create();

    expect($media->fresh())
        ->product_id->toBe($product->id)
        ->and($media->status)->toBe(MediaStatus::Processing);
});

it('marks media as ready after processing stub job', function (): void {
    $product = Product::factory()->for($this->store)->create();
    $media = ProductMedia::factory()->for($product)->create([
        'status' => MediaStatus::Processing->value,
    ]);

    (new ProcessMediaUpload($media))->handle();

    expect($media->fresh()->status)->toBe(MediaStatus::Ready);
});
