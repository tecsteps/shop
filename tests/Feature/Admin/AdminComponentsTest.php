<?php

use App\Enums\StoreUserRole;
use App\Livewire\Admin\Analytics\Index as AnalyticsIndex;
use App\Livewire\Admin\Apps\Index as AppsIndex;
use App\Livewire\Admin\Auth\Login;
use App\Livewire\Admin\Collections\Form as CollectionForm;
use App\Livewire\Admin\Collections\Index as CollectionIndex;
use App\Livewire\Admin\Customers\Index as CustomerIndex;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Developers\Index as DevelopersIndex;
use App\Livewire\Admin\Discounts\Index as DiscountIndex;
use App\Livewire\Admin\Inventory\Index as InventoryIndex;
use App\Livewire\Admin\Navigation\Index as NavigationIndex;
use App\Livewire\Admin\Orders\Index as OrderIndex;
use App\Livewire\Admin\Orders\Show as OrderShow;
use App\Livewire\Admin\Pages\Form as PageForm;
use App\Livewire\Admin\Pages\Index as PageIndex;
use App\Livewire\Admin\Products\Form as ProductForm;
use App\Livewire\Admin\Products\Index as ProductIndex;
use App\Livewire\Admin\SearchSettings;
use App\Livewire\Admin\Settings\Domains;
use App\Livewire\Admin\Settings\General;
use App\Livewire\Admin\Settings\Shipping;
use App\Livewire\Admin\Settings\Tax;
use App\Livewire\Admin\Themes\Index as ThemeIndex;
use App\Models\Collection;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Organization;
use App\Models\Page;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\StoreUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

function createAdminContext(StoreUserRole $role = StoreUserRole::Owner): array
{
    $store = Store::factory()->for(Organization::factory())->create(['default_currency' => 'EUR']);
    $user = User::factory()->create();
    StoreUser::query()->create(['store_id' => $store->getKey(), 'user_id' => $user->getKey(), 'role' => $role, 'created_at' => now()]);
    app()->instance('current_store', $store);
    session(['current_store_id' => $store->getKey()]);

    return [$user, $store];
}

test('an active store administrator can sign in', function () {
    [$user] = createAdminContext();
    auth()->logout();

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect('/admin');

    $this->assertAuthenticatedAs($user);
});

test('the product list is scoped to the active store', function () {
    [$user, $store] = createAdminContext();
    $visibleProduct = Product::factory()->for($store)->create(['title' => 'Visible Product']);
    $otherProduct = Product::factory()->create(['title' => 'Hidden Product']);

    Livewire::actingAs($user)
        ->test(ProductIndex::class)
        ->assertSee($visibleProduct->title)
        ->assertDontSee($otherProduct->title);
});

test('an administrator can create a product with inventory', function () {
    [$user, $store] = createAdminContext();

    Livewire::actingAs($user)
        ->test(ProductForm::class)
        ->set('title', 'Admin Created Shirt')
        ->set('handle', 'admin-created-shirt')
        ->set('status', 'active')
        ->set('variants', [[
            'sku' => 'ADMIN-SHIRT-M',
            'price' => 2499,
            'compareAtPrice' => null,
            'quantity' => 12,
            'requiresShipping' => true,
        ]])
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    $product = Product::query()->where('store_id', $store->getKey())->where('handle', 'admin-created-shirt')->firstOrFail();
    expect($product->variants)->toHaveCount(1)
        ->and($product->variants->first()->inventoryItem->quantity_on_hand)->toBe(12);
});

test('an administrator can create a collection with assigned products', function () {
    [$user, $store] = createAdminContext();
    $product = Product::factory()->for($store)->create();

    Livewire::actingAs($user)
        ->test(CollectionForm::class)
        ->set('title', 'Summer Favorites')
        ->set('handle', 'summer-favorites')
        ->set('assignedProductIds', [$product->getKey()])
        ->call('save')
        ->assertHasNoErrors();

    $collection = Collection::query()->where('store_id', $store->getKey())->where('handle', 'summer-favorites')->firstOrFail();
    expect($collection->products()->pluck('products.id')->all())->toBe([$product->getKey()]);
});

test('staff can update inventory quantities', function () {
    [$user, $store] = createAdminContext(StoreUserRole::Staff);
    $product = Product::factory()->for($store)->create();
    $variant = ProductVariant::factory()->for($product)->create();
    $inventory = $variant->inventoryItem;

    Livewire::actingAs($user)
        ->test(InventoryIndex::class)
        ->call('updateQuantity', $inventory->getKey(), 27)
        ->assertDispatched('toast');

    expect($inventory->refresh()->quantity_on_hand)->toBe(27);
});

test('an owner can update general store settings', function () {
    [$user, $store] = createAdminContext();

    Livewire::actingAs($user)
        ->test(General::class)
        ->set('name', 'Updated Store')
        ->set('contactEmail', 'hello@example.com')
        ->set('currency', 'EUR')
        ->set('locale', 'de')
        ->set('timezone', 'Europe/Berlin')
        ->call('save')
        ->assertHasNoErrors();

    expect($store->refresh()->name)->toBe('Updated Store')
        ->and($store->settings->settings_json['contact_email'])->toBe('hello@example.com');
});

test('an administrator can publish a content page', function () {
    [$user, $store] = createAdminContext();

    Livewire::actingAs($user)
        ->test(PageForm::class)
        ->set('title', 'About our materials')
        ->set('handle', 'about-materials')
        ->set('bodyHtml', '<p>Responsibly sourced.</p>')
        ->set('status', 'published')
        ->call('save')
        ->assertHasNoErrors();

    $page = Page::query()->where('store_id', $store->getKey())->where('handle', 'about-materials')->firstOrFail();
    expect($page->published_at)->not->toBeNull();
});

test('an administrator can confirm a bank transfer payment', function () {
    [$user, $store] = createAdminContext();
    $product = Product::factory()->for($store)->create();
    $variant = ProductVariant::factory()->for($product)->create(['requires_shipping' => true]);
    $variant->inventoryItem->update(['quantity_on_hand' => 10, 'quantity_reserved' => 1]);
    $order = Order::factory()->for($store)->create(['payment_method' => 'bank_transfer', 'status' => 'pending', 'financial_status' => 'pending']);
    OrderLine::factory()->for($order)->create(['product_id' => $product->getKey(), 'variant_id' => $variant->getKey(), 'quantity' => 1]);
    Payment::factory()->for($order)->create(['method' => 'bank_transfer', 'status' => 'pending']);

    Livewire::actingAs($user)
        ->test(OrderShow::class, ['order' => $order])
        ->call('confirmPayment')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    expect($order->refresh()->financial_status->value)->toBe('paid')
        ->and($variant->inventoryItem->refresh()->quantity_on_hand)->toBe(9)
        ->and($variant->inventoryItem->quantity_reserved)->toBe(0);
});

test('staff can fulfill and ship a paid order', function () {
    [$user, $store] = createAdminContext(StoreUserRole::Staff);
    $product = Product::factory()->for($store)->create();
    $variant = ProductVariant::factory()->for($product)->create(['requires_shipping' => true]);
    $order = Order::factory()->for($store)->create(['financial_status' => 'paid', 'fulfillment_status' => 'unfulfilled']);
    $line = OrderLine::factory()->for($order)->create(['product_id' => $product->getKey(), 'variant_id' => $variant->getKey(), 'quantity' => 1]);

    $component = Livewire::actingAs($user)
        ->test(OrderShow::class, ['order' => $order])
        ->set('trackingCompany', 'DHL')
        ->set('trackingNumber', 'DHL123')
        ->set('fulfillmentQuantities', [$line->getKey() => 1])
        ->call('createFulfillment')
        ->assertHasNoErrors();

    $fulfillment = $order->fulfillments()->firstOrFail();
    $component->call('markShipped', $fulfillment->getKey())->assertHasNoErrors();

    expect($fulfillment->refresh()->status->value)->toBe('shipped');
});

test('an owner can issue a partial refund', function () {
    [$user, $store] = createAdminContext();
    $order = Order::factory()->for($store)->create(['financial_status' => 'paid', 'total_amount' => 5499]);
    Payment::factory()->for($order)->create(['amount' => 5499, 'status' => 'captured']);

    Livewire::actingAs($user)
        ->test(OrderShow::class, ['order' => $order])
        ->set('refundAmount', '10.00')
        ->set('refundReason', 'Customer request')
        ->call('processRefund')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    expect($order->refresh()->financial_status->value)->toBe('partially_refunded')
        ->and($order->refunds()->value('amount'))->toBe(1000);
});

test('admin index and settings screens render', function (string $component, string $heading) {
    [$user] = createAdminContext();

    Livewire::actingAs($user)
        ->test($component)
        ->assertSee($heading);
})->with([
    'dashboard' => [Dashboard::class, 'Dashboard'],
    'collections' => [CollectionIndex::class, 'Collections'],
    'inventory' => [InventoryIndex::class, 'Inventory'],
    'orders' => [OrderIndex::class, 'Orders'],
    'customers' => [CustomerIndex::class, 'Customers'],
    'discounts' => [DiscountIndex::class, 'Discounts'],
    'domains' => [Domains::class, 'Domains'],
    'shipping' => [Shipping::class, 'Shipping'],
    'tax' => [Tax::class, 'Tax Settings'],
    'themes' => [ThemeIndex::class, 'Themes'],
    'pages' => [PageIndex::class, 'Pages'],
    'navigation' => [NavigationIndex::class, 'Navigation'],
    'analytics' => [AnalyticsIndex::class, 'Analytics'],
    'search' => [SearchSettings::class, 'Search Settings'],
    'apps' => [AppsIndex::class, 'Apps'],
    'developers' => [DevelopersIndex::class, 'Developers'],
]);
