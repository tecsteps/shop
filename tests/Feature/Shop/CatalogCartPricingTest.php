<?php

use App\Enums\ProductStatus;
use App\Events\ProductStatusChanged;
use App\Exceptions\CartVersionMismatchException;
use App\Exceptions\DomainException;
use App\Exceptions\InsufficientInventoryException;
use App\Exceptions\InvalidDiscountException;
use App\Exceptions\InvalidProductTransitionException;
use App\Models\Discount;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\TaxSettings;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\DiscountService;
use App\Services\InventoryService;
use App\Services\ProductService;
use App\Services\ShippingCalculator;
use App\Services\VariantMatrixService;
use App\Support\HandleGenerator;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

describe('handles and product lifecycle', function () {
    it('generates handles per store with collision suffixes and exclusions', function () {
        $storeA = createStoreContext()['store'];
        $storeB = createStoreContext()['store'];
        Product::factory()->for($storeA)->create(['title' => 'T-Shirt', 'handle' => 't-shirt']);
        Product::factory()->for($storeA)->create(['title' => 'T-Shirt Again', 'handle' => 't-shirt-1']);
        $editable = Product::factory()->for($storeA)->create(['title' => 'Editable', 'handle' => 'editable']);
        $handles = app(HandleGenerator::class);

        expect($handles->generate('T-Shirt', 'products', $storeA->id))->toBe('t-shirt-2')
            ->and($handles->generate('T-Shirt', 'products', $storeB->id))->toBe('t-shirt')
            ->and($handles->generate('Editable', 'products', $storeA->id, $editable->id))->toBe('editable')
            ->and($handles->generate("Loewe's Fall/Winter 2026", 'products', $storeA->id))->toBe('loewes-fallwinter-2026');
    });

    it('creates a draft product with one default variant and inventory', function () {
        $store = createStoreContext()['store'];
        $product = app(ProductService::class)->create($store, [
            'title' => 'Summer T-Shirt',
            'description_html' => '<p>Soft cotton.</p>',
            'variant' => ['price_amount' => 2500, 'quantity_on_hand' => 12, 'sku' => 'SUMMER-1'],
        ]);

        expect($product->status->value)->toBe('draft')
            ->and($product->handle)->toBe('summer-t-shirt')
            ->and($product->variants)->toHaveCount(1)
            ->and($product->variants->first()->is_default)->toBeTrue()
            ->and($product->variants->first()->inventoryItem->quantity_on_hand)->toBe(12);
    });

    it('publishes archives and emits the exact product transition event', function () {
        Event::fake([ProductStatusChanged::class]);
        $store = createStoreContext()['store'];
        $product = app(ProductService::class)->create($store, [
            'title' => 'Publishable',
            'variant' => ['price_amount' => 1200],
        ]);
        $service = app(ProductService::class);

        $service->transitionStatus($product, ProductStatus::Active);
        expect($product->refresh()->status->value)->toBe('active')
            ->and($product->published_at)->not->toBeNull();
        Event::assertDispatched(ProductStatusChanged::class, fn ($event): bool => $event->product->is($product) && $event->from === 'draft' && $event->to === 'active');

        $service->transitionStatus($product, ProductStatus::Archived);
        expect($product->refresh()->status->value)->toBe('archived');
    });

    it('blocks publication without a positive-price variant', function () {
        $store = createStoreContext()['store'];
        $product = app(ProductService::class)->create($store, [
            'title' => 'Free Draft',
            'variant' => ['price_amount' => 0],
        ]);

        app(ProductService::class)->transitionStatus($product, ProductStatus::Active);
    })->throws(InvalidProductTransitionException::class);

    it('blocks return to draft and deletion once order history exists', function () {
        $store = createStoreContext()['store'];
        $sku = makeSellableVariant($store);
        $order = Order::factory()->for($store)->create(['customer_id' => null]);
        OrderLine::factory()->for($order)->create([
            'product_id' => $sku['product']->id,
            'variant_id' => $sku['variant']->id,
            'quantity' => 1,
        ]);
        $service = app(ProductService::class);

        expect(fn () => $service->transitionStatus($sku['product'], ProductStatus::Draft))
            ->toThrow(InvalidProductTransitionException::class)
            ->and(fn () => $service->delete($sku['product']))
            ->toThrow(InvalidProductTransitionException::class);
    });

    it('hard deletes only an unreferenced draft product', function () {
        $store = createStoreContext()['store'];
        $product = app(ProductService::class)->create($store, ['title' => 'Disposable Draft']);

        app(ProductService::class)->delete($product);

        expect(Product::withoutGlobalScopes()->find($product->id))->toBeNull();
    });
});

describe('variant matrix SKU and inventory invariants', function () {
    it('creates the full cartesian matrix for two options', function () {
        $store = createStoreContext()['store'];
        $product = app(ProductService::class)->create($store, [
            'title' => 'Matrix Shirt',
            'options' => [
                ['name' => 'Size', 'values' => ['S', 'M', 'L']],
                ['name' => 'Color', 'values' => ['Red', 'Blue']],
            ],
        ]);

        expect($product->variants)->toHaveCount(6)
            ->and($product->variants->every(fn ($variant): bool => $variant->optionValues()->count() === 2))->toBeTrue()
            ->and($product->variants->every(fn ($variant): bool => $variant->inventoryItem()->exists()))->toBeTrue();
    });

    it('preserves existing variant identity and price when one value is added', function () {
        $store = createStoreContext()['store'];
        $service = app(ProductService::class);
        $product = $service->create($store, [
            'title' => 'Expandable Shirt',
            'options' => [['name' => 'Size', 'values' => ['S', 'M']]],
        ]);
        $originals = $product->variants->sortBy('id')->values();
        $originals[0]->update(['price_amount' => 1111]);
        $originals[1]->update(['price_amount' => 2222]);

        $updated = $service->update($product, [
            'options' => [['name' => 'Size', 'values' => ['S', 'M', 'L']]],
        ]);

        expect($updated->variants)->toHaveCount(3)
            ->and($updated->variants->pluck('id')->all())->toContain($originals[0]->id, $originals[1]->id)
            ->and($updated->variants->whereIn('id', $originals->modelKeys())->sortBy('id')->pluck('price_amount')->all())->toBe([1111, 2222]);
    });

    it('archives an orphaned ordered variant and deletes an unreferenced one', function () {
        $store = createStoreContext()['store'];
        $product = app(ProductService::class)->create($store, [
            'title' => 'Shrinking Shirt',
            'options' => [['name' => 'Size', 'values' => ['S', 'M', 'L']]],
        ]);
        [$ordered, $unreferenced] = $product->variants->take(2)->values()->all();
        $order = Order::factory()->for($store)->create(['customer_id' => null]);
        OrderLine::factory()->for($order)->create([
            'product_id' => $product->id,
            'variant_id' => $ordered->id,
        ]);
        $option = $product->options->first();
        $orderedValue = $ordered->optionValues->first();
        $unreferencedValue = $unreferenced->optionValues->first();
        $option->values()->whereKey([$orderedValue->id, $unreferencedValue->id])->delete();

        app(VariantMatrixService::class)->rebuildMatrix($product);

        expect(ProductVariant::query()->find($ordered->id)?->status->value)->toBe('archived')
            ->and(ProductVariant::query()->find($unreferenced->id))->toBeNull();
    });

    it('creates exactly one inventory item whenever a variant is created', function () {
        $store = createStoreContext()['store'];
        $product = Product::factory()->for($store)->create();
        $variant = ProductVariant::factory()->for($product)->create();

        expect($variant->inventoryItem()->count())->toBe(1)
            ->and($variant->inventoryItem->quantity_on_hand)->toBe(0)
            ->and($variant->inventoryItem->quantity_reserved)->toBe(0);
    });

    it('rejects a duplicate non-empty SKU inside one store', function () {
        $store = createStoreContext()['store'];
        $service = app(ProductService::class);
        $service->create($store, ['title' => 'First SKU', 'variant' => ['sku' => 'UNIQUE-1', 'price_amount' => 1000]]);

        $service->create($store, ['title' => 'Second SKU', 'variant' => ['sku' => 'UNIQUE-1', 'price_amount' => 1000]]);
    })->throws(ValidationException::class);

    it('allows duplicate SKUs in separate stores and multiple null SKUs', function () {
        $storeA = createStoreContext()['store'];
        $storeB = createStoreContext()['store'];
        $service = app(ProductService::class);

        $service->create($storeA, ['title' => 'Store A SKU', 'variant' => ['sku' => 'SHARED-1']]);
        $service->create($storeB, ['title' => 'Store B SKU', 'variant' => ['sku' => 'SHARED-1']]);
        $service->create($storeA, ['title' => 'Null SKU One', 'variant' => ['sku' => null]]);
        $service->create($storeA, ['title' => 'Null SKU Two', 'variant' => ['sku' => null]]);

        expect(ProductVariant::query()->whereNull('sku')->count())->toBe(2)
            ->and(ProductVariant::withoutGlobalScopes()->where('sku', 'SHARED-1')->count())->toBe(2);
    });

    it('reserves releases commits and restocks integer inventory', function () {
        $store = createStoreContext()['store'];
        $sku = makeSellableVariant($store, inventoryAttributes: ['quantity_on_hand' => 10, 'quantity_reserved' => 0]);
        $service = app(InventoryService::class);

        expect($service->available($sku['inventory']))->toBe(10)
            ->and($service->checkAvailability($sku['inventory'], 10))->toBeTrue();
        $service->reserve($sku['inventory'], 3);
        expect($sku['inventory']->quantity_reserved)->toBe(3)
            ->and($service->available($sku['inventory']))->toBe(7);
        $service->release($sku['inventory'], 1);
        expect($sku['inventory']->quantity_reserved)->toBe(2);
        $service->commit($sku['inventory'], 2);
        expect($sku['inventory']->quantity_on_hand)->toBe(8)
            ->and($sku['inventory']->quantity_reserved)->toBe(0);
        $service->restock($sku['inventory'], 4);
        expect($sku['inventory']->quantity_on_hand)->toBe(12);
    });

    it('blocks insufficient deny-policy inventory but permits backorders', function () {
        $store = createStoreContext()['store'];
        $deny = makeSellableVariant($store, inventoryAttributes: ['quantity_on_hand' => 5, 'quantity_reserved' => 3]);
        $continue = makeSellableVariant($store, inventoryAttributes: ['quantity_on_hand' => 2, 'policy' => 'continue']);
        $service = app(InventoryService::class);

        expect(fn () => $service->reserve($deny['inventory'], 3))->toThrow(InsufficientInventoryException::class);
        $service->reserve($continue['inventory'], 5);
        expect($continue['inventory']->quantity_reserved)->toBe(5);
    });
});

describe('cart mutations and optimistic concurrency', function () {
    it('resolves only an active session cart and creates one fresh cart after conversion', function () {
        $flow = checkoutReadyForPayment();
        session(['cart_id' => $flow['cart']->id]);

        $order = app(CheckoutService::class)->completeCheckout($flow['checkout'], ['card_number' => '4242424242424242']);
        $service = app(CartService::class);

        expect($service->resolveForSession($flow['store']))->toBeNull()
            ->and(session()->has('cart_id'))->toBeFalse();

        $fresh = $service->getOrCreateForSession($flow['store']);
        $sameOrder = app(CheckoutService::class)->completeCheckout($flow['checkout']->refresh(), ['card_number' => '4242424242424242']);
        $sameFresh = $service->getOrCreateForSession($flow['store']);

        expect($fresh->id)->not->toBe($flow['cart']->id)
            ->and($fresh->status->value)->toBe('active')
            ->and($fresh->lines()->count())->toBe(0)
            ->and(session('cart_id'))->toBe($fresh->id)
            ->and($sameOrder->id)->toBe($order->id)
            ->and($sameFresh->id)->toBe($fresh->id)
            ->and(Order::withoutGlobalScopes()->where('store_id', $flow['store']->id)->count())->toBe(1);
    });

    it('creates prices combines updates removes and versions cart lines', function () {
        $store = createStoreContext()['store'];
        $sku = makeSellableVariant($store, variantAttributes: ['price_amount' => 2500]);
        $service = app(CartService::class);
        $cart = $service->create($store);

        expect($cart->cart_version)->toBe(1)
            ->and($cart->status->value)->toBe('active')
            ->and($cart->currency)->toBe($store->default_currency);

        $line = $service->addLine($cart, $sku['variant'], 2, 1);
        expect($line->quantity)->toBe(2)
            ->and($line->unit_price_amount)->toBe(2500)
            ->and($line->line_subtotal_amount)->toBe(5000)
            ->and($line->line_total_amount)->toBe(5000)
            ->and($cart->cart_version)->toBe(2);

        $combined = $service->addLine($cart, $sku['variant'], 1, 2);
        expect($combined->id)->toBe($line->id)
            ->and($combined->quantity)->toBe(3)
            ->and($combined->line_subtotal_amount)->toBe(7500)
            ->and($cart->cart_version)->toBe(3);

        $updated = $service->updateLineQuantity($cart, $line->id, 4, 3);
        expect($updated->quantity)->toBe(4)
            ->and($updated->line_total_amount)->toBe(10000)
            ->and($cart->cart_version)->toBe(4);

        $service->removeLine($cart, $line->id, 4);
        expect($cart->cart_version)->toBe(5)
            ->and($cart->lines()->count())->toBe(0);
    });

    it('removes a line when its quantity is changed to zero exactly once', function () {
        $store = createStoreContext()['store'];
        $sku = makeSellableVariant($store);
        ['cart' => $cart, 'line' => $line] = makeCartWithLine($store, $sku['variant'], 2);

        app(CartService::class)->updateLineQuantity($cart, $line->id, 0, 2);

        expect($cart->lines()->count())->toBe(0)
            ->and($cart->cart_version)->toBe(3);
    });

    it('returns current state through a cart version conflict', function () {
        $store = createStoreContext()['store'];
        $sku = makeSellableVariant($store);
        ['cart' => $cart, 'line' => $line] = makeCartWithLine($store, $sku['variant']);

        try {
            app(CartService::class)->updateLineQuantity($cart, $line->id, 2, 1);
            $this->fail('A stale cart mutation was accepted.');
        } catch (CartVersionMismatchException $exception) {
            expect($exception->cart->cart_version)->toBe(2)
                ->and($exception->cart->lines)->toHaveCount(1);
        }
    });

    it('rejects foreign inactive and unavailable variants', function () {
        $store = createStoreContext()['store'];
        $other = createStoreContext()['store'];
        $cart = app(CartService::class)->create($store);
        $foreign = makeSellableVariant($other);
        $draft = makeSellableVariant($store, productAttributes: ['status' => 'draft', 'published_at' => null]);
        $empty = makeSellableVariant($store, inventoryAttributes: ['quantity_on_hand' => 1]);
        $service = app(CartService::class);

        expect(fn () => $service->addLine($cart, $foreign['variant']))->toThrow(DomainException::class)
            ->and(fn () => $service->addLine($cart, $draft['variant']))->toThrow(DomainException::class)
            ->and(fn () => $service->addLine($cart, $empty['variant'], 2))->toThrow(InsufficientInventoryException::class);
    });

    it('allows continue-policy overselling and merges guest quantities', function () {
        $store = createStoreContext()['store'];
        $first = makeSellableVariant($store, inventoryAttributes: ['quantity_on_hand' => 0, 'policy' => 'continue']);
        $second = makeSellableVariant($store);
        $service = app(CartService::class);
        $guest = $service->create($store);
        $customer = $service->create($store);
        $service->addLine($guest, $first['variant'], 2);
        $service->addLine($customer, $first['variant'], 1);
        $service->addLine($customer, $second['variant'], 3);

        $merged = $service->mergeOnLogin($guest, $customer);

        expect($merged->lines)->toHaveCount(2)
            ->and($merged->lines->firstWhere('variant_id', $first['variant']->id)->quantity)->toBe(2)
            ->and($merged->lines->firstWhere('variant_id', $second['variant']->id)->quantity)->toBe(3)
            ->and($guest->refresh()->status->value)->toBe('abandoned');
    });
});

describe('discount shipping tax and pricing integration', function () {
    it('validates codes case-insensitively and returns precise rejection reasons', function () {
        $store = createStoreContext()['store'];
        $sku = makeSellableVariant($store, variantAttributes: ['price_amount' => 3000]);
        ['cart' => $cart] = makeCartWithLine($store, $sku['variant']);
        Discount::factory()->for($store)->create(['code' => 'SUMMER20']);
        $service = app(DiscountService::class);

        expect($service->validate('summer20', $store, $cart)->code)->toBe('SUMMER20');

        foreach ([
            'MISSING' => 'not_found',
            'EXPIRED' => 'expired',
            'FUTURE' => 'not_yet_active',
            'MAXED' => 'usage_limit_reached',
            'MINIMUM' => 'minimum_not_met',
        ] as $code => $reason) {
            match ($code) {
                'EXPIRED' => Discount::factory()->for($store)->expired()->create(['code' => $code]),
                'FUTURE' => Discount::factory()->for($store)->create(['code' => $code, 'starts_at' => now()->addDay()]),
                'MAXED' => Discount::factory()->for($store)->maxedOut()->create(['code' => $code]),
                'MINIMUM' => Discount::factory()->for($store)->create(['code' => $code, 'rules_json' => ['min_purchase_amount' => 5000]]),
                default => null,
            };

            try {
                $service->validate($code, $store, $cart);
                $this->fail("{$code} should have been rejected.");
            } catch (InvalidDiscountException $exception) {
                expect($exception->reason)->toBe($reason);
            }
        }
    });

    it('selects the most specific shipping zone and filters unavailable rates', function () {
        $store = createStoreContext()['store'];
        $country = ShippingZone::factory()->for($store)->create(['countries_json' => ['DE'], 'regions_json' => []]);
        $region = ShippingZone::factory()->for($store)->create(['countries_json' => ['DE'], 'regions_json' => ['BE']]);
        ShippingRate::factory()->for($region, 'zone')->create(['name' => 'Berlin', 'config_json' => ['amount' => 499]]);
        ShippingRate::factory()->for($region, 'zone')->inactive()->create(['name' => 'Hidden']);
        ShippingRate::factory()->for($country, 'zone')->create(['name' => 'Germany', 'config_json' => ['amount' => 799]]);
        $sku = makeSellableVariant($store);
        ['cart' => $cart] = makeCartWithLine($store, $sku['variant']);
        $service = app(ShippingCalculator::class);

        expect($service->matchingZone($store, ['country_code' => 'DE', 'province_code' => 'BE'])->is($region))->toBeTrue()
            ->and($service->getAvailableRates($store, ['country_code' => 'DE', 'province_code' => 'BE'], $cart))->toHaveCount(1)
            ->and($service->matchingZone($store, ['country_code' => 'FR']))->toBeNull();
    });

    it('calculates and snapshots discount shipping and per-line exclusive tax', function () {
        $store = createStoreContext()['store'];
        $sku = makeSellableVariant($store, variantAttributes: ['price_amount' => 2500]);
        ['cart' => $cart] = makeCartWithLine($store, $sku['variant'], 2);
        $zone = ShippingZone::factory()->for($store)->create(['countries_json' => ['DE']]);
        $rate = ShippingRate::factory()->for($zone, 'zone')->create(['config_json' => ['amount' => 499]]);
        TaxSettings::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'mode' => 'manual',
            'provider' => 'none',
            'prices_include_tax' => false,
            'config_json' => ['rate_bps' => 1900, 'label' => 'VAT'],
        ]);
        Discount::factory()->for($store)->create(['code' => 'SAVE10', 'value_type' => 'percent', 'value_amount' => 10]);
        $checkouts = app(CheckoutService::class);
        $checkout = $checkouts->create($cart);
        $checkout = $checkouts->setAddress($checkout, validCheckoutAddress());
        $checkout = $checkouts->setShippingMethod($checkout, $rate->id);
        $checkout = $checkouts->applyDiscount($checkout, 'save10');
        $totals = $checkout->totals_json;

        expect($totals['subtotal'])->toBe(5000)
            ->and($totals['discount'])->toBe(500)
            ->and($totals['discounted_subtotal'])->toBe(4500)
            ->and($totals['shipping'])->toBe(499)
            ->and($totals['tax_total'])->toBe(950)
            ->and($totals['total'])->toBe(5949)
            ->and($totals['tax_lines'][0])->toMatchArray(['name' => 'VAT', 'rate' => 1900, 'amount' => 950])
            ->and($cart->lines()->first()->line_discount_amount)->toBe(500);
    });

    it('sets shipping to zero for digital carts and free-shipping discounts', function () {
        $store = createStoreContext()['store'];
        $digital = makeSellableVariant($store, variantAttributes: ['requires_shipping' => false, 'weight_g' => 0]);
        ['cart' => $cart] = makeCartWithLine($store, $digital['variant']);
        $checkout = app(CheckoutService::class)->create($cart);
        $checkout = app(CheckoutService::class)->setAddress($checkout, validCheckoutAddress());
        $checkout = app(CheckoutService::class)->setShippingMethod($checkout, null);

        expect($checkout->status->value)->toBe('shipping_selected')
            ->and($checkout->shipping_method_id)->toBeNull()
            ->and($checkout->totals_json['shipping'])->toBe(0);

        $physical = makeSellableVariant($store);
        ['cart' => $physicalCart] = makeCartWithLine($store, $physical['variant']);
        $zone = ShippingZone::factory()->for($store)->create(['countries_json' => ['DE']]);
        $rate = ShippingRate::factory()->for($zone, 'zone')->create(['config_json' => ['amount' => 999]]);
        Discount::factory()->for($store)->freeShipping()->create(['code' => 'SHIPFREE']);
        $physicalCheckout = app(CheckoutService::class)->create($physicalCart);
        $physicalCheckout = app(CheckoutService::class)->setAddress($physicalCheckout, validCheckoutAddress());
        $physicalCheckout = app(CheckoutService::class)->setShippingMethod($physicalCheckout, $rate->id);
        $physicalCheckout = app(CheckoutService::class)->applyDiscount($physicalCheckout, 'SHIPFREE');

        expect($physicalCheckout->totals_json['shipping'])->toBe(0)
            ->and($physicalCheckout->shipping_method_id)->toBe($rate->id);
    });
});
