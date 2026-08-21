<?php

use App\Enums\CheckoutStatus;
use App\Enums\DiscountValueType;
use App\Enums\VariantStatus;
use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\DiscountService;
use App\Services\RefundService;
use Database\Seeders\ShopSeeder;

uses(Tests\TestCase::class, \Illuminate\Foundation\Testing\LazilyRefreshDatabase::class);

beforeEach(function (): void {
    config(['cache.default' => 'array']);
    $this->seed(ShopSeeder::class);
    $this->store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    app()->instance('current_store', $this->store);
});

test('guest cart merges duplicate variants using the higher quantity and clears the session cart', function (): void {
    $variant = Product::query()->where('handle', 'classic-cotton-t-shirt')->firstOrFail()->variants()->firstOrFail();
    $customer = \App\Models\Customer::query()->where('email', 'customer@acme.test')->firstOrFail();
    $service = app(CartService::class);
    $guest = $service->create($this->store);
    $customerCart = $service->create($this->store, $customer);

    $service->addLine($guest, $variant->getKey(), 2);
    $service->addLine($customerCart, $variant->getKey(), 1);
    session(['cart_id_'.$this->store->getKey() => $guest->getKey()]);

    $merged = $service->mergeOnLogin($guest, $customerCart);

    expect($merged->lines->firstWhere('variant_id', $variant->getKey())->quantity)->toBe(2)
        ->and($guest->refresh()->status->value)->toBe('abandoned')
        ->and(session()->has('cart_id_'.$this->store->getKey()))->toBeFalse();
});

test('discount validation and allocation honor product restrictions', function (): void {
    $product = Product::query()->where('handle', 'classic-cotton-t-shirt')->firstOrFail();
    $variant = $product->variants()->firstOrFail();
    $cart = Cart::withoutGlobalScopes()->create(['store_id' => $this->store->getKey(), 'currency' => 'EUR', 'cart_version' => 1, 'status' => 'active']);
    $cart->lines()->create(['variant_id' => $variant->getKey(), 'quantity' => 1, 'unit_price_amount' => $variant->price_amount, 'line_subtotal_amount' => $variant->price_amount, 'line_total_amount' => $variant->price_amount]);
    $discount = Discount::withoutGlobalScopes()->create(['store_id' => $this->store->getKey(), 'code' => 'PRODUCT10', 'type' => 'code', 'value_type' => DiscountValueType::Percent, 'value_amount' => 10, 'status' => 'active', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDay(), 'rules_json' => ['applicable_product_ids' => [$product->getKey()]]]);

    expect(app(DiscountService::class)->validate($discount->code, $this->store, $cart)->is($discount))->toBeTrue()
        ->and(app(DiscountService::class)->calculate($discount, $variant->price_amount, [
            ['line_id' => 1, 'product_id' => $product->getKey(), 'amount' => 1000],
            ['line_id' => 2, 'product_id' => $product->getKey() + 999, 'amount' => 1000],
        ])->allocations)->toBe([1 => 100]);
});

test('discount validation returns the documented usage limit error code', function (): void {
    $discount = Discount::withoutGlobalScopes()->create(['store_id' => $this->store->getKey(), 'code' => 'MAXED-TEST', 'type' => 'code', 'value_type' => DiscountValueType::Percent, 'value_amount' => 10, 'status' => 'active', 'usage_limit' => 1, 'usage_count' => 1, 'starts_at' => now()->subDay(), 'ends_at' => now()->addDay(), 'rules_json' => []]);
    $cart = Cart::withoutGlobalScopes()->create(['store_id' => $this->store->getKey(), 'currency' => 'EUR', 'cart_version' => 1, 'status' => 'active']);

    try {
        app(DiscountService::class)->validate($discount->code, $this->store, $cart);
        expect(false)->toBeTrue();
    } catch (InvalidDiscountException $exception) {
        expect($exception->reason)->toBe('discount_usage_limit_reached');
    }
});

test('line refunds restock only the refunded quantity', function (): void {
    $order = Order::withoutGlobalScopes()->where('order_number', '#1001')->firstOrFail();
    $payment = $order->payments()->firstOrFail();
    $line = $order->lines()->firstOrFail();
    $inventory = InventoryItem::withoutGlobalScopes()->where('variant_id', $line->variant_id)->firstOrFail();
    $before = $inventory->quantity_on_hand;

    $refund = app(RefundService::class)->create($order, $payment, [$line->getKey() => 1], 'Damaged item', true);

    expect($refund->amount)->toBe(intdiv($line->line_total_amount, $line->quantity))
        ->and($inventory->refresh()->quantity_on_hand)->toBe($before + 1)
        ->and($order->refresh()->financial_status->value)->toBe('partially_refunded');
});

test('expiring a payment-selected checkout releases reserved inventory', function (): void {
    $variant = Product::query()->where('handle', 'classic-cotton-t-shirt')->firstOrFail()->variants()->firstOrFail();
    $inventory = InventoryItem::withoutGlobalScopes()->where('variant_id', $variant->getKey())->firstOrFail();
    $cart = app(CartService::class)->create($this->store);
    app(CartService::class)->addLine($cart, $variant->getKey(), 1);
    $checkout = Checkout::withoutGlobalScopes()->create(['store_id' => $this->store->getKey(), 'cart_id' => $cart->getKey(), 'email' => 'expire@example.test', 'status' => CheckoutStatus::PaymentSelected, 'expires_at' => now()->subMinute()]);
    app(\App\Services\InventoryService::class)->reserve($inventory, 1);

    app(CheckoutService::class)->expireCheckout($checkout);

    expect($checkout->refresh()->status)->toBe(CheckoutStatus::Expired)
        ->and($inventory->refresh()->quantity_reserved)->toBe(0);
});

test('cart additions reject archived variants', function (): void {
    $variant = Product::query()->where('handle', 'classic-cotton-t-shirt')->firstOrFail()->variants()->firstOrFail();
    $variant->update(['status' => VariantStatus::Archived]);

    expect(fn (): mixed => app(CartService::class)->addLine(app(CartService::class)->create($this->store), $variant->getKey(), 1))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
});
