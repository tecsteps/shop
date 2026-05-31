<?php

use App\Livewire\Storefront\Products\Show as ProductShow;
use App\Models\Product;
use App\Models\ProductVariant;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext(['hostname' => 'acme-fashion.test']);
    $this->store = $this->context['store'];
});

it('renders a published product with its price', function () {
    $product = Product::factory()->active()->create([
        'store_id' => $this->store->id,
        'title' => 'Merino Beanie',
        'handle' => 'merino-beanie',
    ]);
    $variant = ProductVariant::factory()->default()->create(['product_id' => $product->id, 'price_amount' => 3500]);
    $variant->inventoryItem->update(['quantity_on_hand' => 50, 'policy' => 'deny']);

    $this->get(storefrontUrl('acme-fashion.test', '/products/merino-beanie'))
        ->assertOk()
        ->assertSee('Merino Beanie')
        ->assertSee('35.00 USD', escape: false)
        ->assertSee('In stock');
});

it('404s for a draft product', function () {
    Product::factory()->create([
        'store_id' => $this->store->id,
        'status' => 'draft',
        'handle' => 'hidden-product',
    ]);

    $this->get(storefrontUrl('acme-fashion.test', '/products/hidden-product'))
        ->assertNotFound();
});

it('dispatches add-to-cart for a purchasable variant', function () {
    $product = Product::factory()->active()->create(['store_id' => $this->store->id, 'handle' => 'tee']);
    $variant = ProductVariant::factory()->default()->create(['product_id' => $product->id, 'price_amount' => 2000]);
    $variant->inventoryItem->update(['quantity_on_hand' => 10, 'policy' => 'deny']);

    Livewire::test(ProductShow::class, ['handle' => 'tee'])
        ->set('quantity', 2)
        ->call('addToCart')
        ->assertDispatched('add-to-cart', variantId: $variant->id, quantity: 2);
});

it('shows out of stock and blocks add-to-cart for a sold-out deny variant', function () {
    $product = Product::factory()->active()->create(['store_id' => $this->store->id, 'handle' => 'sold-out-tee']);
    $variant = ProductVariant::factory()->default()->create(['product_id' => $product->id, 'price_amount' => 2000]);
    $variant->inventoryItem->update(['quantity_on_hand' => 0, 'policy' => 'deny']);

    Livewire::test(ProductShow::class, ['handle' => 'sold-out-tee'])
        ->assertSee('Out of stock')
        ->call('addToCart')
        ->assertDispatched('cart-error')
        ->assertNotDispatched('add-to-cart');
});

it('allows backorder for a continue-policy variant at zero stock', function () {
    $product = Product::factory()->active()->create(['store_id' => $this->store->id, 'handle' => 'backorder-tee']);
    $variant = ProductVariant::factory()->default()->create(['product_id' => $product->id, 'price_amount' => 2000]);
    $variant->inventoryItem->update(['quantity_on_hand' => 0, 'policy' => 'continue']);

    Livewire::test(ProductShow::class, ['handle' => 'backorder-tee'])
        ->assertSee('Available on backorder')
        ->call('addToCart')
        ->assertDispatched('add-to-cart');
});
