<?php

use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\InventoryService;
use App\Services\OrderService;
use App\ValueObjects\PaymentResult;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates pending bank transfer orders with snapshot lines', function () {
    $store = Store::factory()->create();
    $product = Product::factory()->create(['store_id' => $store->id, 'title' => 'Snapshot Product']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'sku' => 'SNAP-1']);
    $inventory = InventoryItem::factory()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 3,
    ]);
    app(InventoryService::class)->reserve($inventory, 1);
    $cart = Cart::factory()->create(['store_id' => $store->id]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id]);
    $checkout = Checkout::factory()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'payment_method' => PaymentMethod::BankTransfer,
        'totals_json' => [
            'subtotal' => 1000, 'discount' => 0, 'shipping' => 0,
            'tax_total' => 0, 'total' => 1000, 'tax_lines' => [],
        ],
    ]);

    $order = app(OrderService::class)->createFromCheckout(
        $checkout,
        new PaymentResult(true, 'mock_pending', PaymentStatus::Pending),
    );

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($order->financial_status)->toBe(FinancialStatus::Pending)
        ->and($order->lines->first()->title_snapshot)->toBe('Snapshot Product')
        ->and($order->payments->first()->status)->toBe(PaymentStatus::Pending)
        ->and($inventory->refresh()->quantity_reserved)->toBe(1);
});
