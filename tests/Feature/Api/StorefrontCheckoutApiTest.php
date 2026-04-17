<?php

use App\Models\Discount;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\CartService;
use App\Services\CheckoutService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->domain = $this->context['domain'];
    $this->apiBase = "http://{$this->domain->hostname}/api/storefront/v1";
});

it('creates a checkout from a cart', function () {
    $cartService = new CartService;
    $cart = $cartService->create($this->store);

    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2000]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 10]);

    $cartService->addLine($cart, $variant->id, 2);

    $response = $this->postJson("{$this->apiBase}/checkouts", [
        'cart_id' => $cart->id,
        'email' => 'test@example.com',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.status', 'started');
});

it('sets checkout address', function () {
    $cartService = new CartService;
    $cart = $cartService->create($this->store);

    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2000]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 10]);

    $cartService->addLine($cart, $variant->id, 1);

    $checkoutService = app(CheckoutService::class);
    $checkout = $checkoutService->createFromCart($cart);
    $checkout->update(['email' => 'test@example.com']);

    $response = $this->putJson("{$this->apiBase}/checkouts/{$checkout->id}/address", [
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'Berlin',
            'postal_code' => '10115',
            'country_code' => 'DE',
        ],
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'addressed');
});

it('selects a shipping method', function () {
    $cartService = new CartService;
    $cart = $cartService->create($this->store);

    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2000]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 10]);

    $cartService->addLine($cart, $variant->id, 1);

    $checkoutService = app(CheckoutService::class);
    $checkout = $checkoutService->createFromCart($cart);
    $checkout->update(['email' => 'test@example.com']);

    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['DE'],
    ]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id]);

    $checkout = $checkoutService->setAddress($checkout, [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'address1' => '123 Main St',
        'city' => 'Berlin',
        'country_code' => 'DE',
        'zip' => '10115',
    ]);

    $response = $this->putJson("{$this->apiBase}/checkouts/{$checkout->id}/shipping-method", [
        'shipping_method_id' => $rate->id,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'shipping_selected');
});

it('applies a discount code', function () {
    $cartService = new CartService;
    $cart = $cartService->create($this->store);

    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 5000]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 10]);

    $cartService->addLine($cart, $variant->id, 1);

    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'SAVE10',
        'value_amount' => 10,
    ]);

    $checkoutService = app(CheckoutService::class);
    $checkout = $checkoutService->createFromCart($cart);
    $checkout->update(['email' => 'test@example.com']);

    $response = $this->postJson("{$this->apiBase}/checkouts/{$checkout->id}/apply-discount", [
        'code' => 'SAVE10',
    ]);

    $response->assertStatus(200);
});

it('retrieves checkout with totals', function () {
    $cartService = new CartService;
    $cart = $cartService->create($this->store);

    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 3000]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 10]);

    $cartService->addLine($cart, $variant->id, 2);

    $checkoutService = app(CheckoutService::class);
    $checkout = $checkoutService->createFromCart($cart);
    $checkout->update(['email' => 'test@example.com']);

    $response = $this->getJson("{$this->apiBase}/checkouts/{$checkout->id}");

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['id', 'status', 'email', 'totals']]);
});

it('selects a payment method', function () {
    $cartService = new CartService;
    $cart = $cartService->create($this->store);

    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2000]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 10]);

    $cartService->addLine($cart, $variant->id, 1);

    $checkoutService = app(CheckoutService::class);
    $checkout = $checkoutService->createFromCart($cart);
    $checkout->update(['email' => 'test@example.com']);

    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['DE'],
    ]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id]);

    $checkout = $checkoutService->setAddress($checkout, [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'address1' => '123 Main St',
        'city' => 'Berlin',
        'country_code' => 'DE',
        'zip' => '10115',
    ]);
    $checkout = $checkoutService->setShippingMethod($checkout, $rate->id);

    $response = $this->putJson("{$this->apiBase}/checkouts/{$checkout->id}/payment-method", [
        'payment_method' => 'credit_card',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'payment_selected');
});

it('completes checkout with credit card payment', function () {
    $cartService = new CartService;
    $cart = $cartService->create($this->store);

    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2000]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 10]);

    $cartService->addLine($cart, $variant->id, 1);

    $checkoutService = app(CheckoutService::class);
    $checkout = $checkoutService->createFromCart($cart);
    $checkout->update(['email' => 'test@example.com']);

    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['DE'],
    ]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id]);

    $checkout = $checkoutService->setAddress($checkout, [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'address1' => '123 Main St',
        'city' => 'Berlin',
        'country_code' => 'DE',
        'zip' => '10115',
    ]);
    $checkout = $checkoutService->setShippingMethod($checkout, $rate->id);
    $checkout = $checkoutService->selectPaymentMethod($checkout, 'credit_card');

    $response = $this->postJson("{$this->apiBase}/checkouts/{$checkout->id}/pay", [
        'payment_method' => 'credit_card',
        'card_number' => '4111111111111111',
        'card_expiry' => '12/28',
        'card_cvc' => '123',
        'card_holder' => 'John Doe',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('status', 'completed')
        ->assertJsonStructure(['order' => ['id', 'order_number', 'status']]);
});

it('rejects payment with declined card', function () {
    $cartService = new CartService;
    $cart = $cartService->create($this->store);

    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2000]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 10]);

    $cartService->addLine($cart, $variant->id, 1);

    $checkoutService = app(CheckoutService::class);
    $checkout = $checkoutService->createFromCart($cart);
    $checkout->update(['email' => 'test@example.com']);

    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['DE'],
    ]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id]);

    $checkout = $checkoutService->setAddress($checkout, [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'address1' => '123 Main St',
        'city' => 'Berlin',
        'country_code' => 'DE',
        'zip' => '10115',
    ]);
    $checkout = $checkoutService->setShippingMethod($checkout, $rate->id);
    $checkout = $checkoutService->selectPaymentMethod($checkout, 'credit_card');

    $response = $this->postJson("{$this->apiBase}/checkouts/{$checkout->id}/pay", [
        'payment_method' => 'credit_card',
        'card_number' => '4000000000000002',
        'card_expiry' => '12/28',
        'card_cvc' => '123',
        'card_holder' => 'John Doe',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('error_code', 'card_declined');
});

it('validates required address fields', function () {
    $cartService = new CartService;
    $cart = $cartService->create($this->store);

    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2000]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 10]);

    $cartService->addLine($cart, $variant->id, 1);

    $checkoutService = app(CheckoutService::class);
    $checkout = $checkoutService->createFromCart($cart);
    $checkout->update(['email' => 'test@example.com']);

    $response = $this->putJson("{$this->apiBase}/checkouts/{$checkout->id}/address", [
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            // Missing city
            'country_code' => 'DE',
        ],
    ]);

    $response->assertStatus(422);
});
