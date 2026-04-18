<?php

use Illuminate\Support\Facades\Schema;

it('returns 404 when product handle is missing', function (): void {
    $this->createStoreContext(['hostname' => 'product-404-store.test']);

    $this->get('http://product-404-store.test/products/does-not-exist')->assertNotFound();
});

it('renders a product page when the product exists', function (): void {
    if (! Schema::hasTable('products')) {
        $this->markTestSkipped('Product table not available yet.');
    }

    $context = $this->createStoreContext(['hostname' => 'product-show-store.test']);

    if (! class_exists(\App\Models\Product::class)) {
        $this->markTestSkipped('Product model not ready.');
    }

    $product = \App\Models\Product::factory()->create([
        'store_id' => $context['store']->id,
        'status' => \App\Enums\ProductStatus::Active,
        'handle' => 'showcase-tee',
        'title' => 'Showcase Tee',
    ]);

    $response = $this->get('http://product-show-store.test/products/'.$product->handle);

    $response->assertOk();
    $response->assertSee('Showcase Tee');
});
