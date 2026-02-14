<?php

declare(strict_types=1);

use App\Enums\CollectionStatus;
use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Enums\PageStatus;
use App\Enums\ProductStatus;
use App\Enums\ProductVariantStatus;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Collection;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Discount;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
});

/**
 * @return array{store: Store, product: Product, variant: ProductVariant, collection: Collection, page: Page, customer: Customer, cart: Cart, checkout: Checkout, order: Order}
 */
function createStorefrontFixture(): array
{
    $store = Store::factory()->create([
        'name' => 'Acme Fashion',
        'handle' => 'acme-fashion',
        'default_currency' => 'EUR',
    ]);

    StoreDomain::factory()->create([
        'store_id' => $store->id,
        'hostname' => 'shop.test',
    ]);

    $collection = Collection::factory()->create([
        'store_id' => $store->id,
        'title' => 'T-Shirts',
        'handle' => 't-shirts',
        'status' => CollectionStatus::Active,
    ]);

    $product = Product::factory()->create([
        'store_id' => $store->id,
        'title' => 'Classic Cotton T-Shirt',
        'handle' => 'classic-cotton-t-shirt',
        'status' => ProductStatus::Active,
        'published_at' => now(),
    ]);

    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'is_default' => true,
        'status' => ProductVariantStatus::Active,
        'price_amount' => 2499,
        'currency' => 'EUR',
        'sku' => 'TSHIRT-001',
    ]);

    $collection->products()->attach($product->id, ['position' => 1]);

    $page = Page::query()->create([
        'store_id' => $store->id,
        'title' => 'About',
        'handle' => 'about',
        'body_html' => '<p>About Acme Fashion</p>',
        'status' => PageStatus::Published,
        'published_at' => now(),
    ]);

    $customer = Customer::factory()->create([
        'store_id' => $store->id,
        'name' => 'John Doe',
        'email' => 'customer@acme.test',
        'password_hash' => Hash::make('password'),
    ]);

    CustomerAddress::query()->create([
        'customer_id' => $customer->id,
        'label' => 'Home',
        'address_json' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => 'Main Street 1',
            'city' => 'Berlin',
            'country_code' => 'DE',
            'postal_code' => '10115',
        ],
        'is_default' => true,
    ]);

    $cart = Cart::factory()->create([
        'store_id' => $store->id,
        'customer_id' => $customer->id,
        'currency' => 'EUR',
    ]);

    CartLine::query()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 2499,
        'line_subtotal_amount' => 2499,
        'line_discount_amount' => 0,
        'line_total_amount' => 2499,
    ]);

    $checkout = Checkout::query()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'customer_id' => $customer->id,
        'status' => 'completed',
        'payment_method' => 'credit_card',
        'email' => $customer->email,
        'totals_json' => [
            'subtotal' => 2499,
            'discount' => 0,
            'shipping' => 499,
            'tax' => 380,
            'total' => 3378,
            'currency' => 'EUR',
        ],
        'expires_at' => now()->addHour(),
    ]);

    $order = Order::factory()->create([
        'store_id' => $store->id,
        'customer_id' => $customer->id,
        'order_number' => '#1001',
        'email' => $customer->email,
        'currency' => 'EUR',
        'total_amount' => 3378,
    ]);

    OrderLine::query()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'title_snapshot' => $product->title,
        'sku_snapshot' => $variant->sku,
        'quantity' => 1,
        'unit_price_amount' => 2499,
        'total_amount' => 2499,
        'tax_lines_json' => [],
        'discount_allocations_json' => [],
    ]);

    return [
        'store' => $store,
        'product' => $product,
        'variant' => $variant,
        'collection' => $collection,
        'page' => $page,
        'customer' => $customer,
        'cart' => $cart,
        'checkout' => $checkout,
        'order' => $order,
    ];
}

function createStorefrontShippingRate(Store $store): ShippingRate
{
    $zone = ShippingZone::query()->create([
        'store_id' => $store->id,
        'name' => 'Germany',
        'countries_json' => ['DE'],
        'regions_json' => [],
    ]);

    return ShippingRate::query()->create([
        'zone_id' => $zone->id,
        'name' => 'Standard Shipping',
        'type' => 'flat',
        'config_json' => ['amount' => 500, 'currency' => 'EUR'],
        'is_active' => true,
    ]);
}

function createStorefrontDiscount(Store $store, string $code = 'WELCOME10'): Discount
{
    return Discount::query()->create([
        'store_id' => $store->id,
        'type' => DiscountType::Code,
        'code' => $code,
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
        'usage_limit' => null,
        'usage_count' => 0,
        'rules_json' => [],
        'status' => DiscountStatus::Active,
    ]);
}

function storefrontEnumValue(mixed $value): string
{
    if ($value instanceof BackedEnum) {
        return (string) $value->value;
    }

    return (string) $value;
}

function storefrontAssertOrderDiscountAllocations(Order $order, Discount $discount): void
{
    $order->loadMissing('lines');
    expect($order->lines->count())->toBeGreaterThan(0);

    $allocatedDiscountAmount = 0;
    $linesWithAllocations = 0;

    foreach ($order->lines as $line) {
        $allocations = $line->discount_allocations_json;

        expect($allocations)->toBeArray();

        if ($allocations === []) {
            continue;
        }

        $linesWithAllocations++;

        foreach ($allocations as $allocation) {
            expect(is_array($allocation))->toBeTrue();
            expect(array_key_exists('discount_id', $allocation))->toBeTrue();
            expect(array_key_exists('code', $allocation))->toBeTrue();
            expect(array_key_exists('amount', $allocation))->toBeTrue();
            expect($allocation['discount_id'])->toBe((int) $discount->id);
            expect($allocation['code'])->toBe((string) $discount->code);
            expect(is_int($allocation['amount']))->toBeTrue();
            expect($allocation['amount'])->toBeGreaterThan(0);

            $allocatedDiscountAmount += $allocation['amount'];
        }
    }

    expect($linesWithAllocations)->toBeGreaterThan(0);
    expect($allocatedDiscountAmount)->toBe((int) $order->discount_amount);
}

test('storefront core pages render with tenant host', function (): void {
    $fixture = createStorefrontFixture();

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->get('/')
        ->assertOk()
        ->assertSee('Acme Fashion');

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->get('/collections')
        ->assertOk()
        ->assertSee('Collections');

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->get('/collections/'.$fixture['collection']->handle)
        ->assertOk()
        ->assertSee('T-Shirts');

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->get('/products/'.$fixture['product']->handle)
        ->assertOk()
        ->assertSee('Classic Cotton T-Shirt');

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->get('/pages/'.$fixture['page']->handle)
        ->assertOk()
        ->assertSee('About');

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->get('/search?q=shirt')
        ->assertOk()
        ->assertSee('Search Products');

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->get('/checkout/'.$fixture['checkout']->id)
        ->assertOk()
        ->assertSee('Checkout #'.$fixture['checkout']->id);

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->get('/checkout/'.$fixture['checkout']->id.'/confirmation')
        ->assertOk()
        ->assertSee('Checkout Confirmation');
});

test('customer can register and access account pages', function (): void {
    createStorefrontFixture();

    $registerResponse = $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->post('/account/register', [
            'name' => 'New Customer',
            'email' => 'new-customer@acme.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'marketing_opt_in' => '1',
        ]);

    $registerResponse->assertRedirect('/account');

    $customer = Customer::query()->where('email', 'new-customer@acme.test')->first();
    expect($customer)->not->toBeNull();

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->actingAs($customer, 'customer')
        ->get('/account')
        ->assertOk()
        ->assertSee('Welcome, New Customer');

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->actingAs($customer, 'customer')
        ->get('/account/orders')
        ->assertOk()
        ->assertSee('Your Orders');

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->actingAs($customer, 'customer')
        ->get('/account/addresses')
        ->assertOk()
        ->assertSee('Address Book');
});

test('registration redirect resolves to account dashboard without redirect loops', function (): void {
    createStorefrontFixture();

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->followingRedirects()
        ->post('/account/register', [
            'name' => 'Loop Check Customer',
            'email' => 'loop-check-customer@acme.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'marketing_opt_in' => '0',
        ])
        ->assertOk()
        ->assertSee('Welcome, Loop Check Customer');
});

test('customer login and logout flow works', function (): void {
    $fixture = createStorefrontFixture();

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->post('/account/login', [
            'email' => $fixture['customer']->email,
            'password' => 'password',
        ])
        ->assertRedirect('/account');

    $this->assertAuthenticated('customer');

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->post('/account/logout')
        ->assertRedirect('/account/login');

    $this->assertGuest('customer');
});

test('product add to cart form creates a guest cart line', function (): void {
    $fixture = createStorefrontFixture();
    $productPath = '/products/'.$fixture['product']->handle;

    $response = $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->from($productPath)
        ->post('/cart/lines', [
            'variant_id' => $fixture['variant']->id,
            'quantity' => 2,
        ]);

    $response->assertRedirect($productPath)
        ->assertSessionHas('status')
        ->assertSessionHas('cart_id');

    $cartId = (int) $response->getSession()->get('cart_id');

    $this->assertDatabaseHas('cart_lines', [
        'cart_id' => $cartId,
        'variant_id' => $fixture['variant']->id,
        'quantity' => 2,
    ]);
});

test('product add to cart form returns validation errors for unknown variants', function (): void {
    $fixture = createStorefrontFixture();
    $productPath = '/products/'.$fixture['product']->handle;

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->from($productPath)
        ->post('/cart/lines', [
            'variant_id' => 999999,
            'quantity' => 1,
        ])
        ->assertRedirect($productPath)
        ->assertSessionHasErrors('variant_id');
});

test('cart line quantity update and remove forms mutate lines', function (): void {
    $fixture = createStorefrontFixture();
    $line = CartLine::query()->where('cart_id', $fixture['cart']->id)->firstOrFail();

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->withSession(['cart_id' => $fixture['cart']->id])
        ->patch('/cart/lines/'.$line->id, [
            'quantity' => 3,
            'cart_version' => (int) $fixture['cart']->cart_version,
        ])
        ->assertRedirect('/cart')
        ->assertSessionHas('status');

    $this->assertDatabaseHas('cart_lines', [
        'id' => $line->id,
        'quantity' => 3,
    ]);

    $fixture['cart']->refresh();

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->withSession(['cart_id' => $fixture['cart']->id])
        ->delete('/cart/lines/'.$line->id, [
            'cart_version' => (int) $fixture['cart']->cart_version,
        ])
        ->assertRedirect('/cart')
        ->assertSessionHas('status');

    $this->assertDatabaseMissing('cart_lines', [
        'id' => $line->id,
    ]);
});

test('cart line remove form shows errors for unknown lines', function (): void {
    $fixture = createStorefrontFixture();

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->withSession(['cart_id' => $fixture['cart']->id])
        ->delete('/cart/lines/999999', [
            'cart_version' => (int) $fixture['cart']->cart_version,
        ])
        ->assertRedirect('/cart')
        ->assertSessionHasErrors('line');
});

test('cart can start checkout from cart page', function (): void {
    $fixture = createStorefrontFixture();

    $response = $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->withSession(['cart_id' => $fixture['cart']->id])
        ->post('/cart/checkout');

    $newCheckout = Checkout::query()
        ->where('store_id', $fixture['store']->id)
        ->where('cart_id', $fixture['cart']->id)
        ->where('status', 'started')
        ->latest('id')
        ->first();

    expect($newCheckout)->not->toBeNull();

    $response->assertRedirect('/checkout/'.$newCheckout->id)
        ->assertSessionHas('status');
});

test('cart checkout start fails for empty carts', function (): void {
    $fixture = createStorefrontFixture();
    CartLine::query()->where('cart_id', $fixture['cart']->id)->delete();

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->withSession(['cart_id' => $fixture['cart']->id])
        ->post('/cart/checkout')
        ->assertRedirect('/cart')
        ->assertSessionHasErrors('cart');
});

test('checkout step forms can set address shipping payment discount and complete payment', function (): void {
    $fixture = createStorefrontFixture();
    $shippingRate = createStorefrontShippingRate($fixture['store']);
    createStorefrontDiscount($fixture['store'], 'WELCOME10');

    $checkout = Checkout::query()->create([
        'store_id' => $fixture['store']->id,
        'cart_id' => $fixture['cart']->id,
        'customer_id' => $fixture['customer']->id,
        'status' => 'started',
        'payment_method' => null,
        'email' => null,
        'shipping_address_json' => null,
        'billing_address_json' => null,
        'shipping_method_id' => null,
        'discount_code' => null,
        'tax_provider_snapshot_json' => null,
        'totals_json' => null,
        'expires_at' => now()->addDay(),
    ]);

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->put('/checkout/'.$checkout->id.'/address', [
            'email' => 'buyer@example.test',
            'shipping_address' => [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'address1' => 'Main Street 1',
                'city' => 'Berlin',
                'country' => 'Germany',
                'country_code' => 'DE',
                'postal_code' => '10115',
            ],
            'use_shipping_as_billing' => '1',
        ])
        ->assertRedirect('/checkout/'.$checkout->id)
        ->assertSessionHas('status');

    $checkout->refresh();
    expect(storefrontEnumValue($checkout->status))->toBe('addressed');

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->put('/checkout/'.$checkout->id.'/shipping-method', [
            'shipping_method_id' => $shippingRate->id,
        ])
        ->assertRedirect('/checkout/'.$checkout->id)
        ->assertSessionHas('status');

    $checkout->refresh();
    expect(storefrontEnumValue($checkout->status))->toBe('shipping_selected');

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->put('/checkout/'.$checkout->id.'/payment-method', [
            'payment_method' => 'credit_card',
        ])
        ->assertRedirect('/checkout/'.$checkout->id)
        ->assertSessionHas('status');

    $checkout->refresh();
    expect(storefrontEnumValue($checkout->status))->toBe('payment_selected');

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->post('/checkout/'.$checkout->id.'/discount', [
            'code' => 'WELCOME10',
        ])
        ->assertRedirect('/checkout/'.$checkout->id)
        ->assertSessionHas('status');

    $checkout->refresh();
    expect($checkout->discount_code)->toBe('WELCOME10');

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->delete('/checkout/'.$checkout->id.'/discount')
        ->assertRedirect('/checkout/'.$checkout->id)
        ->assertSessionHas('status');

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->post('/checkout/'.$checkout->id.'/pay', [
            'payment_method' => 'credit_card',
            'card_number' => '4242424242424242',
            'card_expiry' => '12/28',
            'card_cvc' => '123',
            'card_holder' => 'Jane Doe',
        ])
        ->assertRedirect('/checkout/'.$checkout->id.'/confirmation')
        ->assertSessionHas('status');

    $checkout->refresh();
    $fixture['cart']->refresh();
    $createdOrder = Order::query()->where('checkout_id', $checkout->id)->first();

    expect(storefrontEnumValue($checkout->status))->toBe('completed')
        ->and(storefrontEnumValue($fixture['cart']->status))->toBe('converted');

    $this->assertDatabaseHas('orders', [
        'store_id' => $fixture['store']->id,
        'email' => 'buyer@example.test',
        'checkout_id' => $checkout->id,
    ]);

    expect($createdOrder)->toBeInstanceOf(Order::class);

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->get('/checkout/'.$checkout->id.'/confirmation')
        ->assertOk()
        ->assertSee((string) $createdOrder?->order_number);
});

test('checkout completion with a valid discount increments usage count and persists line allocations', function (): void {
    $fixture = createStorefrontFixture();
    $shippingRate = createStorefrontShippingRate($fixture['store']);
    $discount = createStorefrontDiscount($fixture['store'], 'WELCOME10');
    $discount->rules_json = [
        'applicable_product_ids' => [$fixture['product']->id],
    ];
    $discount->save();

    $secondProduct = Product::factory()->create([
        'store_id' => $fixture['store']->id,
        'title' => 'Structured Allocation Product',
        'handle' => 'structured-allocation-product',
        'status' => ProductStatus::Active,
        'published_at' => now(),
    ]);

    $secondVariant = ProductVariant::factory()->create([
        'product_id' => $secondProduct->id,
        'is_default' => true,
        'status' => ProductVariantStatus::Active,
        'price_amount' => 1501,
        'currency' => 'EUR',
        'sku' => 'STRUCT-ALLOC-001',
    ]);

    CartLine::query()->create([
        'cart_id' => $fixture['cart']->id,
        'variant_id' => $secondVariant->id,
        'quantity' => 1,
        'unit_price_amount' => 1501,
        'line_subtotal_amount' => 1501,
        'line_discount_amount' => 0,
        'line_total_amount' => 1501,
    ]);

    $checkout = Checkout::query()->create([
        'store_id' => $fixture['store']->id,
        'cart_id' => $fixture['cart']->id,
        'customer_id' => $fixture['customer']->id,
        'status' => 'started',
        'payment_method' => null,
        'email' => null,
        'shipping_address_json' => null,
        'billing_address_json' => null,
        'shipping_method_id' => null,
        'discount_code' => null,
        'tax_provider_snapshot_json' => null,
        'totals_json' => null,
        'expires_at' => now()->addDay(),
    ]);

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->put('/checkout/'.$checkout->id.'/address', [
            'email' => 'buyer@example.test',
            'shipping_address' => [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'address1' => 'Main Street 1',
                'city' => 'Berlin',
                'country' => 'Germany',
                'country_code' => 'DE',
                'postal_code' => '10115',
            ],
            'use_shipping_as_billing' => '1',
        ])
        ->assertRedirect('/checkout/'.$checkout->id)
        ->assertSessionHas('status');

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->put('/checkout/'.$checkout->id.'/shipping-method', [
            'shipping_method_id' => $shippingRate->id,
        ])
        ->assertRedirect('/checkout/'.$checkout->id)
        ->assertSessionHas('status');

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->put('/checkout/'.$checkout->id.'/payment-method', [
            'payment_method' => 'credit_card',
        ])
        ->assertRedirect('/checkout/'.$checkout->id)
        ->assertSessionHas('status');

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->post('/checkout/'.$checkout->id.'/discount', [
            'code' => 'WELCOME10',
        ])
        ->assertRedirect('/checkout/'.$checkout->id)
        ->assertSessionHas('status');

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->post('/checkout/'.$checkout->id.'/pay', [
            'payment_method' => 'credit_card',
            'card_number' => '4242424242424242',
            'card_expiry' => '12/28',
            'card_cvc' => '123',
            'card_holder' => 'Jane Doe',
        ])
        ->assertRedirect('/checkout/'.$checkout->id.'/confirmation')
        ->assertSessionHas('status');

    $checkout->refresh();
    $discount->refresh();

    expect(storefrontEnumValue($checkout->status))->toBe('completed');
    expect((int) $discount->usage_count)->toBe(1);

    $order = Order::query()
        ->where('checkout_id', $checkout->id)
        ->firstOrFail();

    expect((int) $order->discount_amount)->toBeGreaterThan(0);
    expect((int) $order->lines()->count())->toBe(2);

    storefrontAssertOrderDiscountAllocations($order, $discount);

    $order->loadMissing('lines');
    $primaryLine = $order->lines->firstWhere('variant_id', $fixture['variant']->id);
    $secondaryLine = $order->lines->firstWhere('variant_id', $secondVariant->id);

    expect($primaryLine)->not->toBeNull();
    expect($secondaryLine)->not->toBeNull();
    expect(is_array($primaryLine?->discount_allocations_json))->toBeTrue();
    expect($primaryLine?->discount_allocations_json)->not->toBeEmpty();
    expect($secondaryLine?->discount_allocations_json)->toBe([]);
});

test('checkout pay succeeds when applied discount becomes invalid before payment', function (): void {
    $fixture = createStorefrontFixture();
    $shippingRate = createStorefrontShippingRate($fixture['store']);
    $discount = createStorefrontDiscount($fixture['store'], 'WELCOME10');

    $checkout = Checkout::query()->create([
        'store_id' => $fixture['store']->id,
        'cart_id' => $fixture['cart']->id,
        'customer_id' => $fixture['customer']->id,
        'status' => 'started',
        'payment_method' => null,
        'email' => null,
        'shipping_address_json' => null,
        'billing_address_json' => null,
        'shipping_method_id' => null,
        'discount_code' => null,
        'tax_provider_snapshot_json' => null,
        'totals_json' => null,
        'expires_at' => now()->addDay(),
    ]);

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->put('/checkout/'.$checkout->id.'/address', [
            'email' => 'buyer@example.test',
            'shipping_address' => [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'address1' => 'Main Street 1',
                'city' => 'Berlin',
                'country' => 'Germany',
                'country_code' => 'DE',
                'postal_code' => '10115',
            ],
            'use_shipping_as_billing' => '1',
        ])
        ->assertRedirect('/checkout/'.$checkout->id)
        ->assertSessionHas('status');

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->put('/checkout/'.$checkout->id.'/shipping-method', [
            'shipping_method_id' => $shippingRate->id,
        ])
        ->assertRedirect('/checkout/'.$checkout->id)
        ->assertSessionHas('status');

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->put('/checkout/'.$checkout->id.'/payment-method', [
            'payment_method' => 'credit_card',
        ])
        ->assertRedirect('/checkout/'.$checkout->id)
        ->assertSessionHas('status');

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->post('/checkout/'.$checkout->id.'/discount', [
            'code' => 'WELCOME10',
        ])
        ->assertRedirect('/checkout/'.$checkout->id)
        ->assertSessionHas('status');

    $discount->rules_json = [
        'applicable_product_ids' => [999999],
    ];
    $discount->save();

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->post('/checkout/'.$checkout->id.'/pay', [
            'payment_method' => 'credit_card',
            'card_number' => '4242424242424242',
            'card_expiry' => '12/28',
            'card_cvc' => '123',
            'card_holder' => 'Jane Doe',
        ])
        ->assertRedirect('/checkout/'.$checkout->id.'/confirmation')
        ->assertSessionHas('status');

    $checkout->refresh();
    $discount->refresh();

    expect(storefrontEnumValue($checkout->status))->toBe('completed');
    expect($checkout->discount_code)->toBeNull();
    expect((int) $discount->usage_count)->toBe(0);

    $order = Order::query()
        ->where('checkout_id', $checkout->id)
        ->firstOrFail();

    expect((int) $order->discount_amount)->toBe(0);

    $order->loadMissing('lines');

    foreach ($order->lines as $line) {
        expect($line->discount_allocations_json)->toBe([]);
    }
});

test('checkout forms return errors for invalid discount and invalid payment state', function (): void {
    $fixture = createStorefrontFixture();

    $checkout = Checkout::query()->create([
        'store_id' => $fixture['store']->id,
        'cart_id' => $fixture['cart']->id,
        'customer_id' => $fixture['customer']->id,
        'status' => 'started',
        'payment_method' => null,
        'email' => 'buyer@example.test',
        'shipping_address_json' => null,
        'billing_address_json' => null,
        'shipping_method_id' => null,
        'discount_code' => null,
        'tax_provider_snapshot_json' => null,
        'totals_json' => null,
        'expires_at' => now()->addDay(),
    ]);

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->post('/checkout/'.$checkout->id.'/discount', [
            'code' => 'INVALID',
        ])
        ->assertRedirect('/checkout/'.$checkout->id)
        ->assertSessionHasErrors('code');

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->post('/checkout/'.$checkout->id.'/pay', [
            'payment_method' => 'credit_card',
        ])
        ->assertRedirect('/checkout/'.$checkout->id)
        ->assertSessionHasErrors('checkout');
});

test('visiting an expired payment selected checkout releases reserved inventory', function (): void {
    $fixture = createStorefrontFixture();

    $inventoryItem = InventoryItem::query()->create([
        'store_id' => $fixture['store']->id,
        'variant_id' => $fixture['variant']->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 1,
        'policy' => 'deny',
    ]);

    $checkout = Checkout::query()->create([
        'store_id' => $fixture['store']->id,
        'cart_id' => $fixture['cart']->id,
        'customer_id' => $fixture['customer']->id,
        'status' => 'payment_selected',
        'payment_method' => 'credit_card',
        'email' => 'buyer@example.test',
        'shipping_address_json' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => 'Main Street 1',
            'city' => 'Berlin',
            'country' => 'Germany',
            'country_code' => 'DE',
            'postal_code' => '10115',
        ],
        'billing_address_json' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => 'Main Street 1',
            'city' => 'Berlin',
            'country' => 'Germany',
            'country_code' => 'DE',
            'postal_code' => '10115',
        ],
        'shipping_method_id' => null,
        'discount_code' => null,
        'tax_provider_snapshot_json' => null,
        'totals_json' => null,
        'expires_at' => now()->subMinute(),
    ]);

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->get('/checkout/'.$checkout->id)
        ->assertOk()
        ->assertSee('Checkout #'.$checkout->id);

    $inventoryItem->refresh();
    $checkout->refresh();

    expect((int) $inventoryItem->quantity_reserved)->toBe(0);
    expect(storefrontEnumValue($checkout->status))->toBe('expired');
});

test('customer can create update and delete addresses from account', function (): void {
    $fixture = createStorefrontFixture();
    $customer = $fixture['customer'];

    $createResponse = $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->actingAs($customer, 'customer')
        ->post('/account/addresses', [
            'label' => 'Office',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => 'Second Street 5',
            'address2' => 'Floor 3',
            'city' => 'Berlin',
            'province' => 'Berlin',
            'province_code' => 'BE',
            'postal_code' => '10117',
            'country' => 'Germany',
            'country_code' => 'de',
            'phone' => '+4930123000',
            'is_default' => '1',
        ]);

    $createResponse->assertRedirect('/account/addresses')
        ->assertSessionHas('status');

    $createdAddress = CustomerAddress::query()
        ->where('customer_id', $customer->id)
        ->where('label', 'Office')
        ->first();

    expect($createdAddress)->toBeInstanceOf(CustomerAddress::class);
    expect($createdAddress?->is_default)->toBeTrue();

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->actingAs($customer, 'customer')
        ->put('/account/addresses/'.$createdAddress?->id, [
            'label' => 'Office HQ',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => 'Third Street 9',
            'city' => 'Berlin',
            'postal_code' => '10999',
            'country' => 'Germany',
            'country_code' => 'de',
            'is_default' => '0',
        ])
        ->assertRedirect('/account/addresses')
        ->assertSessionHas('status');

    $createdAddress?->refresh();

    expect($createdAddress?->label)->toBe('Office HQ');
    expect($createdAddress?->address_json['address1'] ?? null)->toBe('Third Street 9');

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->actingAs($customer, 'customer')
        ->delete('/account/addresses/'.$createdAddress?->id)
        ->assertRedirect('/account/addresses')
        ->assertSessionHas('status');

    $this->assertDatabaseMissing('customer_addresses', [
        'id' => $createdAddress?->id,
    ]);
});

test('address form validates required fields', function (): void {
    $fixture = createStorefrontFixture();

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->actingAs($fixture['customer'], 'customer')
        ->post('/account/addresses', [
            'label' => 'Invalid',
            'first_name' => '',
            'last_name' => '',
            'address1' => '',
            'city' => '',
            'postal_code' => '',
            'country_code' => '',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors([
            'first_name',
            'last_name',
            'address1',
            'city',
            'postal_code',
            'country_code',
        ]);
});
