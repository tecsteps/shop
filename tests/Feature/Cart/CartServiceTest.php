<?php

use App\Enums\CartStatus;
use App\Enums\InventoryPolicy;
use App\Enums\VariantStatus;
use App\Exceptions\InsufficientInventoryException;
use App\Models\Customer;
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
    $this->product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $this->variant = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'price_amount' => 2999,
        'status' => VariantStatus::Active,
    ]);
    InventoryItem::factory()->withStock(25)->create([
        'store_id' => $this->store->id,
        'variant_id' => $this->variant->id,
        'policy' => InventoryPolicy::Deny,
    ]);
});

it('creates a cart for a store', function () {
    $cart = $this->service->create($this->store);

    expect($cart->store_id)->toBe($this->store->id)
        ->and($cart->status)->toBe(CartStatus::Active)
        ->and($cart->cart_version)->toBe(1)
        ->and($cart->currency)->toBe($this->store->default_currency);
});

it('creates a cart for a customer', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);
    $cart = $this->service->create($this->store, $customer);

    expect($cart->customer_id)->toBe($customer->id);
});

it('adds a line to the cart', function () {
    $cart = $this->service->create($this->store);
    $line = $this->service->addLine($cart, $this->variant->id, 2);

    expect($line->variant_id)->toBe($this->variant->id)
        ->and($line->quantity)->toBe(2)
        ->and($line->unit_price_amount)->toBe(2999)
        ->and($line->line_subtotal_amount)->toBe(5998)
        ->and($line->line_total_amount)->toBe(5998);
});

it('increments quantity for existing variant in cart', function () {
    $cart = $this->service->create($this->store);
    $this->service->addLine($cart, $this->variant->id, 2);
    $line = $this->service->addLine($cart, $this->variant->id, 1);

    expect($line->quantity)->toBe(3)
        ->and($line->line_subtotal_amount)->toBe(8997);
});

it('rejects add for inactive product', function () {
    $draftProduct = Product::factory()->create(['store_id' => $this->store->id]);
    $draftVariant = ProductVariant::factory()->create([
        'product_id' => $draftProduct->id,
        'status' => VariantStatus::Active,
    ]);

    $cart = $this->service->create($this->store);

    $this->service->addLine($cart, $draftVariant->id, 1);
})->throws(InvalidArgumentException::class, 'Product is not active');

it('rejects add when inventory is insufficient', function () {
    $cart = $this->service->create($this->store);

    $this->service->addLine($cart, $this->variant->id, 100);
})->throws(InsufficientInventoryException::class);

it('updates line quantity', function () {
    $cart = $this->service->create($this->store);
    $line = $this->service->addLine($cart, $this->variant->id, 1);

    $updated = $this->service->updateLineQuantity($cart, $line->id, 5);

    expect($updated->quantity)->toBe(5)
        ->and($updated->line_subtotal_amount)->toBe(14995);
});

it('removes line when quantity set to zero', function () {
    $cart = $this->service->create($this->store);
    $line = $this->service->addLine($cart, $this->variant->id, 1);

    $this->service->updateLineQuantity($cart, $line->id, 0);

    expect($cart->lines()->count())->toBe(0);
});

it('removes a line from the cart', function () {
    $cart = $this->service->create($this->store);
    $line = $this->service->addLine($cart, $this->variant->id, 1);

    $this->service->removeLine($cart, $line->id);

    expect($cart->lines()->count())->toBe(0);
});

it('merges guest cart into customer cart', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);

    $guestCart = $this->service->create($this->store);
    $this->service->addLine($guestCart, $this->variant->id, 2);

    $customerCart = $this->service->create($this->store, $customer);

    $merged = $this->service->mergeOnLogin($guestCart, $customerCart);

    expect($merged->lines)->toHaveCount(1)
        ->and($merged->lines->first()->quantity)->toBe(2)
        ->and($guestCart->fresh()->status)->toBe(CartStatus::Abandoned);
});

it('merges with higher quantity on duplicate variant', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);

    $guestCart = $this->service->create($this->store);
    $this->service->addLine($guestCart, $this->variant->id, 5);

    $customerCart = $this->service->create($this->store, $customer);
    $this->service->addLine($customerCart, $this->variant->id, 2);

    $merged = $this->service->mergeOnLogin($guestCart, $customerCart);

    expect($merged->lines->first()->quantity)->toBe(5);
});

it('allows add when inventory policy is continue', function () {
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 1000,
        'status' => VariantStatus::Active,
    ]);
    InventoryItem::factory()->continuePolicy()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 0,
    ]);

    $cart = $this->service->create($this->store);
    $line = $this->service->addLine($cart, $variant->id, 10);

    expect($line->quantity)->toBe(10);
});
