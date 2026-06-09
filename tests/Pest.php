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
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

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
