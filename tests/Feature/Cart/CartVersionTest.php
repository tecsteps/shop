<?php

use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Exceptions\CartVersionMismatchException;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;

beforeEach(function (): void {
    $ctx = $this->createStoreContext();
    $this->store = $ctx['store'];
    $this->service = app(CartService::class);

    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => ProductStatus::Active]);
    $this->variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 1000]);
    InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $this->variant->id,
        'quantity_on_hand' => 100,
        'policy' => InventoryPolicy::Deny,
    ]);
});

it('allows updates when expected version matches', function (): void {
    $cart = $this->service->create($this->store);
    $line = $this->service->addLine($cart, $this->variant->id, 1);

    $current = $cart->fresh()->cart_version;
    $updated = $this->service->updateLineQuantity($cart, $line->id, 3, $current);

    expect($updated->quantity)->toBe(3);
});

it('throws when expected version is stale', function (): void {
    $cart = $this->service->create($this->store);
    $line = $this->service->addLine($cart, $this->variant->id, 1);

    expect(fn () => $this->service->updateLineQuantity($cart, $line->id, 5, 99))
        ->toThrow(CartVersionMismatchException::class);
});

it('increments version on add, update, and remove', function (): void {
    $cart = $this->service->create($this->store);
    expect($cart->cart_version)->toBe(1);

    $line = $this->service->addLine($cart, $this->variant->id, 1);
    expect($cart->fresh()->cart_version)->toBe(2);

    $this->service->updateLineQuantity($cart, $line->id, 2);
    expect($cart->fresh()->cart_version)->toBe(3);

    $this->service->removeLine($cart, $line->id);
    expect($cart->fresh()->cart_version)->toBe(4);
});

it('returns HTTP 409 when API client sends a stale version', function (): void {
    $this->bindStoreToAppHost($this->store);

    $cart = $this->service->create($this->store);
    $line = $this->service->addLine($cart, $this->variant->id, 1);

    $response = $this->putJson("/api/storefront/v1/carts/{$cart->id}/lines/{$line->id}", [
        'quantity' => 2,
        'cart_version' => 0,
    ]);

    $response->assertStatus(409)
        ->assertJsonStructure(['message', 'expected_version', 'current_version']);
});

it('exposes expected and current versions on the exception', function (): void {
    $cart = $this->service->create($this->store);
    $line = $this->service->addLine($cart, $this->variant->id, 1);

    try {
        $this->service->updateLineQuantity($cart, $line->id, 5, 99);
        $this->fail('Expected CartVersionMismatchException');
    } catch (CartVersionMismatchException $e) {
        expect($e->expected)->toBe(99);
        expect($e->current)->toBe($cart->fresh()->cart_version);
    }
});

it('does not bump version on read-only fetches', function (): void {
    $cart = $this->service->create($this->store);
    $before = $cart->cart_version;

    $cart->refresh();

    expect($cart->cart_version)->toBe($before);
});
