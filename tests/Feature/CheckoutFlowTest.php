<?php

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\TaxSettings;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('completes checkout atomically and idempotently', function () {
    $store = Store::factory()->create();
    $product = Product::factory()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2000]);
    $inventory = InventoryItem::factory()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 5,
    ]);
    $cart = Cart::factory()->create(['store_id' => $store->id]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'unit_price_amount' => 2000,
        'line_subtotal_amount' => 2000,
        'line_total_amount' => 2000,
    ]);
    $zone = ShippingZone::factory()->create(['store_id' => $store->id]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id]);
    TaxSettings::factory()->create(['store_id' => $store->id]);
    $service = app(CheckoutService::class);
    $checkout = $service->create($cart);
    $checkout = $service->setAddress($checkout, [
        'email' => 'buyer@example.com',
        'shipping_address' => [
            'first_name' => 'Test',
            'last_name' => 'Buyer',
            'address1' => '1 Main St',
            'city' => 'New York',
            'country' => 'US',
            'country_code' => 'US',
            'postal_code' => '10001',
        ],
    ]);
    $checkout = $service->setShippingMethod($checkout, $rate->id);
    $checkout = $service->selectPaymentMethod($checkout, PaymentMethod::CreditCard);
    $order = $service->completeCheckout($checkout, ['card_number' => '4242424242424242']);
    $sameOrder = $service->completeCheckout($checkout->refresh(), ['card_number' => '4242424242424242']);

    expect($checkout->refresh()->status)->toBe(CheckoutStatus::Completed)
        ->and($cart->refresh()->status)->toBe(CartStatus::Converted)
        ->and($sameOrder->id)->toBe($order->id)
        ->and($inventory->refresh()->quantity_on_hand)->toBe(4)
        ->and($inventory->quantity_reserved)->toBe(0)
        ->and($store->orders()->count())->toBe(1);
});
