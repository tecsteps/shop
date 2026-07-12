<?php

use App\Enums\PaymentMethod;
use App\Enums\ProductStatus;
use App\Events\OrderPaid;
use App\Exceptions\DomainException;
use App\Exceptions\InvalidDiscountException;
use App\Livewire\Admin\Collections\Form as CollectionForm;
use App\Livewire\Admin\Settings\Checkout as CheckoutSettings;
use App\Livewire\Admin\Settings\Notifications as NotificationSettings;
use App\Models\Collection;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Product;
use App\Models\StoreSettings;
use App\Models\TaxSettings;
use App\Models\WebhookSubscription;
use App\Services\CheckoutService;
use App\Services\FulfillmentService;
use App\Services\ProductService;
use App\Services\RefundService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

it('stacks code and automatic discounts sequentially and snapshots allocations and taxes per line', function () {
    $store = createStoreContext()['store'];
    $sku = makeSellableVariant($store, variantAttributes: [
        'price_amount' => 10000,
        'requires_shipping' => false,
        'weight_g' => 0,
    ]);
    ['cart' => $cart] = makeCartWithLine($store, $sku['variant']);
    $code = Discount::factory()->for($store)->create(['code' => 'STACK10', 'value_type' => 'percent', 'value_amount' => 10]);
    $fixed = Discount::factory()->for($store)->automatic()->fixed(1000)->create();
    $automaticPercent = Discount::factory()->for($store)->automatic()->create(['value_type' => 'percent', 'value_amount' => 10]);
    TaxSettings::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'mode' => 'manual',
        'provider' => 'none',
        'prices_include_tax' => false,
        'config_json' => ['rate_bps' => 1900, 'label' => 'VAT'],
    ]);

    $service = app(CheckoutService::class);
    $checkout = $service->create($cart);
    $checkout = $service->setAddress($checkout, validCheckoutAddress());
    $checkout = $service->setShippingMethod($checkout, null);
    $checkout = $service->applyDiscount($checkout, 'STACK10');

    expect($checkout->totals_json['discount'])->toBe(2800)
        ->and($checkout->totals_json['tax_total'])->toBe(1368)
        ->and($checkout->totals_json['total'])->toBe(8568)
        ->and($checkout->totals_json['applied_discounts'])->toHaveCount(3)
        ->and($checkout->tax_provider_snapshot_json)->toMatchArray([
            'provider' => 'manual',
            'shipping_tax_amount' => 0,
            'shipping_tax_rate' => 1900,
        ])
        ->and($checkout->tax_provider_snapshot_json['lines'][0])->toMatchArray([
            'variant_id' => $sku['variant']->id,
            'tax_amount' => 1368,
            'rate' => 1900,
            'jurisdiction' => 'DE',
        ]);

    $checkout = $service->selectPaymentMethod($checkout, PaymentMethod::CreditCard);
    $order = $service->completeCheckout($checkout, ['card_number' => '4242424242424242']);
    $line = $order->lines->first();

    expect($line->total_amount)->toBe(7200)
        ->and($line->tax_lines_json)->toBe([['name' => 'VAT', 'rate' => 1900, 'amount' => 1368]])
        ->and(collect($line->discount_allocations_json)->pluck('discount_id')->all())->toBe([$code->id, $fixed->id, $automaticPercent->id])
        ->and(collect($line->discount_allocations_json)->sum('amount'))->toBe(2800)
        ->and($code->refresh()->usage_count)->toBe(1)
        ->and($fixed->refresh()->usage_count)->toBe(1)
        ->and($automaticPercent->refresh()->usage_count)->toBe(1);
});

it('enforces once per customer and never increments usage on rejected reuse', function () {
    $store = createStoreContext()['store'];
    $customer = Customer::factory()->for($store)->create();
    $discount = Discount::factory()->for($store)->create([
        'code' => 'ONCEONLY',
        'rules_json' => ['once_per_customer' => true],
    ]);
    $service = app(CheckoutService::class);

    $makeCheckout = function () use ($store, $customer, $service) {
        $sku = makeSellableVariant($store, variantAttributes: ['requires_shipping' => false, 'weight_g' => 0]);
        ['cart' => $cart] = makeCartWithLine($store, $sku['variant']);
        $cart->update(['customer_id' => $customer->id]);
        $checkout = $service->create($cart);
        $checkout = $service->setAddress($checkout, validCheckoutAddress());
        $checkout = $service->setShippingMethod($checkout, null);

        return [$checkout, $sku];
    };

    [$first] = $makeCheckout();
    $first = $service->applyDiscount($first, 'ONCEONLY');
    $first = $service->selectPaymentMethod($first, PaymentMethod::CreditCard);
    $service->completeCheckout($first, ['card_number' => '4242424242424242']);

    [$second] = $makeCheckout();
    expect(fn () => $service->applyDiscount($second, 'ONCEONLY'))
        ->toThrow(InvalidDiscountException::class)
        ->and($discount->refresh()->usage_count)->toBe(1);
});

it('requires refund line quantities for restocking and cannot restock a line twice beyond its quantity', function () {
    $flow = checkoutReadyForPayment(PaymentMethod::CreditCard, digital: false, quantity: 2);
    $order = app(CheckoutService::class)->completeCheckout($flow['checkout'], ['card_number' => '4242424242424242']);
    $payment = $order->payments->first();
    $line = $order->lines->first();
    $inventory = $flow['variant']->inventoryItem->refresh();
    $before = $inventory->quantity_on_hand;
    $refunds = app(RefundService::class);

    expect(fn () => $refunds->create($order, $payment, 1000, restock: true))
        ->toThrow(DomainException::class)
        ->and($inventory->refresh()->quantity_on_hand)->toBe($before);

    $refunds->create($order, $payment, 1000, restock: true, lines: [$line->id => 1]);
    $refunds->create($order, $payment, 1000, restock: true, lines: [$line->id => 1]);

    expect($inventory->refresh()->quantity_on_hand)->toBe($before + 2)
        ->and(fn () => $refunds->create($order, $payment, 1000, restock: true, lines: [$line->id => 1]))
        ->toThrow(DomainException::class)
        ->and($inventory->refresh()->quantity_on_hand)->toBe($before + 2);
});

it('keeps exactly one default variant and rejects option matrices over one hundred combinations', function () {
    $store = createStoreContext()['store'];
    $service = app(ProductService::class);
    $product = $service->create($store, [
        'title' => 'Matrix Product',
        'options' => [
            ['name' => 'Size', 'values' => ['S', 'M']],
            ['name' => 'Color', 'values' => ['Blue', 'Red']],
        ],
    ]);

    expect($product->variants)->toHaveCount(4)
        ->and($product->variants->where('is_default', true))->toHaveCount(1)
        ->and($product->variants->where('is_default', true)->first()->position)->toBe(0);

    $requestedDefault = $product->variants->last();
    $product = $service->update($product, [
        'variants' => $product->variants->values()->map(fn ($variant): array => [
            'id' => $variant->id,
            'price_amount' => $variant->price_amount,
            'is_default' => $variant->is($requestedDefault),
        ])->all(),
    ]);
    expect($product->variants->where('is_default', true))->toHaveCount(1)
        ->and($product->variants->firstWhere('is_default', true)->id)->toBe($requestedDefault->id);

    expect(fn () => $service->create($store, [
        'title' => 'Too Many Variants',
        'options' => [
            ['name' => 'One', 'values' => range(1, 5)],
            ['name' => 'Two', 'values' => range(1, 5)],
            ['name' => 'Three', 'values' => range(1, 5)],
        ],
    ]))->toThrow(InvalidArgumentException::class)
        ->and(Product::withoutGlobalScopes()->where('store_id', $store->id)->where('title', 'Too Many Variants')->exists())->toBeFalse();
});

it('rejects cross-store collection product associations in services and Livewire', function () {
    $contextA = createStoreContext();
    $foreign = createStoreContext();
    $foreignProduct = Product::factory()->for($foreign['store'])->create();
    $foreignCollection = Collection::factory()->for($foreign['store'])->create();
    bindStore($contextA['store']);
    actingAsAdmin($contextA['user'], $contextA['store']);

    expect(fn () => app(ProductService::class)->create($contextA['store'], [
        'title' => 'Scoped Product',
        'collections' => [$foreignCollection->id],
    ]))->toThrow(InvalidArgumentException::class);

    Livewire::test(CollectionForm::class)
        ->call('addProduct', $foreignProduct->id)
        ->assertNotFound();
});

it('supports documented incremental collection membership while rejecting foreign products', function () {
    $context = createStoreContext();
    $foreign = createStoreContext();
    $product = Product::factory()->for($context['store'])->create();
    $foreignProduct = Product::factory()->for($foreign['store'])->create();
    $collection = Collection::factory()->for($context['store'])->create();
    $token = adminApiToken($context, ['write-collections']);

    $this->withToken($token)->putJson(adminStoreUrl($context['store'], "collections/{$collection->id}"), [
        'add_product_ids' => [$foreignProduct->id],
    ])->assertUnprocessable();
    $this->withToken($token)->putJson(adminStoreUrl($context['store'], "collections/{$collection->id}"), [
        'add_product_ids' => [$product->id],
    ])->assertOk()->assertJsonPath('data.products.0.id', $product->id);
    $this->withToken($token)->putJson(adminStoreUrl($context['store'], "collections/{$collection->id}"), [
        'remove_product_ids' => [$product->id],
    ])->assertOk()->assertJsonCount(0, 'data.products');
});

it('maps paid fulfilled and archived events without firing fulfilled webhooks for partial fulfillment', function () {
    Notification::fake();
    Queue::fake();
    $fixture = paidOrderFixture();
    foreach (['order.paid', 'order.fulfilled', 'product.deleted'] as $eventType) {
        WebhookSubscription::withoutGlobalScopes()->create([
            'store_id' => $fixture['store']->id,
            'event_type' => $eventType,
            'target_url' => 'https://hooks.example/'.$eventType,
            'signing_secret_encrypted' => 'secret',
            'status' => 'active',
        ]);
    }

    event(new OrderPaid($fixture['order']));
    expect(WebhookSubscription::withoutGlobalScopes()->where('event_type', 'order.paid')->first()->deliveries)->toHaveCount(1);

    $fulfillments = app(FulfillmentService::class);
    $partial = $fulfillments->create($fixture['order'], [$fixture['line']->id => 1]);
    $fulfillments->markAsShipped($partial);
    expect(WebhookSubscription::withoutGlobalScopes()->where('event_type', 'order.fulfilled')->first()->deliveries)->toHaveCount(0);

    $fulfillments->create($fixture['order']->refresh(), [$fixture['line']->id => 1]);
    expect(WebhookSubscription::withoutGlobalScopes()->where('event_type', 'order.fulfilled')->first()->deliveries)->toHaveCount(1);

    app(ProductService::class)->transitionStatus($fixture['variant']->product, ProductStatus::Archived);
    expect(WebhookSubscription::withoutGlobalScopes()->where('event_type', 'product.deleted')->first()->deliveries)->toHaveCount(1);
});

it('persists checkout and notification settings in tenant store settings', function () {
    $context = createStoreContext();
    actingAsAdmin($context['user'], $context['store']);

    Livewire::test(CheckoutSettings::class)
        ->set('allowGuestCheckout', false)
        ->set('requirePhone', true)
        ->set('checkoutExpiryHours', 48)
        ->call('save')
        ->assertHasNoErrors();
    Livewire::test(NotificationSettings::class)
        ->set('refundConfirmation', false)
        ->set('notifyStaffOfNewOrders', false)
        ->call('save')
        ->assertHasNoErrors();

    $settings = StoreSettings::withoutGlobalScopes()->where('store_id', $context['store']->id)->value('settings_json');
    expect(data_get($settings, 'checkout.allow_guest_checkout'))->toBeFalse()
        ->and(data_get($settings, 'checkout.require_phone'))->toBeTrue()
        ->and(data_get($settings, 'checkout.expiry_hours'))->toBe(48)
        ->and(data_get($settings, 'notifications.refund_confirmation'))->toBeFalse()
        ->and(data_get($settings, 'notifications.staff_new_order'))->toBeFalse();
});
