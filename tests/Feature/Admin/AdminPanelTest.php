<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Admin\Orders\Show as OrderShow;
use App\Livewire\Admin\Products\Form as ProductForm;
use App\Livewire\Admin\Settings\Index as SettingsIndex;
use App\Livewire\Admin\Themes\Editor as ThemeEditor;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
    $this->seed();
    $this->store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $this->user = User::query()->where('email', 'admin@example.com')->firstOrFail();
});

function actAsAdmin($test): void
{
    $test->actingAs($test->user);
    session(['current_store_id' => $test->store->id]);
    app()->instance('current_store', $test->store);
}

test('admin guests are redirected to the admin login page', function (): void {
    $this->get('/admin')
        ->assertRedirect(route('admin.login'));
});

test('admin can log in and gets an active store in session', function (): void {
    Livewire::test(AdminLogin::class)
        ->set('email', 'admin@example.com')
        ->set('password', 'password')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.dashboard'));

    expect(Auth::guard('web')->check())->toBeTrue()
        ->and(session('current_store_id'))->toBe($this->store->id);
});

test('admin shell pages render with seeded store data', function (): void {
    $this->actingAs($this->user);
    $order = Order::query()->where('order_number', '#1001')->firstOrFail();
    $customer = $order->customer;
    $theme = Theme::query()->firstOrFail();

    foreach ([
        '/admin' => 'Dashboard',
        '/admin/products' => 'Linen Shirt',
        '/admin/products/create' => 'Create product',
        '/admin/collections' => 'Summer Essentials',
        '/admin/collections/create' => 'Create collection',
        '/admin/inventory' => 'Track available stock',
        '/admin/orders' => '#1001',
        "/admin/orders/{$order->id}" => '#1001',
        '/admin/customers' => 'jane@example.com',
        "/admin/customers/{$customer->id}" => 'jane@example.com',
        '/admin/discounts' => 'WELCOME10',
        '/admin/discounts/create' => 'Create discount',
        '/admin/settings' => 'Acme Fashion',
        '/admin/settings/shipping' => 'Shipping',
        '/admin/settings/taxes' => 'Manual rate',
        '/admin/themes' => 'Default',
        "/admin/themes/{$theme->id}/editor" => 'Storefront theme editor',
        '/admin/pages' => 'Pages',
        '/admin/pages/create' => 'Create page',
        '/admin/navigation' => 'Main menu',
        '/admin/analytics' => 'Total sales',
        '/admin/search/settings' => 'Synonyms',
        '/admin/apps' => 'Product Reviews',
        '/admin/apps/reviews' => 'Reviews',
        '/admin/developers' => 'API tokens',
    ] as $uri => $expectedText) {
        $this->withSession(['current_store_id' => $this->store->id])
            ->get($uri)
            ->assertOk()
            ->assertSee($expectedText);
    }
});

test('admin can create a product with default variant inventory', function (): void {
    actAsAdmin($this);

    Livewire::test(ProductForm::class)
        ->set('title', 'Canvas Tote')
        ->set('status', 'active')
        ->set('vendor', 'Acme')
        ->set('productType', 'Accessories')
        ->set('priceAmount', 2500)
        ->set('sku', 'BAG-TOTE')
        ->set('quantityOnHand', 8)
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::query()->where('title', 'Canvas Tote')->firstOrFail();
    $variant = $product->variants()->with('inventoryItem')->firstOrFail();

    expect($product->handle)->toBe('canvas-tote')
        ->and($variant->price_amount)->toBe(2500)
        ->and($variant->sku)->toBe('BAG-TOTE')
        ->and($variant->inventoryItem->quantity_on_hand)->toBe(8);
});

test('admin can create a product with multi option variants', function (): void {
    actAsAdmin($this);

    Livewire::test(ProductForm::class)
        ->set('title', 'Matrix Hoodie')
        ->set('status', 'active')
        ->set('vendor', 'Acme')
        ->set('productType', 'Hoodies')
        ->set('priceAmount', 5900)
        ->set('quantityOnHand', 3)
        ->set('options', [
            ['name' => 'Size', 'values' => ['S', 'M']],
            ['name' => 'Color', 'values' => ['Black', 'White']],
        ])
        ->call('generateVariants')
        ->set('variants.0.sku', 'HD-S-BLK')
        ->set('variants.0.priceAmount', 5900)
        ->set('variants.0.quantityOnHand', 4)
        ->set('variants.3.sku', 'HD-M-WHT')
        ->set('variants.3.priceAmount', 6200)
        ->set('variants.3.quantityOnHand', 7)
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::query()
        ->where('title', 'Matrix Hoodie')
        ->with('options.values', 'variants.optionValues.option', 'variants.inventoryItem')
        ->firstOrFail();

    $whiteMedium = $product->variants
        ->first(fn ($variant): bool => $variant->optionValues->pluck('value')->sort()->values()->all() === ['M', 'White']);

    expect($product->options)->toHaveCount(2)
        ->and($product->options->firstWhere('name', 'Size')->values)->toHaveCount(2)
        ->and($product->variants)->toHaveCount(4)
        ->and($whiteMedium->sku)->toBe('HD-M-WHT')
        ->and($whiteMedium->price_amount)->toBe(6200)
        ->and($whiteMedium->inventoryItem->quantity_on_hand)->toBe(7);
});

test('admin can configure storefront home section order and visibility', function (): void {
    actAsAdmin($this);

    $theme = Theme::query()->where('status', 'published')->firstOrFail();

    Livewire::test(ThemeEditor::class, ['theme' => $theme])
        ->call('moveSectionUp', 'featured_products')
        ->set('homeSections.2.enabled', false)
        ->call('selectSection', 'hero')
        ->set('settings.home.hero_heading', 'Editorial Launch')
        ->call('selectSection', 'rich_text')
        ->set('settings.home.rich_text_heading', 'Material notes')
        ->set('settings.home.rich_text_html', '<p>Breathable cotton<script>alert(1)</script></p>')
        ->call('save')
        ->assertHasNoErrors();

    $settings = $theme->fresh()->settings->settings_json;

    expect(data_get($settings, 'home.sections.1.key'))->toBe('featured_products')
        ->and(data_get($settings, 'home.sections.2.key'))->toBe('featured_collections')
        ->and(data_get($settings, 'home.sections.2.enabled'))->toBeFalse()
        ->and(data_get($settings, 'home.hero_heading'))->toBe('Editorial Launch')
        ->and(data_get($settings, 'home.rich_text_html'))->toBe('<p>Breathable cotton</p>');

    $this->get('http://shop.test/')
        ->assertOk()
        ->assertSeeInOrder(['Editorial Launch', 'Featured Products', 'Material notes'])
        ->assertDontSee('Featured Collections');
});

test('admin can confirm a pending bank transfer order', function (): void {
    actAsAdmin($this);

    $order = Order::query()->where('order_number', '#1002')->firstOrFail();

    Livewire::test(OrderShow::class, ['order' => $order])
        ->call('confirmBankTransfer')
        ->assertHasNoErrors();

    expect($order->fresh()->financial_status)->toBe(FinancialStatus::Paid);
});

test('admin can create ship and deliver a fulfillment', function (): void {
    actAsAdmin($this);

    $order = Order::query()->where('order_number', '#1001')->firstOrFail();
    $component = Livewire::test(OrderShow::class, ['order' => $order]);

    $component
        ->set('trackingCompany', 'DHL')
        ->set('trackingNumber', 'DHL123456789')
        ->set('trackingUrl', 'https://tracking.test/DHL123456789')
        ->call('fulfillAll')
        ->assertHasNoErrors()
        ->assertSee('All line items have been fulfilled.');

    $fulfillment = $order->fulfillments()->firstOrFail();

    expect($fulfillment->tracking_company)->toBe('DHL')
        ->and($fulfillment->tracking_number)->toBe('DHL123456789')
        ->and($fulfillment->status)->toBe(FulfillmentShipmentStatus::Pending);

    $component
        ->call('markFulfillmentShipped', $fulfillment->id)
        ->assertHasNoErrors();

    expect($fulfillment->refresh()->status)->toBe(FulfillmentShipmentStatus::Shipped)
        ->and($fulfillment->shipped_at)->not->toBeNull();

    $component
        ->call('markFulfillmentDelivered', $fulfillment->id)
        ->assertHasNoErrors();

    expect($fulfillment->refresh()->status)->toBe(FulfillmentShipmentStatus::Delivered)
        ->and($fulfillment->delivered_at)->not->toBeNull();
});

test('admin can create a selected quantity fulfillment', function (): void {
    actAsAdmin($this);

    $order = Order::query()->where('order_number', '#1001')->firstOrFail();
    $line = $order->lines()->firstOrFail();
    $line->forceFill(['quantity' => 2])->save();

    Livewire::test(OrderShow::class, ['order' => $order])
        ->set("fulfillmentLines.{$line->id}", 1)
        ->call('createFulfillment')
        ->assertHasNoErrors();

    $fulfillment = $order->fulfillments()->with('lines')->firstOrFail();

    expect($fulfillment->lines)->toHaveCount(1)
        ->and($fulfillment->lines->first()->quantity)->toBe(1)
        ->and($order->refresh()->fulfillment_status)->toBe(FulfillmentStatus::Partial);
});

test('admin can refund selected line quantities', function (): void {
    actAsAdmin($this);

    $order = Order::query()->where('order_number', '#1001')->firstOrFail();
    $line = $order->lines()->with('variant.inventoryItem')->firstOrFail();
    $inventory = $line->variant->inventoryItem;
    $stockBeforeRefund = $inventory->quantity_on_hand;

    Livewire::test(OrderShow::class, ['order' => $order])
        ->set("refundLines.{$line->id}", 1)
        ->set('refundReason', 'Customer returned one item')
        ->set('restockRefund', true)
        ->call('refund')
        ->assertHasNoErrors();

    $refund = $order->refunds()->firstOrFail();

    expect($refund->amount)->toBe($line->total_amount)
        ->and($refund->reason)->toBe('Customer returned one item')
        ->and($order->refresh()->financial_status)->toBe(FinancialStatus::PartiallyRefunded)
        ->and($inventory->refresh()->quantity_on_hand)->toBe($stockBeforeRefund + 1);
});

test('admin can update store settings', function (): void {
    actAsAdmin($this);

    Livewire::test(SettingsIndex::class)
        ->set('name', 'Acme Admin Store')
        ->set('defaultCurrency', 'EUR')
        ->set('defaultLocale', 'en')
        ->set('timezone', 'Europe/Berlin')
        ->set('status', 'active')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->store->fresh()->name)->toBe('Acme Admin Store')
        ->and($this->store->fresh()->timezone)->toBe('Europe/Berlin');
});
