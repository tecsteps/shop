<?php

use App\Models\Store;

it('supports storefront cart api create, read, add, update, and delete line endpoints', function () {
    $store = Store::factory()->create();
    $variant = createSellableVariant($store, variantAttributes: ['price_amount' => 1800]);

    $createResponse = $this->postJson('/api/storefront/v1/carts', [
        'currency' => 'eur',
    ])->assertCreated();

    $cartId = (int) $createResponse->json('id');

    expect($cartId)->toBeGreaterThan(0);

    $addResponse = $this->postJson("/api/storefront/v1/carts/{$cartId}/lines", [
        'variant_id' => $variant->id,
        'quantity' => 2,
        'expected_version' => 1,
    ])->assertCreated()
        ->assertJsonPath('cart_version', 2)
        ->assertJsonPath('lines.0.quantity', 2);

    $lineId = (int) $addResponse->json('lines.0.id');

    $this->putJson("/api/storefront/v1/carts/{$cartId}/lines/{$lineId}", [
        'quantity' => 3,
        'expected_version' => 2,
    ])->assertOk()
        ->assertJsonPath('cart_version', 3)
        ->assertJsonPath('lines.0.quantity', 3)
        ->assertJsonPath('totals.item_count', 3);

    $this->getJson("/api/storefront/v1/carts/{$cartId}")
        ->assertOk()
        ->assertJsonPath('id', $cartId)
        ->assertJsonPath('lines.0.id', $lineId);

    $this->deleteJson("/api/storefront/v1/carts/{$cartId}/lines/{$lineId}", [
        'expected_version' => 3,
    ])->assertOk()
        ->assertJsonPath('cart_version', 4)
        ->assertJsonCount(0, 'lines')
        ->assertJsonPath('totals.item_count', 0);
});
