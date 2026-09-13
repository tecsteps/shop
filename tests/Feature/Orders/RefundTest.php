<?php

use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\RefundStatus;
use App\Enums\StoreUserRole;
use App\Models\Refund;
use App\Models\StoreUser;
use App\Services\CheckoutService;
use App\Services\RefundService;
use Tests\Support\CatalogTestHelper;
use Tests\Support\CommerceTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(Illuminate\Routing\Middleware\ThrottleRequests::class);
});

function paidOrderContext(array $lines = [['price' => 2500, 'qty' => 2]]): array
{
    ['store' => $store] = test()->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store, $lines);
    $rate = CommerceTestHelper::flatShippingRate($store, ['DE'], 0);

    $checkouts = app(CheckoutService::class);
    $checkout = $checkouts->createFromCart($cart, 'customer@example.com');
    $checkout = $checkouts->setAddress($checkout, CommerceTestHelper::address());
    $checkout = $checkouts->setShippingMethod($checkout, $rate->id);
    $checkout = $checkouts->selectPaymentMethod($checkout, PaymentMethod::CreditCard);
    $order = $checkouts->pay($checkout, PaymentMethod::CreditCard, ['card_number' => '4242424242424242']);

    return ['store' => $store, 'cart' => $cart, 'order' => $order];
}

it('creates a full refund', function () {
    $ctx = paidOrderContext();
    $order = $ctx['order'];

    $refund = app(RefundService::class)->create($order, $order->total_amount, null, 'Full refund');

    expect($refund->status)->toBe(RefundStatus::Processed);
    expect($refund->provider_refund_id)->toStartWith('mock_rf_');
    expect($order->refresh()->financial_status)->toBe(FinancialStatus::Refunded);
    expect($order->refresh()->status)->toBe(OrderStatus::Refunded);
});

it('creates a partial refund', function () {
    $ctx = paidOrderContext();
    $order = $ctx['order'];

    app(RefundService::class)->create($order, 2000);

    expect($order->refresh()->financial_status)->toBe(FinancialStatus::PartiallyRefunded);
});

it('rejects refund exceeding payment amount', function () {
    $ctx = paidOrderContext();

    app(RefundService::class)->create($ctx['order'], $ctx['order']->total_amount + 1);
})->throws(Illuminate\Validation\ValidationException::class);

it('restocks inventory when restock flag is true', function () {
    $ctx = paidOrderContext();
    $order = $ctx['order'];
    $line = $order->orderLines()->first();

    app(RefundService::class)->create($order, 2500, [$line->id => 1], null, true);

    $item = App\Models\InventoryItem::query()
        ->where('variant_id', $ctx['cart']->cartLines()->first()->variant_id)
        ->first();

    expect($item->quantity_on_hand)->toBe(49);
});

it('does not restock when restock flag is false', function () {
    $ctx = paidOrderContext();
    $order = $ctx['order'];
    $line = $order->orderLines()->first();

    app(RefundService::class)->create($order, 2500, [$line->id => 1], null, false);

    $item = App\Models\InventoryItem::query()
        ->where('variant_id', $ctx['cart']->cartLines()->first()->variant_id)
        ->first();

    expect($item->quantity_on_hand)->toBe(48);
});

it('only allows admin or owner to process refunds', function () {
    ['store' => $store] = $this->createStoreContext();

    $cart = CommerceTestHelper::cartWithLines($store);
    $rate = CommerceTestHelper::flatShippingRate($store, ['DE'], 0);

    $checkouts = app(CheckoutService::class);
    $checkout = $checkouts->createFromCart($cart, 'customer@example.com');
    $checkout = $checkouts->setAddress($checkout, CommerceTestHelper::address());
    $checkout = $checkouts->setShippingMethod($checkout, $rate->id);
    $checkout = $checkouts->selectPaymentMethod($checkout, PaymentMethod::CreditCard);
    $order = $checkouts->pay($checkout, PaymentMethod::CreditCard, ['card_number' => '4242424242424242']);

    $staff = App\Models\User::factory()->create();
    StoreUser::factory()->create(['store_id' => $store->id, 'user_id' => $staff->id, 'role' => StoreUserRole::Staff]);

    $response = $this->postJson(
        "/api/admin/v1/stores/{$store->id}/orders/{$order->id}/refunds",
        ['amount' => 1000],
        CatalogTestHelper::adminHeaders($staff, ['write-orders'])
    );

    $response->assertForbidden();
});

it('records refund reason', function () {
    $ctx = paidOrderContext();

    $refund = app(RefundService::class)->create($ctx['order'], 1000, null, 'Customer requested');

    expect($refund->reason)->toBe('Customer requested');
    expect(Refund::query()->whereKey($refund->id)->exists())->toBeTrue();
});
