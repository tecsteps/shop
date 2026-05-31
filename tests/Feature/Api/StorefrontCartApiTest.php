<?php

use App\Models\Cart;

beforeEach(function () {
    $this->context = createStoreContext(['hostname' => 'shop.test']);
    $this->store = $this->context['store'];
});

function storefrontCartApi(string $path = ''): string
{
    return storefrontUrl('shop.test', '/api/storefront/v1/carts'.$path);
}

/**
 * Create a cart in the store with one sellable line and return [cart, lineId].
 *
 * @return array{0: \App\Models\Cart, 1: int}
 */
function cartWithApiLine(\App\Models\Store $store): array
{
    bindCurrentStore($store);
    $variant = makeSellableVariant(['price' => 2500]);

    $cart = Cart::factory()->create(['store_id' => $store->id, 'currency' => 'USD']);
    $line = $cart->lines()->create([
        'variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 5000,
        'line_discount_amount' => 0,
        'line_total_amount' => 5000,
    ]);

    return [$cart, $line->id];
}

it('creates a cart', function () {
    $this->postJson(storefrontCartApi())
        ->assertCreated()
        ->assertJsonPath('cart_version', 1)
        ->assertJsonStructure(['id', 'cart_version', 'lines', 'totals']);
});

it('retrieves a cart with lines and totals', function () {
    [$cart] = cartWithApiLine($this->store);

    $this->getJson(storefrontCartApi("/{$cart->id}"))
        ->assertSuccessful()
        ->assertJsonCount(1, 'lines')
        ->assertJsonPath('totals.subtotal', 5000);
});

it('adds a line to the cart', function () {
    bindCurrentStore($this->store);
    $variant = makeSellableVariant(['price' => 2500]);
    $cart = Cart::factory()->create(['store_id' => $this->store->id, 'currency' => 'USD']);

    $this->postJson(storefrontCartApi("/{$cart->id}/lines"), [
        'variant_id' => $variant->id,
        'quantity' => 1,
    ])->assertCreated()->assertJsonCount(1, 'lines');
});

it('updates a line quantity', function () {
    [$cart, $lineId] = cartWithApiLine($this->store);

    $this->putJson(storefrontCartApi("/{$cart->id}/lines/{$lineId}"), [
        'quantity' => 5,
        'cart_version' => $cart->cart_version,
    ])->assertSuccessful();

    expect($cart->lines()->find($lineId)->quantity)->toBe(5);
});

it('removes a line', function () {
    [$cart, $lineId] = cartWithApiLine($this->store);

    $this->deleteJson(storefrontCartApi("/{$cart->id}/lines/{$lineId}"), [
        'cart_version' => $cart->cart_version,
    ])->assertSuccessful()->assertJsonCount(0, 'lines');
});

it('validates variant exists on add', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id, 'currency' => 'USD']);

    $this->postJson(storefrontCartApi("/{$cart->id}/lines"), [
        'variant_id' => 99999,
        'quantity' => 1,
    ])->assertStatus(422);
});

it('validates quantity is positive', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id, 'currency' => 'USD']);

    $this->postJson(storefrontCartApi("/{$cart->id}/lines"), [
        'variant_id' => 1,
        'quantity' => 0,
    ])->assertStatus(422);
});

it('returns 404 for nonexistent cart', function () {
    $this->getJson(storefrontCartApi('/999'))->assertNotFound();
});
