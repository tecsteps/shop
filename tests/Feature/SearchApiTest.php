<?php

use App\Models\ProductMedia;
use Illuminate\Support\Facades\Storage;

it('returns validated facets, compare at pricing, and a usable media URL from paginated search results', function () {
    Storage::fake('public');
    $store = shopStore();
    $product = shopProduct($store);
    $product['variant']->update(['compare_at_amount' => 3500]);
    $media = ProductMedia::query()->create([
        'product_id' => $product['product']->id,
        'type' => 'image',
        'storage_key' => 'products/test-shirt.png',
        'alt_text' => 'Blue cotton shirt',
        'mime_type' => 'image/png',
        'byte_size' => 100,
        'position' => 0,
        'status' => 'ready',
    ]);
    Storage::disk('public')->put($media->storage_key, 'placeholder');

    $this->getJson('http://shop.test/api/storefront/v1/search?q=cotton&per_page=1')
        ->assertOk()
        ->assertJsonPath('pagination.total', 1)
        ->assertJsonPath('pagination.per_page', 1)
        ->assertJsonPath('results.0.id', $product['product']->id)
        ->assertJsonPath('results.0.price_amount', 2500)
        ->assertJsonPath('results.0.compare_at_amount', 3500)
        ->assertJsonPath('results.0.image_url', Storage::disk('public')->url($media->storage_key));
});

it('returns a client error for malformed filters and validation errors for invalid filter shapes', function () {
    shopStore();

    $this->getJson('http://shop.test/api/storefront/v1/search?q=shirt&filters='.rawurlencode('{"tags":'))
        ->assertBadRequest();

    $this->getJson('http://shop.test/api/storefront/v1/search?q=shirt&filters='.rawurlencode('{"tags":"cotton"}'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('tags');

    $this->getJson('http://shop.test/api/storefront/v1/search?q='.str_repeat('x', 201))
        ->assertBadRequest();
});
