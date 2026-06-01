<?php

use App\Models\Cart;

beforeEach(function () {
    $this->context = createStoreContext(['hostname' => 'shop.test']);
    $this->store = $this->context['store'];
});

function cartApi(string $path = ''): string
{
    return storefrontUrl('shop.test', '/api/storefront/v1/carts'.$path);
}

it('creates a cart via API', function () {
    $this->postJson(cartApi())
        ->assertCreated()
        ->assertJsonPath('cart_version', 1)
        ->assertJsonPath('status', 'active');
});

it('retrieves a cart via API', function () {
    $variant = (function () {
        bindCurrentStore($this->store);

        return makeSellableVariant(['price' => 2500]);
    })();

    $cart = Cart::factory()->create(['store_id' => $this->store->id, 'currency' => 'USD']);
    $cart->lines()->create([
        'variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 5000,
        'line_discount_amount' => 0,
        'line_total_amount' => 5000,
    ]);

    $this->getJson(cartApi("/{$cart->id}"))
        ->assertSuccessful()
        ->assertJsonPath('id', $cart->id)
        ->assertJsonCount(1, 'lines')
        ->assertJsonPath('totals.subtotal', 5000);
});

it('adds a line via API', function () {
    bindCurrentStore($this->store);
    $variant = makeSellableVariant(['price' => 2500]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id, 'currency' => 'USD']);

    $this->postJson(cartApi("/{$cart->id}/lines"), [
        'variant_id' => $variant->id,
        'quantity' => 2,
    ])
        ->assertCreated()
        ->assertJsonCount(1, 'lines');

    expect($cart->fresh()->lines()->count())->toBe(1);
});

it('updates line quantity via API', function () {
    bindCurrentStore($this->store);
    $variant = makeSellableVariant(['price' => 2500]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id, 'currency' => 'USD']);
    $line = $cart->lines()->create([
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 2500,
        'line_discount_amount' => 0,
        'line_total_amount' => 2500,
    ]);
    $cart->increment('cart_version');

    $this->putJson(cartApi("/{$cart->id}/lines/{$line->id}"), [
        'quantity' => 3,
        'cart_version' => $cart->fresh()->cart_version,
    ])
        ->assertSuccessful();

    expect($line->fresh()->quantity)->toBe(3);
});

it('removes a line via API', function () {
    bindCurrentStore($this->store);
    $variant = makeSellableVariant(['price' => 2500]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id, 'currency' => 'USD']);
    $line = $cart->lines()->create([
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 2500,
        'line_discount_amount' => 0,
        'line_total_amount' => 2500,
    ]);

    $this->deleteJson(cartApi("/{$cart->id}/lines/{$line->id}"), [
        'cart_version' => $cart->cart_version,
    ])->assertSuccessful();

    expect($cart->fresh()->lines()->count())->toBe(0);
});

it('returns 404 for nonexistent cart', function () {
    $this->getJson(cartApi('/999999'))->assertNotFound();
});

it('returns 409 on version mismatch', function () {
    bindCurrentStore($this->store);
    $variant = makeSellableVariant(['price' => 2500]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id, 'currency' => 'USD', 'cart_version' => 3]);
    $line = $cart->lines()->create([
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 2500,
        'line_discount_amount' => 0,
        'line_total_amount' => 2500,
    ]);

    $this->putJson(cartApi("/{$cart->id}/lines/{$line->id}"), [
        'quantity' => 2,
        'cart_version' => 2,
    ])
        ->assertStatus(409)
        ->assertJsonPath('error_code', 'version_conflict')
        ->assertJsonPath('current_version', 3);
});

it('respects storefront rate limiting', function () {
    for ($i = 0; $i < 120; $i++) {
        $this->getJson(cartApi('/999999'))->assertNotFound();
    }

    $this->getJson(cartApi('/999999'))->assertStatus(429);
});
