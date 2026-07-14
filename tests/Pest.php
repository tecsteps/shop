<?php

use App\Enums\PaymentMethod;
use App\Enums\StoreUserRole;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreSettings;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature');
pest()->use(RefreshDatabase::class)->in('Feature/Shop');

/**
 * Bind an explicit tenant for global-scope and policy assertions.
 */
function bindStore(Store $store): Store
{
    app()->forgetInstance('current_store');
    app()->instance('current_store', $store);

    return $store;
}

/**
 * @param  array<string, mixed>  $storeAttributes
 * @param  array<string, mixed>  $userAttributes
 * @return array{organization: Organization, store: Store, domain: StoreDomain, user: User}
 */
function createStoreContext(
    StoreUserRole|string $role = StoreUserRole::Owner,
    array $storeAttributes = [],
    array $userAttributes = [],
): array {
    $organization = Organization::factory()->create();
    $store = Store::factory()->for($organization)->create($storeAttributes);
    $domain = StoreDomain::factory()->for($store)->create([
        'hostname' => $store->handle.'.test',
        'type' => 'storefront',
        'is_primary' => true,
    ]);
    $user = User::factory()->create($userAttributes);
    $role = $role instanceof StoreUserRole ? $role->value : $role;
    DB::table('store_users')->insert([
        'store_id' => $store->id,
        'user_id' => $user->id,
        'role' => $role,
        'created_at' => now(),
    ]);
    StoreSettings::query()->create([
        'store_id' => $store->id,
        'settings_json' => [
            'order_number_prefix' => '#',
            'bank_transfer_cancel_days' => 7,
            'cart_abandon_days' => 14,
        ],
    ]);
    bindStore($store);

    return compact('organization', 'store', 'domain', 'user');
}

function actingAsAdmin(User $user, Store $store): User
{
    bindStore($store);
    test()->actingAs($user, 'web');
    session(['current_store_id' => $store->id]);

    return $user;
}

function actingAsCustomer(Customer $customer): Customer
{
    $store = Store::query()->findOrFail($customer->store_id);
    bindStore($store);
    test()->actingAs($customer, 'customer');

    return $customer;
}

/**
 * Create an active SKU with explicit inventory. Factories intentionally do not
 * hide whether inventory records are created by a service or by the caller.
 *
 * @param  array<string, mixed>  $productAttributes
 * @param  array<string, mixed>  $variantAttributes
 * @param  array<string, mixed>  $inventoryAttributes
 * @return array{product: Product, variant: ProductVariant, inventory: InventoryItem}
 */
function makeSellableVariant(
    Store $store,
    array $productAttributes = [],
    array $variantAttributes = [],
    array $inventoryAttributes = [],
): array {
    bindStore($store);
    $title = (string) ($productAttributes['title'] ?? 'Product '.Str::lower(Str::random(8)));
    $product = Product::factory()->for($store)->create([
        'title' => $title,
        'handle' => $productAttributes['handle'] ?? Str::slug($title).'-'.Str::lower(Str::random(5)),
        'status' => 'active',
        'published_at' => now(),
        ...$productAttributes,
    ]);
    $variant = ProductVariant::factory()->for($product)->create([
        'sku' => $variantAttributes['sku'] ?? 'SKU-'.Str::upper(Str::random(10)),
        'price_amount' => 2500,
        'currency' => $store->default_currency,
        'requires_shipping' => true,
        'status' => 'active',
        ...$variantAttributes,
    ]);
    $inventory = InventoryItem::withoutGlobalScopes()->firstOrCreate(
        ['variant_id' => $variant->id],
        ['store_id' => $store->id],
    );
    $inventory->fill([
        'store_id' => $store->id,
        'quantity_on_hand' => 20,
        'quantity_reserved' => 0,
        'policy' => 'deny',
        ...$inventoryAttributes,
    ])->save();

    return compact('product', 'variant', 'inventory');
}

/**
 * @return array{cart: Cart, line: CartLine}
 */
function makeCartWithLine(Store $store, ProductVariant $variant, int $quantity = 1): array
{
    /** @var CartService $service */
    $service = app(CartService::class);
    $cart = $service->create($store);
    $line = $service->addLine($cart, $variant, $quantity, 1);

    return compact('cart', 'line');
}

/** @return array<string, mixed> */
function validCheckoutAddress(string $countryCode = 'DE', string $provinceCode = 'BE'): array
{
    return [
        'email' => 'buyer@example.test',
        'shipping_address' => [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'address1' => 'Example Street 1',
            'city' => 'Berlin',
            'province_code' => $provinceCode,
            'country' => $countryCode,
            'country_code' => $countryCode,
            'postal_code' => '10115',
        ],
    ];
}

/**
 * @return array{store: Store, variant: ProductVariant, cart: Cart, checkout: Checkout, rate: ?ShippingRate}
 */
function checkoutReadyForPayment(PaymentMethod $method = PaymentMethod::CreditCard, bool $digital = false, int $quantity = 2): array
{
    $store = createStoreContext()['store'];
    $sku = makeSellableVariant(
        $store,
        productAttributes: ['title' => $digital ? 'Digital Guide' : 'Physical Guide'],
        variantAttributes: [
            'sku' => $digital ? 'DIGITAL-GUIDE' : 'PHYSICAL-GUIDE',
            'price_amount' => 2500,
            'requires_shipping' => ! $digital,
            'weight_g' => $digital ? 0 : 500,
        ],
        inventoryAttributes: ['quantity_on_hand' => 10],
    );
    ['cart' => $cart] = makeCartWithLine($store, $sku['variant'], $quantity);
    $rate = null;
    if (! $digital) {
        $zone = ShippingZone::factory()->for($store)->create(['countries_json' => ['DE'], 'regions_json' => []]);
        $rate = ShippingRate::factory()->for($zone, 'zone')->create(['name' => 'Standard', 'config_json' => ['amount' => 499]]);
    }

    $service = app(CheckoutService::class);
    $checkout = $service->create($cart);
    $checkout = $service->setAddress($checkout, validCheckoutAddress());
    $checkout = $service->setShippingMethod($checkout, $rate?->id);
    $checkout = $service->selectPaymentMethod($checkout, $method);

    return ['store' => $store, 'variant' => $sku['variant'], 'cart' => $cart, 'checkout' => $checkout, 'rate' => $rate];
}

/**
 * @return array{store: Store, variant: ProductVariant, order: Order, line: OrderLine, payment: Payment}
 */
function paidOrderFixture(int $quantity = 2, bool $digital = false): array
{
    $store = createStoreContext()['store'];
    $sku = makeSellableVariant($store, variantAttributes: [
        'price_amount' => 2500,
        'requires_shipping' => ! $digital,
        'weight_g' => $digital ? 0 : 500,
    ], inventoryAttributes: ['quantity_on_hand' => 8]);
    $order = Order::factory()->for($store)->create([
        'customer_id' => null,
        'payment_method' => 'credit_card',
        'status' => 'paid',
        'financial_status' => 'paid',
        'fulfillment_status' => 'unfulfilled',
        'subtotal_amount' => 2500 * $quantity,
        'shipping_amount' => 0,
        'tax_amount' => 0,
        'total_amount' => 2500 * $quantity,
    ]);
    $line = OrderLine::factory()->for($order)->create([
        'product_id' => $sku['product']->id,
        'variant_id' => $sku['variant']->id,
        'title_snapshot' => $sku['product']->title,
        'sku_snapshot' => $sku['variant']->sku,
        'quantity' => $quantity,
        'unit_price_amount' => 2500,
        'total_amount' => 2500 * $quantity,
    ]);
    $payment = Payment::factory()->for($order)->create([
        'method' => 'credit_card',
        'status' => 'captured',
        'amount' => 2500 * $quantity,
        'currency' => $store->default_currency,
    ]);

    return ['store' => $store, 'variant' => $sku['variant'], 'order' => $order, 'line' => $line, 'payment' => $payment];
}
