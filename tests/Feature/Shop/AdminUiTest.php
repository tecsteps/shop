<?php

use App\Jobs\ProcessMediaUpload;
use App\Livewire\Admin\Collections\Form as CollectionForm;
use App\Livewire\Admin\Customers\Show as CustomerShow;
use App\Livewire\Admin\Discounts\Form as DiscountForm;
use App\Livewire\Admin\Inventory\Index as InventoryIndex;
use App\Livewire\Admin\Orders\Show as OrderShow;
use App\Livewire\Admin\Products\Form as ProductForm;
use App\Livewire\Admin\Settings\Index as SettingsIndex;
use App\Livewire\Admin\Settings\Shipping as ShippingSettings;
use App\Livewire\Admin\Settings\Taxes as TaxSettings;
use App\Models\App as AppModel;
use App\Models\AppInstallation;
use App\Models\Collection;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Page;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\StoreDomain;
use App\Models\TaxSettings as TaxSettingsModel;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

describe('admin web routes and middleware', function () {
    it('renders every public admin authentication screen', function () {
        $this->get('/admin/login')->assertOk()->assertSee('Sign in to your store');
        $this->get('/admin/forgot-password')->assertOk()->assertSee('Reset your password');
        $this->get('/admin/reset-password/example-token')->assertOk()->assertSee('Choose a new password');
    });

    it('redirects guests and unverified users and rejects users without a store', function () {
        $this->get('/admin')->assertRedirect('/admin/login');

        $unverified = createStoreContext(userAttributes: ['email_verified_at' => null]);
        actingAsAdmin($unverified['user'], $unverified['store']);
        $this->get('/admin')->assertRedirect(route('verification.notice'));

        $this->actingAs(User::factory()->create(), 'web')
            ->withSession(['current_store_id' => null])
            ->get('/admin')
            ->assertForbidden();
    });

    it('renders the complete admin surface for an owner', function () {
        $context = createStoreContext();
        actingAsAdmin($context['user'], $context['store']);
        $product = Product::factory()->for($context['store'])->create(['title' => 'Route Product']);
        $collection = Collection::factory()->for($context['store'])->create(['title' => 'Route Collection']);
        $order = Order::factory()->for($context['store'])->create(['customer_id' => null, 'order_number' => '#ROUTE-1']);
        $customer = Customer::factory()->for($context['store'])->create(['name' => 'Route Customer']);
        $discount = Discount::factory()->for($context['store'])->create(['code' => 'ROUTE20']);
        $theme = Theme::factory()->for($context['store'])->create(['name' => 'Route Theme']);
        $page = Page::factory()->for($context['store'])->create(['title' => 'Route Page']);
        $app = AppModel::query()->create(['name' => 'Route App', 'status' => 'active']);
        $installation = AppInstallation::withoutGlobalScopes()->create([
            'store_id' => $context['store']->id,
            'app_id' => $app->id,
            'scopes_json' => ['read_products'],
            'status' => 'active',
            'installed_at' => now(),
        ]);

        $pages = [
            '/admin' => 'Dashboard',
            '/admin/products' => 'Products',
            '/admin/products/create' => 'Add product',
            "/admin/products/{$product->id}/edit" => 'Route Product',
            '/admin/inventory' => 'Inventory',
            '/admin/collections' => 'Collections',
            '/admin/collections/create' => 'Add collection',
            "/admin/collections/{$collection->id}/edit" => 'Route Collection',
            '/admin/orders' => 'Orders',
            "/admin/orders/{$order->id}" => '#ROUTE-1',
            '/admin/customers' => 'Customers',
            "/admin/customers/{$customer->id}" => 'Route Customer',
            '/admin/discounts' => 'Discounts',
            '/admin/discounts/create' => 'Create discount',
            "/admin/discounts/{$discount->id}/edit" => 'ROUTE20',
            '/admin/settings' => 'Store Settings',
            '/admin/settings/shipping' => 'Shipping',
            '/admin/settings/taxes' => 'Tax Settings',
            '/admin/themes' => 'Themes',
            "/admin/themes/{$theme->id}/editor" => 'Customize Route Theme',
            '/admin/pages' => 'Pages',
            '/admin/pages/create' => 'Create page',
            "/admin/pages/{$page->id}/edit" => 'Route Page',
            '/admin/navigation' => 'Navigation',
            '/admin/apps' => 'Apps',
            "/admin/apps/{$installation->id}" => 'Route App',
            '/admin/developers' => 'Developers',
            '/admin/analytics' => 'Analytics',
            '/admin/search/settings' => 'Search Settings',
        ];

        foreach ($pages as $url => $heading) {
            $this->get($url)->assertOk()->assertSee($heading);
        }
    });

    it('enforces tenant binding before resolving admin route models', function () {
        $context = createStoreContext();
        actingAsAdmin($context['user'], $context['store']);
        $foreign = createStoreContext();
        $product = Product::factory()->for($foreign['store'])->create();
        $order = Order::factory()->for($foreign['store'])->create(['customer_id' => null]);
        $customer = Customer::factory()->for($foreign['store'])->create();
        $theme = Theme::factory()->for($foreign['store'])->create();

        session(['current_store_id' => $context['store']->id]);
        bindStore($context['store']);

        $this->get("/admin/products/{$product->id}/edit")->assertNotFound();
        $this->get("/admin/orders/{$order->id}")->assertNotFound();
        $this->get("/admin/customers/{$customer->id}")->assertNotFound();
        $this->get("/admin/themes/{$theme->id}/editor")->assertNotFound();
    });

    it('gives staff operational access but keeps owner settings private', function () {
        $context = createStoreContext('staff');
        actingAsAdmin($context['user'], $context['store']);

        foreach (['/admin', '/admin/products', '/admin/products/create', '/admin/inventory', '/admin/orders', '/admin/customers', '/admin/pages', '/admin/analytics'] as $url) {
            $this->get($url)->assertOk();
        }

        foreach (['/admin/settings', '/admin/settings/shipping', '/admin/settings/taxes', '/admin/themes', '/admin/navigation', '/admin/apps', '/admin/developers', '/admin/search/settings'] as $url) {
            $this->get($url)->assertForbidden();
        }
    });

    it('keeps support read only and denies privileged reporting and configuration', function () {
        $context = createStoreContext('support');
        actingAsAdmin($context['user'], $context['store']);
        $product = Product::factory()->for($context['store'])->create(['title' => 'Support Product']);
        $customer = Customer::factory()->for($context['store'])->create();
        $order = Order::factory()->for($context['store'])->create(['customer_id' => $customer->id]);

        $this->get('/admin/products')->assertOk();
        $this->get("/admin/products/{$product->id}/edit")
            ->assertOk()
            ->assertSee('disabled', false)
            ->assertDontSee('Save changes');
        $this->get("/admin/orders/{$order->id}")
            ->assertOk()
            ->assertDontSee('wire:click="openFulfillmentModal"', false)
            ->assertDontSee('wire:click="openRefundModal"', false);
        $this->get("/admin/customers/{$customer->id}")
            ->assertOk()
            ->assertDontSee('wire:click="openAddressForm"', false);

        foreach (['/admin/products/create', '/admin/pages', '/admin/analytics', '/admin/settings', '/admin/themes', '/admin/navigation', '/admin/apps', '/admin/developers', '/admin/search/settings'] as $url) {
            $this->get($url)->assertForbidden();
        }

        $this->get('/admin')->assertForbidden();
    });
});

describe('admin catalog workflows', function () {
    it('creates a published product matrix with collections and inventory', function () {
        $context = createStoreContext();
        actingAsAdmin($context['user'], $context['store']);
        $collection = Collection::factory()->for($context['store'])->create();

        Livewire::test(ProductForm::class)
            ->set('title', 'Trail Runner')
            ->set('handle', 'trail-runner')
            ->set('descriptionHtml', '<p>Built for <strong>long days</strong>.</p>')
            ->set('status', 'active')
            ->set('vendor', 'Northwind')
            ->set('productType', 'Shoes')
            ->set('tags', 'running, outdoor')
            ->set('collectionIds', [$collection->id])
            ->set('options', [
                ['name' => 'Size', 'values' => ['S', 'M']],
                ['name' => 'Color', 'values' => ['Black', 'Blue']],
            ])
            ->set('variants', [
                ['title' => 'S / Black', 'sku' => 'TR-S-BLK', 'price' => 7900, 'compareAtPrice' => 9900, 'quantity' => 5, 'requiresShipping' => true],
                ['title' => 'S / Blue', 'sku' => 'TR-S-BLU', 'price' => 7900, 'compareAtPrice' => null, 'quantity' => 6, 'requiresShipping' => true],
                ['title' => 'M / Black', 'sku' => 'TR-M-BLK', 'price' => 8100, 'compareAtPrice' => null, 'quantity' => 7, 'requiresShipping' => true],
                ['title' => 'M / Blue', 'sku' => 'TR-M-BLU', 'price' => 8100, 'compareAtPrice' => null, 'quantity' => 8, 'requiresShipping' => true],
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $product = Product::query()->where('handle', 'trail-runner')->firstOrFail();

        expect($product->status->value)->toBe('active')
            ->and($product->published_at)->not->toBeNull()
            ->and($product->options()->count())->toBe(2)
            ->and($product->variants()->count())->toBe(4)
            ->and($product->collections()->pluck('collections.id')->all())->toBe([$collection->id])
            ->and($product->variants()->orderBy('position')->pluck('sku')->all())->toBe([
                'TR-S-BLK', 'TR-S-BLU', 'TR-M-BLK', 'TR-M-BLU',
            ]);

        expect(InventoryItem::query()
            ->whereIn('variant_id', $product->variants()->pluck('id'))
            ->orderBy('quantity_on_hand')
            ->pluck('quantity_on_hand')->all())->toBe([5, 6, 7, 8]);
    });

    it('creates an ordered manual collection and a targeted discount', function () {
        $context = createStoreContext();
        actingAsAdmin($context['user'], $context['store']);
        $first = Product::factory()->for($context['store'])->create(['title' => 'First']);
        $second = Product::factory()->for($context['store'])->create(['title' => 'Second']);

        Livewire::test(CollectionForm::class)
            ->set('title', 'Summer Edit')
            ->set('handle', 'summer-edit')
            ->set('status', 'active')
            ->set('assignedProductIds', [$second->id, $first->id])
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $collection = Collection::query()->where('handle', 'summer-edit')->firstOrFail();

        expect($collection->products()->orderByPivot('position')->pluck('products.id')->all())
            ->toBe([$second->id, $first->id]);

        Livewire::test(DiscountForm::class)
            ->set('type', 'code')
            ->set('code', 'summer25')
            ->set('valueType', 'percent')
            ->set('valueAmount', 25)
            ->set('minimumPurchaseAmount', 5000)
            ->set('specificProductIds', [$first->id])
            ->set('specificCollectionIds', [$collection->id])
            ->set('usageLimit', 50)
            ->set('onePerCustomer', true)
            ->set('startsAt', now()->subHour()->format('Y-m-d\TH:i'))
            ->set('endsAt', now()->addMonth()->format('Y-m-d\TH:i'))
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $discount = Discount::query()->where('code', 'SUMMER25')->firstOrFail();

        expect($discount->value_amount)->toBe(25)
            ->and($discount->usage_limit)->toBe(50)
            ->and(data_get($discount->rules_json, 'min_purchase_amount'))->toBe(5000)
            ->and(data_get($discount->rules_json, 'applicable_product_ids'))->toBe([$first->id])
            ->and(data_get($discount->rules_json, 'applicable_collection_ids'))->toBe([$collection->id])
            ->and(data_get($discount->rules_json, 'one_per_customer'))->toBeTrue();
    });

    it('edits inventory through the admin component and preserves reservations', function () {
        $context = createStoreContext();
        actingAsAdmin($context['user'], $context['store']);
        $sku = makeSellableVariant($context['store'], inventoryAttributes: [
            'quantity_on_hand' => 12,
            'quantity_reserved' => 3,
            'policy' => 'deny',
        ]);

        Livewire::test(InventoryIndex::class)
            ->call('updateQuantity', $sku['inventory']->id, 25)
            ->call('updatePolicy', $sku['inventory']->id, 'continue')
            ->assertDispatched('toast');

        expect($sku['inventory']->refresh())
            ->quantity_on_hand->toBe(25)
            ->quantity_reserved->toBe(3)
            ->and($sku['inventory']->policy->value)->toBe('continue');
    });

    it('authorizes and queues a valid direct product media upload', function () {
        Storage::fake('public');
        Queue::fake();
        $context = createStoreContext();
        actingAsAdmin($context['user'], $context['store']);
        $product = Product::factory()->for($context['store'])->create(['title' => 'Upload Product']);

        Livewire::test(ProductForm::class, ['product' => $product])
            ->set('newMedia', [UploadedFile::fake()->image('product.png', 800, 600)->size(200)])
            ->call('uploadMedia')
            ->assertHasNoErrors();

        $media = $product->media()->firstOrFail();
        expect($media->alt_text)->toBe('Upload Product')
            ->and($media->mime_type)->toBe('image/png')
            ->and($media->status->value)->toBe('processing');
        Storage::disk('public')->assertExists($media->storage_key);
        Queue::assertPushed(ProcessMediaUpload::class, fn (ProcessMediaUpload $job): bool => $job->media->is($media));
    });

    it('rejects unauthorized and invalid direct product media uploads', function () {
        Storage::fake('public');
        Queue::fake();
        $support = createStoreContext('support');
        actingAsAdmin($support['user'], $support['store']);
        $product = Product::factory()->for($support['store'])->create();

        Livewire::test(ProductForm::class, ['product' => $product])
            ->set('newMedia', [UploadedFile::fake()->image('forbidden.png')])
            ->call('uploadMedia')
            ->assertForbidden();

        expect($product->media()->count())->toBe(0);
        Queue::assertNothingPushed();

        $owner = createStoreContext();
        actingAsAdmin($owner['user'], $owner['store']);
        $ownerProduct = Product::factory()->for($owner['store'])->create();

        Livewire::test(ProductForm::class, ['product' => $ownerProduct])
            ->set('newMedia', [UploadedFile::fake()->create('payload.php', 20, 'application/x-php')])
            ->call('uploadMedia')
            ->assertHasErrors(['newMedia.0']);

        Livewire::test(ProductForm::class, ['product' => $ownerProduct])
            ->set('newMedia', [UploadedFile::fake()->image('oversized.jpg')->size(5121)])
            ->call('uploadMedia')
            ->assertHasErrors(['newMedia.0']);

        expect($ownerProduct->media()->count())->toBe(0);
        Queue::assertNothingPushed();
    });
});

describe('admin customer and order workflows', function () {
    it('creates edits defaults and deletes customer addresses', function () {
        $context = createStoreContext();
        actingAsAdmin($context['user'], $context['store']);
        $customer = Customer::factory()->for($context['store'])->create();

        $component = Livewire::test(CustomerShow::class, ['customer' => $customer])
            ->call('openAddressForm')
            ->set('addressLabel', 'Home')
            ->set('addressJson', [
                'first_name' => 'Ada', 'last_name' => 'Lovelace', 'company' => '',
                'address1' => 'Example Street 1', 'address2' => '', 'city' => 'Berlin',
                'province' => 'Berlin', 'province_code' => 'be', 'country' => 'Germany',
                'country_code' => 'de', 'zip' => '10115', 'phone' => '+49 30 1234',
            ])
            ->set('addressIsDefault', true)
            ->call('saveAddress')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $home = $customer->addresses()->firstOrFail();
        expect($home->is_default)->toBeTrue()
            ->and(data_get($home->address_json, 'country_code'))->toBe('DE')
            ->and(data_get($home->address_json, 'province_code'))->toBe('BE');

        $work = $customer->addresses()->create([
            'label' => 'Work',
            'address_json' => $home->address_json,
            'is_default' => false,
        ]);

        $component->call('setDefaultAddress', $work->id)->assertDispatched('toast');
        expect($work->refresh()->is_default)->toBeTrue()
            ->and($home->refresh()->is_default)->toBeFalse();

        $component->call('deleteAddress', $work->id)->assertDispatched('toast');
        expect($work->fresh())->toBeNull()
            ->and($home->refresh()->is_default)->toBeTrue();
    });

    it('fulfills ships delivers and refunds an order through the detail component', function () {
        $fixture = paidOrderFixture(quantity: 2);
        $owner = $fixture['store']->users()->firstOrFail();
        actingAsAdmin($owner, $fixture['store']);

        $component = Livewire::test(OrderShow::class, ['order' => $fixture['order']])
            ->set('fulfillmentLines', [$fixture['line']->id => 2])
            ->set('trackingCompany', 'DHL')
            ->set('trackingNumber', 'TRACK-100')
            ->call('createFulfillment')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $fulfillment = $fixture['order']->fulfillments()->firstOrFail();
        $component->call('markAsShipped', $fulfillment->id)
            ->call('markAsDelivered', $fulfillment->id)
            ->assertDispatched('toast');

        expect($fulfillment->refresh()->status->value)->toBe('delivered')
            ->and($fixture['order']->refresh()->fulfillment_status->value)->toBe('fulfilled');

        $component->set('refundAmount', 2500)
            ->set('refundReason', 'Customer return')
            ->set('refundLines', [$fixture['line']->id => 1])
            ->call('createRefund')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        expect($fixture['order']->refunds()->count())->toBe(1)
            ->and($fixture['payment']->refunds()->where('status', 'processed')->sum('amount'))->toBe(2500);
    });

    it('confirms a pending bank transfer through the order detail component', function () {
        $context = createStoreContext();
        actingAsAdmin($context['user'], $context['store']);
        $order = Order::factory()->for($context['store'])->create([
            'customer_id' => null,
            'payment_method' => 'bank_transfer',
            'status' => 'pending',
            'financial_status' => 'pending',
        ]);
        Payment::factory()->for($order)->create([
            'method' => 'bank_transfer',
            'status' => 'pending',
            'amount' => $order->total_amount,
        ]);

        Livewire::test(OrderShow::class, ['order' => $order])
            ->call('confirmPayment')
            ->assertDispatched('toast');

        expect($order->refresh()->status->value)->toBe('paid')
            ->and($order->financial_status->value)->toBe('paid')
            ->and($order->payments()->firstOrFail()->status->value)->toBe('captured');
    });
});

describe('admin settings workflows', function () {
    it('updates store settings and safely manages tenant domains', function () {
        $context = createStoreContext();
        actingAsAdmin($context['user'], $context['store']);

        $component = Livewire::test(SettingsIndex::class)
            ->set('storeName', 'Northwind Outdoor')
            ->set('defaultCurrency', 'CHF')
            ->set('defaultLocale', 'de')
            ->set('timezone', 'Europe/Zurich')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast')
            ->set('newHostname', 'admin.northwind.test')
            ->set('newType', 'admin')
            ->call('addDomain')
            ->assertHasNoErrors();

        expect($context['store']->refresh())
            ->name->toBe('Northwind Outdoor')
            ->default_currency->toBe('CHF')
            ->default_locale->toBe('de')
            ->timezone->toBe('Europe/Zurich');

        $domain = StoreDomain::withoutGlobalScopes()->where('hostname', 'admin.northwind.test')->firstOrFail();
        expect($domain->store_id)->toBe($context['store']->id)
            ->and($domain->type->value)->toBe('admin')
            ->and($domain->is_primary)->toBeTrue();

        $component->call('removeDomain', $domain->id)->assertStatus(422);
    });

    it('creates shipping zones and rates and tests address matching', function () {
        $context = createStoreContext();
        actingAsAdmin($context['user'], $context['store']);

        $component = Livewire::test(ShippingSettings::class)
            ->call('openZoneModal')
            ->set('zoneName', 'DACH')
            ->set('zoneCountries', ['DE', 'AT', 'CH'])
            ->call('saveZone')
            ->assertHasNoErrors();

        $zone = ShippingZone::query()->where('name', 'DACH')->firstOrFail();

        $component->call('openRateModal', $zone->id)
            ->set('rateName', 'Tracked parcel')
            ->set('rateType', 'flat')
            ->set('rateAmount', '6.90')
            ->set('rateActive', true)
            ->call('saveRate')
            ->assertHasNoErrors()
            ->set('testAddress', ['country' => 'DE', 'province' => 'BE', 'city' => 'Berlin', 'postal_code' => '10115'])
            ->call('testShippingAddress')
            ->assertSet('testResult.zone', 'DACH')
            ->assertSet('testResult.rates.0.amount', 690);

        expect(ShippingRate::query()->where('zone_id', $zone->id)->firstOrFail())
            ->name->toBe('Tracked parcel')
            ->and(data_get(ShippingRate::query()->where('zone_id', $zone->id)->firstOrFail()->config_json, 'amount'))->toBe(690);
    });

    it('persists manual and provider tax settings with validation', function () {
        $context = createStoreContext();
        actingAsAdmin($context['user'], $context['store']);

        Livewire::test(TaxSettings::class)
            ->set('mode', 'manual')
            ->set('pricesIncludeTax', true)
            ->set('provider', 'none')
            ->set('manualRates', [
                ['zone_name' => 'Germany', 'rate_percentage' => '19'],
                ['zone_name' => 'Reduced', 'rate_percentage' => '7'],
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $settings = TaxSettingsModel::withoutGlobalScopes()->where('store_id', $context['store']->id)->firstOrFail();
        expect($settings->mode->value)->toBe('manual')
            ->and($settings->prices_include_tax)->toBeTrue()
            ->and(data_get($settings->config_json, 'manual_rates'))->toBe([
                ['zone_name' => 'Germany', 'rate_percentage' => 19],
                ['zone_name' => 'Reduced', 'rate_percentage' => 7],
            ]);
    });
});

describe('admin role boundaries in Livewire', function () {
    it('allows support to inspect products orders and customers but blocks mutations', function () {
        $context = createStoreContext('support');
        actingAsAdmin($context['user'], $context['store']);
        $sku = makeSellableVariant($context['store']);
        $customer = Customer::factory()->for($context['store'])->create();
        $order = Order::factory()->for($context['store'])->create(['customer_id' => $customer->id]);

        Livewire::test(InventoryIndex::class)->assertOk();
        Livewire::test(OrderShow::class, ['order' => $order])->assertOk();
        Livewire::test(CustomerShow::class, ['customer' => $customer])->assertOk();

        Livewire::test(InventoryIndex::class)
            ->call('updateQuantity', $sku['inventory']->id, 999)
            ->assertForbidden();

        expect($sku['inventory']->refresh()->quantity_on_hand)->toBe(20);
    });

    it('blocks staff from owner-only settings', function () {
        $context = createStoreContext('staff');
        actingAsAdmin($context['user'], $context['store']);

        Livewire::test(SettingsIndex::class)->assertForbidden();
        Livewire::test(ShippingSettings::class)->assertForbidden();
        Livewire::test(TaxSettings::class)->assertForbidden();
    });
});
