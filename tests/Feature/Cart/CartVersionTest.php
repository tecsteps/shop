<?php

use App\Enums\VariantStatus;
use App\Exceptions\CartVersionMismatchException;
use App\Models\Cart;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(CartService::class);
    $this->store = Store::factory()->create();
    $this->cart = Cart::factory()->create(['store_id' => $this->store->id]);

    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $this->variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 1000,
        'status' => VariantStatus::Active,
    ]);
    InventoryItem::factory()->withStock(50)->create([
        'store_id' => $this->store->id,
        'variant_id' => $this->variant->id,
    ]);
});

it('increments version on addLine', function () {
    expect($this->cart->cart_version)->toBe(1);

    $this->service->addLine($this->cart, $this->variant->id, 1);

    expect($this->cart->fresh()->cart_version)->toBe(2);
});

it('increments version on updateLineQuantity', function () {
    $line = $this->service->addLine($this->cart, $this->variant->id, 1);
    $versionAfterAdd = $this->cart->fresh()->cart_version;

    $this->service->updateLineQuantity($this->cart, $line->id, 3);

    expect($this->cart->fresh()->cart_version)->toBe($versionAfterAdd + 1);
});

it('increments version on removeLine', function () {
    $line = $this->service->addLine($this->cart, $this->variant->id, 1);
    $versionAfterAdd = $this->cart->fresh()->cart_version;

    $this->service->removeLine($this->cart, $line->id);

    expect($this->cart->fresh()->cart_version)->toBe($versionAfterAdd + 1);
});

it('throws on version mismatch', function () {
    $this->service->addLine($this->cart, $this->variant->id, 1, 999);
})->throws(CartVersionMismatchException::class);

it('succeeds with correct expected version', function () {
    $line = $this->service->addLine($this->cart, $this->variant->id, 1, 1);

    expect($line)->not->toBeNull()
        ->and($this->cart->fresh()->cart_version)->toBe(2);
});
