<?php

use App\Models\Product;
use App\Models\ProductMedia;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->ctx = createStoreContext();
    Storage::fake('public');
});

it('creates a media record for a product', function () {
    $product = Product::factory()->for($this->ctx['store'])->create();

    $media = ProductMedia::factory()->for($product)->create([
        'storage_key' => 'products/test.jpg',
        'alt_text' => 'Test image',
    ]);

    expect($media->product_id)->toBe($product->id);
});

it('sets alt text on media', function () {
    $product = Product::factory()->for($this->ctx['store'])->create();

    $media = ProductMedia::factory()->for($product)->create();
    $media->update(['alt_text' => 'Updated alt text']);

    expect($media->fresh()->alt_text)->toBe('Updated alt text');
});

it('deletes media record', function () {
    $product = Product::factory()->for($this->ctx['store'])->create();
    $media = ProductMedia::factory()->for($product)->create();

    $media->delete();

    expect(ProductMedia::find($media->id))->toBeNull();
});
