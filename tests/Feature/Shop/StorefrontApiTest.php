<?php

use App\Models\AnalyticsEvent;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\StoreDomain;
use App\Services\CartService;
use App\Services\CheckoutService;

/** @param array{domain: StoreDomain} $context */
function storefrontApiUrl(array $context, string $path): string
{
    return 'http://'.$context['domain']->hostname.'/api/storefront/v1/'.ltrim($path, '/');
}

/** @return array{cart_id: int, email: string} */
function storefrontCheckoutPayload(int $cartId, string $email = 'buyer@example.test'): array
{
    return ['cart_id' => $cartId, 'email' => $email];
}

/** @return array{payment_method: string, card_number: string, card_expiry: string, card_cvc: string, card_holder: string} */
function storefrontCardPayment(string $number): array
{
    return [
        'payment_method' => 'credit_card',
        'card_number' => $number,
        'card_expiry' => '12/30',
        'card_cvc' => '123',
        'card_holder' => 'Ada Lovelace',
    ];
}

describe('storefront cart API', function () {
    it('creates retrieves mutates and versions a cart', function () {
        $context = createStoreContext();
        $sku = makeSellableVariant($context['store'], variantAttributes: ['price_amount' => 2500]);

        $created = $this->postJson(storefrontApiUrl($context, 'carts'))
            ->assertCreated()
            ->assertJsonPath('cart_version', 1)
            ->assertJsonPath('currency', $context['store']->default_currency)
            ->assertJsonCount(0, 'lines');
        $cartId = $created->json('id');

        $added = $this->postJson(storefrontApiUrl($context, "carts/{$cartId}/lines"), [
            'variant_id' => $sku['variant']->id,
            'quantity' => 2,
            'expected_version' => 1,
        ])->assertCreated()
            ->assertJsonPath('cart_version', 2)
            ->assertJsonPath('totals.subtotal', 5000)
            ->assertJsonPath('lines.0.quantity', 2)
            ->assertJsonPath('lines.0.line_total_amount', 5000);
        $lineId = $added->json('lines.0.id');

        $this->getJson(storefrontApiUrl($context, "carts/{$cartId}"))
            ->assertOk()
            ->assertJsonCount(1, 'lines')
            ->assertJsonPath('lines.0.product_title', $sku['product']->title);

        $this->putJson(storefrontApiUrl($context, "carts/{$cartId}/lines/{$lineId}"), [
            'quantity' => 0,
            'cart_version' => 2,
        ])->assertUnprocessable()->assertJsonValidationErrors('quantity');

        $this->putJson(storefrontApiUrl($context, "carts/{$cartId}/lines/{$lineId}"), [
            'quantity' => 3,
        ])->assertUnprocessable()->assertJsonValidationErrors('cart_version');

        $this->putJson(storefrontApiUrl($context, "carts/{$cartId}/lines/{$lineId}"), [
            'quantity' => 3,
            'cart_version' => 2,
        ])->assertOk()
            ->assertJsonPath('cart_version', 3)
            ->assertJsonPath('lines.0.quantity', 3)
            ->assertJsonPath('lines.0.line_subtotal_amount', 7500);

        $this->putJson(storefrontApiUrl($context, "carts/{$cartId}/lines/{$lineId}"), [
            'quantity' => 4,
            'cart_version' => 2,
        ])->assertConflict()
            ->assertJsonPath('data.cart_version', 3)
            ->assertJsonPath('data.lines.0.quantity', 3);

        $this->deleteJson(storefrontApiUrl($context, "carts/{$cartId}/lines/{$lineId}"), [
            'cart_version' => 3,
        ])->assertOk()
            ->assertJsonPath('cart_version', 4)
            ->assertJsonCount(0, 'lines');
    });

    it('returns validation responses for invalid quantities variants and inventory', function () {
        $context = createStoreContext();
        $sku = makeSellableVariant($context['store'], inventoryAttributes: ['quantity_on_hand' => 1]);
        $cart = app(CartService::class)->create($context['store']);
        $this->withSession(['cart_id' => $cart->id, 'api_cart_ids' => [$cart->id]]);

        $this->postJson(storefrontApiUrl($context, "carts/{$cart->id}/lines"), [
            'variant_id' => 999999,
            'quantity' => 1,
        ])->assertUnprocessable()->assertJsonValidationErrors('variant_id');

        $this->postJson(storefrontApiUrl($context, "carts/{$cart->id}/lines"), [
            'variant_id' => $sku['variant']->id,
            'quantity' => 0,
        ])->assertUnprocessable()->assertJsonValidationErrors('quantity');

        $this->postJson(storefrontApiUrl($context, "carts/{$cart->id}/lines"), [
            'variant_id' => $sku['variant']->id,
            'quantity' => 2,
        ])->assertUnprocessable()->assertJsonValidationErrors('quantity');
    });

    it('does not reveal carts or variants from another tenant', function () {
        $contextA = createStoreContext();
        $contextB = createStoreContext();
        $foreignSku = makeSellableVariant($contextB['store']);
        $foreignCart = app(CartService::class)->create($contextB['store']);
        bindStore($contextA['store']);
        $cart = app(CartService::class)->create($contextA['store']);
        $this->withSession(['cart_id' => $cart->id, 'api_cart_ids' => [$cart->id]]);

        $this->getJson(storefrontApiUrl($contextA, "carts/{$foreignCart->id}"))->assertNotFound();
        $this->postJson(storefrontApiUrl($contextA, "carts/{$cart->id}/lines"), [
            'variant_id' => $foreignSku['variant']->id,
            'quantity' => 1,
        ])->assertUnprocessable()->assertJsonValidationErrors('variant_id');
        $this->getJson(storefrontApiUrl($contextA, 'carts/999999'))->assertNotFound();
    });
});

describe('storefront cart and checkout ownership', function () {
    it('does not let one anonymous session show or mutate another sessions cart or checkout', function () {
        $context = createStoreContext();
        $sku = makeSellableVariant($context['store']);

        $cartAId = $this->postJson(storefrontApiUrl($context, 'carts'))
            ->assertCreated()
            ->json('id');
        $lineAId = $this->postJson(storefrontApiUrl($context, "carts/{$cartAId}/lines"), [
            'variant_id' => $sku['variant']->id,
            'quantity' => 1,
            'expected_version' => 1,
        ])->assertCreated()->json('lines.0.id');
        $checkoutAId = $this->postJson(storefrontApiUrl($context, 'checkouts'), storefrontCheckoutPayload($cartAId, 'session-a@example.test'))
            ->assertCreated()
            ->assertJsonPath('email', 'session-a@example.test')
            ->json('id');

        $this->flushSession();
        $cartBId = $this->postJson(storefrontApiUrl($context, 'carts'))
            ->assertCreated()
            ->json('id');
        expect($cartBId)->not->toBe($cartAId);

        $this->getJson(storefrontApiUrl($context, "carts/{$cartAId}"))->assertNotFound();
        $this->postJson(storefrontApiUrl($context, "carts/{$cartAId}/lines"), [
            'variant_id' => $sku['variant']->id,
            'quantity' => 1,
            'expected_version' => 2,
        ])->assertNotFound();
        $this->putJson(storefrontApiUrl($context, "carts/{$cartAId}/lines/{$lineAId}"), [
            'quantity' => 5,
            'cart_version' => 2,
        ])->assertNotFound();
        $this->postJson(storefrontApiUrl($context, 'checkouts'), storefrontCheckoutPayload($cartAId))->assertNotFound();
        $this->getJson(storefrontApiUrl($context, "checkouts/{$checkoutAId}"))->assertNotFound();
        $this->putJson(storefrontApiUrl($context, "checkouts/{$checkoutAId}/address"), validCheckoutAddress())
            ->assertNotFound();

        $cartA = Cart::withoutGlobalScopes()->with('lines')->findOrFail($cartAId);
        $checkoutA = Checkout::withoutGlobalScopes()->findOrFail($checkoutAId);
        expect($cartA->cart_version)->toBe(2)
            ->and($cartA->lines)->toHaveCount(1)
            ->and($cartA->lines->first()->quantity)->toBe(1)
            ->and($checkoutA->status->value)->toBe('started')
            ->and($checkoutA->email)->toBe('session-a@example.test');
    });

    it('does not let an authenticated customer access another customers cart or checkout', function () {
        $context = createStoreContext();
        $sku = makeSellableVariant($context['store']);
        $customerA = Customer::factory()->for($context['store'])->create();
        $customerB = Customer::factory()->for($context['store'])->create();
        $carts = app(CartService::class);
        $checkouts = app(CheckoutService::class);

        $cartA = $carts->create($context['store'], $customerA);
        $carts->addLine($cartA, $sku['variant'], 1);
        $checkoutA = $checkouts->create($cartA);
        $cartB = $carts->create($context['store'], $customerB);
        $lineB = $carts->addLine($cartB, $sku['variant'], 1);
        $checkoutB = $checkouts->create($cartB);

        actingAsCustomer($customerA);
        $this->withSession(['cart_id' => $cartB->id]);

        $this->getJson(storefrontApiUrl($context, "carts/{$cartA->id}"))->assertOk();
        $this->getJson(storefrontApiUrl($context, "checkouts/{$checkoutA->id}"))->assertOk();
        $this->getJson(storefrontApiUrl($context, "carts/{$cartB->id}"))->assertNotFound();
        $this->postJson(storefrontApiUrl($context, "carts/{$cartB->id}/lines"), [
            'variant_id' => $sku['variant']->id,
            'quantity' => 1,
            'expected_version' => 2,
        ])->assertNotFound();
        $this->putJson(storefrontApiUrl($context, "carts/{$cartB->id}/lines/{$lineB->id}"), [
            'quantity' => 5,
            'cart_version' => 2,
        ])->assertNotFound();
        $this->postJson(storefrontApiUrl($context, 'checkouts'), storefrontCheckoutPayload($cartB->id))->assertNotFound();
        $this->getJson(storefrontApiUrl($context, "checkouts/{$checkoutB->id}"))->assertNotFound();
        $this->putJson(storefrontApiUrl($context, "checkouts/{$checkoutB->id}/address"), validCheckoutAddress())
            ->assertNotFound();

        expect($cartB->refresh()->cart_version)->toBe(2)
            ->and($lineB->refresh()->quantity)->toBe(1)
            ->and($checkoutB->refresh()->status->value)->toBe('started')
            ->and($checkoutB->email)->toBeNull();
    });
});

describe('storefront checkout API', function () {
    it('keeps one live checkout per cart and refreshes its starting email', function () {
        $context = createStoreContext();
        $sku = makeSellableVariant($context['store']);
        ['cart' => $cart] = makeCartWithLine($context['store'], $sku['variant']);
        $this->withSession(['cart_id' => $cart->id, 'api_cart_ids' => [$cart->id]]);

        $first = $this->postJson(
            storefrontApiUrl($context, 'checkouts'),
            storefrontCheckoutPayload($cart->id, 'first@example.test'),
        )->assertCreated();
        $second = $this->postJson(
            storefrontApiUrl($context, 'checkouts'),
            storefrontCheckoutPayload($cart->id, 'second@example.test'),
        )->assertCreated();

        expect($second->json('id'))->toBe($first->json('id'))
            ->and($second->json('email'))->toBe('second@example.test')
            ->and($cart->checkouts()->whereNotIn('status', ['completed', 'expired'])->count())->toBe(1);
    });

    it('rejects every cart mutation after payment selection', function () {
        $context = createStoreContext();
        $sku = makeSellableVariant($context['store']);
        ['cart' => $cart, 'line' => $line] = makeCartWithLine($context['store'], $sku['variant']);
        $this->withSession(['cart_id' => $cart->id, 'api_cart_ids' => [$cart->id]]);
        $zone = ShippingZone::factory()->for($context['store'])->create(['countries_json' => ['DE']]);
        $rate = ShippingRate::factory()->for($zone, 'zone')->create();
        $checkoutId = $this->postJson(storefrontApiUrl($context, 'checkouts'), storefrontCheckoutPayload($cart->id))
            ->assertCreated()->json('id');
        $this->putJson(storefrontApiUrl($context, "checkouts/{$checkoutId}/address"), validCheckoutAddress())->assertOk();
        $this->putJson(storefrontApiUrl($context, "checkouts/{$checkoutId}/shipping-method"), ['shipping_method_id' => $rate->id])->assertOk();
        $this->putJson(storefrontApiUrl($context, "checkouts/{$checkoutId}/payment-method"), ['payment_method' => 'credit_card'])
            ->assertOk()->assertJsonPath('status', 'payment_selected');

        $this->postJson(storefrontApiUrl($context, "carts/{$cart->id}/lines"), [
            'variant_id' => $sku['variant']->id,
            'quantity' => 1,
            'expected_version' => 2,
        ])->assertUnprocessable()->assertJsonValidationErrors('variant_id');
        $this->putJson(storefrontApiUrl($context, "carts/{$cart->id}/lines/{$line->id}"), [
            'quantity' => 5,
            'cart_version' => 2,
        ])->assertUnprocessable()->assertJsonValidationErrors('cart');
        $this->deleteJson(storefrontApiUrl($context, "carts/{$cart->id}/lines/{$line->id}"), [
            'cart_version' => 2,
        ])->assertUnprocessable()->assertJsonValidationErrors('cart');

        expect($cart->refresh()->cart_version)->toBe(2)
            ->and($line->refresh()->quantity)->toBe(1)
            ->and($sku['inventory']->refresh()->quantity_reserved)->toBe(1);
    });

    it('returns gone for an expired checkout', function () {
        $context = createStoreContext();
        $sku = makeSellableVariant($context['store']);
        ['cart' => $cart] = makeCartWithLine($context['store'], $sku['variant']);
        $this->withSession(['cart_id' => $cart->id, 'api_cart_ids' => [$cart->id]]);
        $checkoutId = $this->postJson(storefrontApiUrl($context, 'checkouts'), storefrontCheckoutPayload($cart->id))
            ->assertCreated()->json('id');
        Checkout::withoutGlobalScopes()->whereKey($checkoutId)->update(['expires_at' => now()->subMinute()]);

        $this->getJson(storefrontApiUrl($context, "checkouts/{$checkoutId}"))->assertGone();
        $this->putJson(storefrontApiUrl($context, "checkouts/{$checkoutId}/address"), validCheckoutAddress())->assertGone();
    });

    it('requires complete matching payment fields before charging', function () {
        $context = createStoreContext();
        $sku = makeSellableVariant($context['store']);
        ['cart' => $cart] = makeCartWithLine($context['store'], $sku['variant']);
        $this->withSession(['cart_id' => $cart->id, 'api_cart_ids' => [$cart->id]]);
        $zone = ShippingZone::factory()->for($context['store'])->create(['countries_json' => ['DE']]);
        $rate = ShippingRate::factory()->for($zone, 'zone')->create();
        $checkoutId = $this->postJson(storefrontApiUrl($context, 'checkouts'), storefrontCheckoutPayload($cart->id))
            ->assertCreated()->json('id');
        $this->putJson(storefrontApiUrl($context, "checkouts/{$checkoutId}/address"), validCheckoutAddress())->assertOk();
        $this->putJson(storefrontApiUrl($context, "checkouts/{$checkoutId}/shipping-method"), ['shipping_method_id' => $rate->id])->assertOk();
        $this->putJson(storefrontApiUrl($context, "checkouts/{$checkoutId}/payment-method"), ['payment_method' => 'credit_card'])->assertOk();

        $this->postJson(storefrontApiUrl($context, "checkouts/{$checkoutId}/pay"), [])
            ->assertUnprocessable()->assertJsonValidationErrors('payment_method');
        $this->postJson(storefrontApiUrl($context, "checkouts/{$checkoutId}/pay"), ['payment_method' => 'credit_card'])
            ->assertUnprocessable()->assertJsonValidationErrors(['card_number', 'card_expiry', 'card_cvc', 'card_holder']);
        $this->postJson(storefrontApiUrl($context, "checkouts/{$checkoutId}/pay"), ['payment_method' => 'paypal'])
            ->assertUnprocessable()->assertJsonValidationErrors('payment_method');

        expect(Order::withoutGlobalScopes()->where('store_id', $context['store']->id)->count())->toBe(0)
            ->and(Payment::query()->count())->toBe(0)
            ->and($sku['inventory']->refresh()->quantity_reserved)->toBe(1);
    });

    it('progresses a digital-only checkout without a shipping address or rate', function () {
        $context = createStoreContext();
        $sku = makeSellableVariant($context['store'], variantAttributes: [
            'requires_shipping' => false,
            'weight_g' => 0,
        ]);
        ['cart' => $cart] = makeCartWithLine($context['store'], $sku['variant']);
        $this->withSession(['cart_id' => $cart->id, 'api_cart_ids' => [$cart->id]]);
        $checkoutId = $this->postJson(storefrontApiUrl($context, 'checkouts'), storefrontCheckoutPayload($cart->id, 'reader@example.test'))
            ->assertCreated()->json('id');

        $this->putJson(storefrontApiUrl($context, "checkouts/{$checkoutId}/address"), [])
            ->assertOk()
            ->assertJsonPath('status', 'addressed')
            ->assertJsonPath('shipping_address_json', null)
            ->assertJsonCount(0, 'available_shipping_methods');
        $this->putJson(storefrontApiUrl($context, "checkouts/{$checkoutId}/shipping-method"), ['shipping_method_id' => null])
            ->assertOk()
            ->assertJsonPath('status', 'shipping_selected')
            ->assertJsonPath('shipping_method_id', null)
            ->assertJsonPath('totals.shipping', 0);
        $this->putJson(storefrontApiUrl($context, "checkouts/{$checkoutId}/payment-method"), ['payment_method' => 'paypal'])
            ->assertOk();
        $this->postJson(storefrontApiUrl($context, "checkouts/{$checkoutId}/pay"), ['payment_method' => 'paypal'])
            ->assertOk()
            ->assertJsonPath('order.financial_status', 'paid');
    });

    it('completes and idempotently repeats a discounted card checkout', function () {
        $context = createStoreContext();
        $sku = makeSellableVariant($context['store'], variantAttributes: ['price_amount' => 2500]);
        ['cart' => $cart] = makeCartWithLine($context['store'], $sku['variant'], 2);
        $this->withSession(['cart_id' => $cart->id, 'api_cart_ids' => [$cart->id]]);
        $zone = ShippingZone::factory()->for($context['store'])->create(['countries_json' => ['DE']]);
        $rate = ShippingRate::factory()->for($zone, 'zone')->create(['config_json' => ['amount' => 499]]);
        Discount::factory()->for($context['store'])->create(['code' => 'API10', 'value_type' => 'percent', 'value_amount' => 10]);

        $created = $this->postJson(storefrontApiUrl($context, 'checkouts'), storefrontCheckoutPayload($cart->id))
            ->assertCreated()
            ->assertJsonPath('status', 'started')
            ->assertJsonPath('email', 'buyer@example.test');
        $checkoutId = $created->json('id');

        $this->putJson(storefrontApiUrl($context, "checkouts/{$checkoutId}/address"), validCheckoutAddress())
            ->assertOk()
            ->assertJsonPath('status', 'addressed')
            ->assertJsonPath('available_shipping_methods.0.id', $rate->id)
            ->assertJsonPath('available_shipping_methods.0.price_amount', 499);

        $this->putJson(storefrontApiUrl($context, "checkouts/{$checkoutId}/shipping-method"), ['shipping_method_id' => $rate->id])
            ->assertOk()
            ->assertJsonPath('status', 'shipping_selected')
            ->assertJsonPath('totals.shipping', 499);

        $this->postJson(storefrontApiUrl($context, "checkouts/{$checkoutId}/apply-discount"), ['code' => 'api10'])
            ->assertOk()
            ->assertJsonPath('discount_code', 'api10')
            ->assertJsonPath('totals.discount', 500);

        $this->putJson(storefrontApiUrl($context, "checkouts/{$checkoutId}/payment-method"), ['payment_method' => 'credit_card'])
            ->assertOk()
            ->assertJsonPath('status', 'payment_selected');

        $paid = $this->postJson(storefrontApiUrl($context, "checkouts/{$checkoutId}/pay"), storefrontCardPayment('4242 4242 4242 4242'))
            ->assertOk()
            ->assertJsonPath('status', 'completed')
            ->assertJsonPath('order.financial_status', 'paid')
            ->assertJsonPath('order.total_amount', 4999);
        $orderId = $paid->json('order.id');

        $this->postJson(storefrontApiUrl($context, "checkouts/{$checkoutId}/pay"), storefrontCardPayment('4242 4242 4242 4242'))
            ->assertOk()
            ->assertJsonPath('order.id', $orderId);

        expect(Order::withoutGlobalScopes()->where('store_id', $context['store']->id)->count())->toBe(1)
            ->and(Payment::query()->where('order_id', $orderId)->count())->toBe(1)
            ->and($sku['inventory']->refresh()->quantity_on_hand)->toBe(18)
            ->and($sku['inventory']->quantity_reserved)->toBe(0);
    });

    it('returns a stable decline code and releases inventory', function () {
        $context = createStoreContext();
        $sku = makeSellableVariant($context['store']);
        ['cart' => $cart] = makeCartWithLine($context['store'], $sku['variant']);
        $this->withSession(['cart_id' => $cart->id, 'api_cart_ids' => [$cart->id]]);
        $zone = ShippingZone::factory()->for($context['store'])->create(['countries_json' => ['DE']]);
        $rate = ShippingRate::factory()->for($zone, 'zone')->create();
        $checkoutId = $this->postJson(storefrontApiUrl($context, 'checkouts'), storefrontCheckoutPayload($cart->id))->json('id');
        $this->putJson(storefrontApiUrl($context, "checkouts/{$checkoutId}/address"), validCheckoutAddress())->assertOk();
        $this->putJson(storefrontApiUrl($context, "checkouts/{$checkoutId}/shipping-method"), ['shipping_method_id' => $rate->id])->assertOk();
        $this->putJson(storefrontApiUrl($context, "checkouts/{$checkoutId}/payment-method"), ['payment_method' => 'credit_card'])->assertOk();

        $this->postJson(storefrontApiUrl($context, "checkouts/{$checkoutId}/pay"), storefrontCardPayment('4000 0000 0000 0002'))
            ->assertUnprocessable()
            ->assertJsonPath('error_code', 'card_declined');

        expect($sku['inventory']->refresh()->quantity_reserved)->toBe(0)
            ->and(Order::withoutGlobalScopes()->where('store_id', $context['store']->id)->count())->toBe(0);
    });

    it('translates invalid checkout domain input into 422 responses', function () {
        $context = createStoreContext();
        $empty = app(CartService::class)->create($context['store']);
        $this->withSession(['cart_id' => $empty->id, 'api_cart_ids' => [$empty->id]]);

        $this->postJson(storefrontApiUrl($context, 'checkouts'), storefrontCheckoutPayload($empty->id))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cart_id');

        $sku = makeSellableVariant($context['store']);
        ['cart' => $cart] = makeCartWithLine($context['store'], $sku['variant']);
        $this->withSession(['cart_id' => $cart->id, 'api_cart_ids' => [$empty->id, $cart->id]]);
        $this->postJson(storefrontApiUrl($context, 'checkouts'), ['cart_id' => $cart->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
        $checkoutId = $this->postJson(storefrontApiUrl($context, 'checkouts'), storefrontCheckoutPayload($cart->id))->json('id');
        $this->putJson(storefrontApiUrl($context, "checkouts/{$checkoutId}/address"), [
            'email' => 'buyer@example.test',
            'shipping_address' => [
                'first_name' => 'Ada', 'last_name' => 'Lovelace', 'address1' => 'Street 1',
                'country_code' => 'DE', 'postal_code' => '10115',
            ],
        ])->assertUnprocessable()->assertJsonValidationErrors('shipping_address.city');

        $this->putJson(storefrontApiUrl($context, "checkouts/{$checkoutId}/address"), validCheckoutAddress())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('shipping_address');

        $zone = ShippingZone::factory()->for($context['store'])->create(['countries_json' => ['DE']]);
        ShippingRate::factory()->for($zone, 'zone')->create();
        $this->putJson(storefrontApiUrl($context, "checkouts/{$checkoutId}/address"), validCheckoutAddress())->assertOk();
        $wrongZone = ShippingZone::factory()->for($context['store'])->create(['countries_json' => ['US']]);
        $wrongRate = ShippingRate::factory()->for($wrongZone, 'zone')->create();
        $this->putJson(storefrontApiUrl($context, "checkouts/{$checkoutId}/shipping-method"), ['shipping_method_id' => $wrongRate->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('shipping_method_id');

        $this->postJson(storefrontApiUrl($context, "checkouts/{$checkoutId}/apply-discount"), ['code' => 'MISSING'])
            ->assertUnprocessable()
            ->assertJsonPath('error_code', 'not_found');
    });
});

describe('storefront search analytics and order API', function () {
    it('searches suggests validates and rate limits queries', function () {
        $context = createStoreContext();
        Product::factory()->for($context['store'])->create(['title' => 'Summer Cotton Dress', 'handle' => 'summer-cotton-dress']);

        $this->getJson(storefrontApiUrl($context, 'search?q=cott'))
            ->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('results.0.title', 'Summer Cotton Dress');
        $this->getJson(storefrontApiUrl($context, 'search/suggest?q=sum'))
            ->assertOk()
            ->assertJsonPath('suggestions.0.title', 'Summer Cotton Dress');
        $this->getJson(storefrontApiUrl($context, 'search/suggest?q=a'))->assertOk();
        $this->getJson(storefrontApiUrl($context, 'search/suggest?q='))
            ->assertUnprocessable()->assertJsonValidationErrors('q');

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10']);
        foreach (range(1, 30) as $attempt) {
            $this->getJson(storefrontApiUrl($context, 'search?q=none'))->assertOk();
        }
        $this->getJson(storefrontApiUrl($context, 'search?q=none'))->assertTooManyRequests();
    });

    it('accepts analytics batches deduplicates events and validates shape', function () {
        $context = createStoreContext();
        $customer = Customer::factory()->for($context['store'])->create();
        actingAsCustomer($customer);
        $event = [
            'type' => 'add_to_cart',
            'session_id' => 'session-api',
            'client_event_id' => 'event-api-1',
            'occurred_at' => now()->toIso8601String(),
            'properties' => ['variant_id' => 42],
        ];

        $this->postJson(storefrontApiUrl($context, 'analytics/events'), ['events' => [$event, $event]])
            ->assertStatus(202)
            ->assertJsonPath('accepted', 1)
            ->assertJsonPath('rejected', 1);

        $stored = AnalyticsEvent::withoutGlobalScopes()->where('store_id', $context['store']->id)->firstOrFail();
        expect(AnalyticsEvent::withoutGlobalScopes()->where('store_id', $context['store']->id)->count())->toBe(1)
            ->and($stored->customer_id)->toBe($customer->id)
            ->and($stored->properties_json)->toBe(['variant_id' => 42]);

        $this->postJson(storefrontApiUrl($context, 'analytics/events'), ['events' => [[
            'type' => 'not_supported',
            'session_id' => 'x',
            'client_event_id' => 'bad',
            'occurred_at' => 'not-a-date',
        ]]])->assertUnprocessable()->assertJsonValidationErrors(['events.0.type', 'events.0.occurred_at']);
    });

    it('requires a signed order token and scopes the order to its tenant', function () {
        $context = createStoreContext();
        $order = Order::factory()->for($context['store'])->create([
            'customer_id' => null,
            'order_number' => '#API1001',
            'email' => 'buyer@example.test',
        ]);
        OrderLine::factory()->for($order)->create(['product_id' => null, 'variant_id' => null, 'title_snapshot' => 'Historic Item']);
        $token = hash_hmac('sha256', $order->order_number.'|'.$order->email, (string) config('app.key'));
        $number = rawurlencode($order->order_number);

        $this->getJson(storefrontApiUrl($context, "orders/{$number}"))->assertUnauthorized();
        $this->getJson(storefrontApiUrl($context, "orders/{$number}?token=wrong"))->assertUnauthorized();
        $this->getJson(storefrontApiUrl($context, "orders/{$number}?token={$token}"))
            ->assertOk()
            ->assertJsonPath('order_number', '#API1001')
            ->assertJsonPath('lines.0.title_snapshot', 'Historic Item');

        $other = createStoreContext();
        $this->getJson(storefrontApiUrl($other, "orders/{$number}?token={$token}"))->assertNotFound();
    });
});
