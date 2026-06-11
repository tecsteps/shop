<?php

use App\Enums\InventoryPolicy;
use App\Enums\StoreUserRole;
use App\Models\Checkout;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreUser;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\PendingAwaitablePage;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function (): void {
        $this->seed();

        registerBrowserTestDomain();

        // The demo seed includes webhook subscriptions; the sync queue would
        // deliver them inline during checkout and fail on real HTTP calls.
        Http::fake();
    })
    ->in('Browser');

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

/**
 * Map the browser test server hostname (127.0.0.1) to the demo store so the
 * ResolveStore middleware resolves the tenant exactly as it would for the
 * seeded acme-fashion.test domain. Pest's browser plugin serves the app
 * in-process on 127.0.0.1, which cannot be resolved via Herd's dnsmasq.
 */
function registerBrowserTestDomain(): void
{
    $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();

    StoreDomain::query()->create([
        'store_id' => $store->getKey(),
        'hostname' => '127.0.0.1',
        'type' => 'storefront',
        'is_primary' => false,
        'tls_mode' => 'managed',
    ]);
}

/**
 * Re-point the browser test hostname (127.0.0.1) at another seeded store so
 * subsequent requests resolve that tenant through the real ResolveStore
 * middleware. Used by the tenant isolation browser tests.
 */
function switchBrowserTestDomainToStore(string $handle): void
{
    $store = Store::query()->where('handle', $handle)->firstOrFail();

    StoreDomain::query()
        ->where('hostname', '127.0.0.1')
        ->update(['store_id' => $store->getKey()]);

    Cache::forget('store_domain:127.0.0.1');
}

/**
 * Browser test helper: log in to the admin panel as the seeded admin user
 * and land on the dashboard.
 */
function browserLoginAsAdmin(): PendingAwaitablePage
{
    $page = visit('/admin/login');

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('@admin-login-button')
        ->assertSee('Dashboard');

    return $page;
}

/**
 * Browser test helper: log in to the storefront account as the seeded
 * customer (customer@acme.test) and land on the account dashboard.
 */
function browserLoginAsCustomer(): PendingAwaitablePage
{
    $page = visit('/account/login');

    $page->fill('email', 'customer@acme.test')
        ->fill('password', 'password')
        ->click('@customer-login-button')
        ->assertSee('My Account');

    return $page;
}

/**
 * Browser test helper: log in to the admin panel and open the detail page
 * of the given seeded order.
 */
function browserOpenAdminOrder(string $orderNumber): PendingAwaitablePage
{
    $page = browserLoginAsAdmin();

    $page->click('aside a:has-text("Orders")')
        ->assertSeeIn('h1[data-flux-heading]', 'Orders')
        ->click('a:has-text("'.$orderNumber.'")')
        ->assertSee('Timeline');

    return $page;
}

/**
 * Browser test helper: create a fulfillment with tracking details for the
 * single line of the currently open admin order detail page.
 */
function browserCreateFulfillment(
    PendingAwaitablePage $page,
    string $orderNumber,
    string $trackingCompany = 'DHL',
    string $trackingNumber = 'DHL123456789',
): PendingAwaitablePage {
    $order = \App\Models\Order::query()
        ->withoutGlobalScopes()
        ->where('order_number', $orderNumber)
        ->firstOrFail();

    $lineId = $order->lines()->first()->getKey();

    $page->click('@create-fulfillment-button')
        ->assertSee('Tracking company')
        ->click('@fulfill-line-checkbox-'.$lineId)
        ->fill('trackingCompany', $trackingCompany)
        ->fill('trackingNumber', $trackingNumber)
        ->click('@submit-fulfillment-button')
        ->assertSee('Fulfillment created');

    return $page;
}

/**
 * Browser test helper: add the seeded Classic Cotton T-Shirt (size M,
 * color Black) to the cart through the storefront product page.
 */
function browserAddClassicTeeToCart(): PendingAwaitablePage
{
    $page = visit('/products/classic-cotton-t-shirt');

    $page->assertSee('Classic Cotton T-Shirt')
        ->click('M')
        ->click('label[title="Black"]')
        ->click('Add to cart')
        ->assertSee('Added to cart');

    return $page;
}

/**
 * Browser test helper: fill the checkout shipping address form with a
 * German address and submit it.
 */
function browserFillCheckoutAddress(
    PendingAwaitablePage|AwaitableWebpage $page,
    string $firstName = 'Test',
    string $lastName = 'Buyer',
    string $address1 = 'Teststrasse 1',
    string $city = 'Berlin',
    string $postalCode = '10115',
    string $countryCode = 'DE',
): PendingAwaitablePage|AwaitableWebpage {
    $page->assertSee('First name')
        ->fill('[id="shipping.first_name"]', $firstName)
        ->fill('[id="shipping.last_name"]', $lastName)
        ->fill('[id="shipping.address1"]', $address1)
        ->fill('[id="shipping.city"]', $city)
        ->fill('[id="shipping.postal_code"]', $postalCode)
        ->select('[id="shipping.country_code"]', $countryCode)
        ->click('Continue');

    return $page;
}

/**
 * Browser test helper: drive a fresh cart with the Classic Cotton T-Shirt
 * through checkout steps 1-3 (contact, DE address, Standard Shipping) so
 * the payment step is visible.
 */
function browserReachCheckoutPaymentStep(string $email = 'test@example.com'): PendingAwaitablePage
{
    $page = browserAddClassicTeeToCart();

    $page->navigate('/cart')
        ->click('Checkout')
        ->assertSee('Contact information')
        ->fill('checkout-email', $email)
        ->click('Continue');

    browserFillCheckoutAddress($page);

    $page->assertSee('Standard Shipping')
        ->click('Standard Shipping')
        ->click('Continue')
        ->assertSee('Select a payment method');

    return $page;
}

/**
 * Create a full store context: Organization, Store, StoreDomain, and a User
 * with the Owner role. Binds the store in the container as "current_store".
 *
 * @param  array<string, mixed>  $storeAttributes
 * @return array{organization: Organization, store: Store, domain: StoreDomain, user: User}
 */
function createStoreContext(array $storeAttributes = []): array
{
    $organization = Organization::factory()->create();
    $store = Store::factory()->for($organization)->create($storeAttributes);
    $domain = StoreDomain::factory()->for($store)->create();

    $user = User::factory()->create();

    StoreUser::query()->create([
        'store_id' => $store->getKey(),
        'user_id' => $user->getKey(),
        'role' => StoreUserRole::Owner,
    ]);

    app()->instance('current_store', $store);

    return [
        'organization' => $organization,
        'store' => $store,
        'domain' => $domain,
        'user' => $user,
    ];
}

/**
 * Create an active product with a single active default variant (and an
 * inventory item) for the given store. Used by cart and checkout tests.
 *
 * @param  array<string, mixed>  $variantAttributes
 */
function createPurchasableVariant(
    Store $store,
    int $priceAmount = 2500,
    int $quantityOnHand = 100,
    array $variantAttributes = [],
    InventoryPolicy $policy = InventoryPolicy::Deny,
): ProductVariant {
    $product = Product::factory()->active()->for($store)->create();

    $variant = ProductVariant::factory()
        ->asDefault()
        ->priced($priceAmount)
        ->for($product)
        ->create($variantAttributes);

    InventoryItem::factory()
        ->forVariant($variant)
        ->withStock($quantityOnHand)
        ->create(['policy' => $policy]);

    return $variant;
}

/**
 * A complete, valid checkout shipping address (Germany by default).
 *
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function validShippingAddress(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Erika',
        'last_name' => 'Mustermann',
        'address1' => 'Musterstrasse 1',
        'city' => 'Berlin',
        'postal_code' => '10115',
        'country_code' => 'DE',
    ], $overrides);
}

/**
 * Drive a checkout for one purchasable variant through the full state
 * machine up to payment_selected (DE address, flat 499 shipping rate when
 * shipping is required). Used by the Phase 5 order and payment tests.
 *
 * @param  array<string, mixed>  $variantAttributes
 */
function createPaymentSelectedCheckout(
    Store $store,
    string $paymentMethod = 'credit_card',
    int $quantity = 1,
    int $priceAmount = 2500,
    int $quantityOnHand = 100,
    array $variantAttributes = [],
    ?Customer $customer = null,
    string $email = 'shopper@example.test',
    ?string $discountCode = null,
): Checkout {
    $variant = createPurchasableVariant($store, $priceAmount, $quantityOnHand, $variantAttributes);

    $cartService = app(CartService::class);
    $cart = $cartService->create($store);
    $cartService->addLine($cart, $variant->getKey(), $quantity);

    $checkoutService = app(CheckoutService::class);
    $checkout = $checkoutService->createFromCart($cart, $customer, $discountCode);

    $checkout = $checkoutService->setAddress($checkout, [
        'email' => $email,
        'shipping_address' => validShippingAddress(),
    ]);

    if ($cart->requiresShipping()) {
        $zone = ShippingZone::factory()->for($store)->create(['countries_json' => ['DE']]);
        $rate = ShippingRate::factory()->for($zone, 'zone')->flatAmount(499)->create();
        $checkout = $checkoutService->setShippingMethod($checkout, $rate->getKey());
    } else {
        $checkout = $checkoutService->setShippingMethod($checkout);
    }

    return $checkoutService->selectPaymentMethod($checkout, $paymentMethod);
}

/**
 * Create an additional user with the given role on the store.
 */
function createStoreMember(Store $store, StoreUserRole $role): User
{
    $user = User::factory()->create();

    StoreUser::query()->create([
        'store_id' => $store->getKey(),
        'user_id' => $user->getKey(),
        'role' => $role,
    ]);

    return $user;
}

/**
 * Authenticate as an admin user and put their store in the session.
 */
function actingAsAdmin(User $user, ?Store $store = null): TestCase
{
    $store ??= $user->stores()->first();

    return test()
        ->actingAs($user)
        ->withSession(['current_store_id' => $store?->getKey()]);
}

/**
 * Authenticate as a storefront customer via the customer guard.
 */
function actingAsCustomer(Customer $customer): TestCase
{
    return test()->actingAs($customer, 'customer');
}
