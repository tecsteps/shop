<?php

use App\Models\Collection;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/** @param list<string> $abilities */
function adminApiToken(array $context, array $abilities): string
{
    $abilities[] = 'store:'.$context['store']->id;

    return $context['user']->createToken('api-test', array_values(array_unique($abilities)))->plainTextToken;
}

function adminStoreUrl(Store $store, string $path): string
{
    return "/api/admin/v1/stores/{$store->id}/".ltrim($path, '/');
}

function themeArchive(bool $includeTemplate = true): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'shop-theme-');
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::OVERWRITE);
    $zip->addFromString('theme.json', json_encode([
        'name' => 'Archive Theme',
        'version' => '1.2.3',
        'required_templates' => ['templates/index.blade.php'],
        'settings' => ['colors' => ['primary' => '#123456']],
    ], JSON_THROW_ON_ERROR));
    if ($includeTemplate) {
        $zip->addFromString('templates/index.blade.php', '<main>{{ $slot ?? "Theme" }}</main>');
    } else {
        $zip->addFromString('assets/theme.css', 'body { color: #123456; }');
    }
    $zip->close();

    return new UploadedFile($path, 'theme.zip', 'application/zip', null, true);
}

describe('admin product API', function () {
    it('lists filters paginates creates shows updates and archives products', function () {
        $context = createStoreContext();
        $token = adminApiToken($context, ['read-products', 'write-products']);
        Product::factory()->count(26)->for($context['store'])->create();
        $other = Store::factory()->create();
        Product::factory()->count(3)->for($other)->create();

        $this->withToken($token)
            ->getJson(adminStoreUrl($context['store'], 'products'))
            ->assertOk()
            ->assertJsonCount(25, 'data')
            ->assertJsonPath('meta.total', 26)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2);

        $created = $this->withToken($token)->postJson(adminStoreUrl($context['store'], 'products'), [
            'title' => 'API Product',
            'description_html' => '<p>Created through the API.</p>',
            'vendor' => 'API Vendor',
            'tags' => ['api', 'new'],
            'variant' => [
                'sku' => 'API-SKU-1',
                'price_amount' => 4500,
                'quantity_on_hand' => 12,
            ],
        ])->assertCreated()
            ->assertJsonPath('data.title', 'API Product')
            ->assertJsonPath('data.handle', 'api-product')
            ->assertJsonPath('data.variants.0.sku', 'API-SKU-1')
            ->assertJsonPath('data.variants.0.price_amount', 4500)
            ->assertJsonPath('data.variants.0.inventory_item.quantity_on_hand', 12);
        $productId = $created->json('data.id');

        $this->withToken($token)->getJson(adminStoreUrl($context['store'], "products/{$productId}"))
            ->assertOk()
            ->assertJsonPath('data.variants.0.inventory_item.quantity_on_hand', 12);
        $this->withToken($token)->putJson(adminStoreUrl($context['store'], "products/{$productId}"), [
            'title' => 'Updated API Product',
            'status' => 'active',
        ])->assertOk()
            ->assertJsonPath('data.title', 'Updated API Product')
            ->assertJsonPath('data.handle', 'updated-api-product')
            ->assertJsonPath('data.status', 'active');
        $this->withToken($token)->getJson(adminStoreUrl($context['store'], 'products?status=active&query=Updated'))
            ->assertOk()->assertJsonPath('meta.total', 1);
        $this->withToken($token)->deleteJson(adminStoreUrl($context['store'], "products/{$productId}"))
            ->assertOk()->assertJsonPath('data.status', 'archived');
        $this->withToken($token)->getJson(adminStoreUrl($other, "products/{$productId}"))->assertForbidden();
    });

    it('accepts the documented variants array and nested inventory contract', function () {
        $context = createStoreContext();
        $token = adminApiToken($context, ['write-products']);

        $this->withToken($token)->postJson(adminStoreUrl($context['store'], 'products'), [
            'title' => 'Documented Variant Product',
            'variants' => [[
                'sku' => 'DOC-SKU-1',
                'price_amount' => 3200,
                'currency' => 'EUR',
                'requires_shipping' => true,
                'is_default' => true,
                'inventory' => ['quantity_on_hand' => 17, 'policy' => 'deny'],
            ]],
        ])->assertCreated()
            ->assertJsonPath('data.variants.0.sku', 'DOC-SKU-1')
            ->assertJsonPath('data.variants.0.price_amount', 3200)
            ->assertJsonPath('data.variants.0.inventory_item.quantity_on_hand', 17);
    });

    it('requires authentication abilities and the store role for mutations', function () {
        $context = createStoreContext();
        $this->getJson(adminStoreUrl($context['store'], 'products'))->assertUnauthorized();

        $reader = adminApiToken($context, ['read-products']);
        $this->withToken($reader)->postJson(adminStoreUrl($context['store'], 'products'), ['title' => 'Forbidden'])
            ->assertForbidden();

        $support = createStoreContext('support');
        $supportToken = adminApiToken($support, ['write-products']);
        app('auth')->forgetGuards();
        $this->withToken($supportToken)->postJson(adminStoreUrl($support['store'], 'products'), [
            'title' => 'Role Escalation',
            'variant' => ['price_amount' => 1000],
        ])->assertForbidden();
    });

    it('rejects a token against another store even when its user is a member there', function () {
        $issuing = createStoreContext();
        $other = createStoreContext();
        DB::table('store_users')->insert([
            'store_id' => $other['store']->id,
            'user_id' => $issuing['user']->id,
            'role' => 'admin',
            'created_at' => now(),
        ]);
        $token = adminApiToken($issuing, ['read-products', 'write-products']);

        app('auth')->forgetGuards();
        $this->withToken($token)
            ->getJson(adminStoreUrl($other['store'], 'products'))
            ->assertForbidden();

        app('auth')->forgetGuards();
        $this->withToken($token)
            ->postJson(adminStoreUrl($other['store'], 'products'), [
                'title' => 'Cross-store escalation',
                'variant' => ['price_amount' => 1000],
            ])
            ->assertForbidden();

        expect(Product::withoutGlobalScopes()->where('store_id', $other['store']->id)->count())->toBe(0);
    });

    it('returns tenant-safe validation for duplicate handles and SKUs', function () {
        $context = createStoreContext();
        $token = adminApiToken($context, ['write-products']);
        $first = $this->withToken($token)->postJson(adminStoreUrl($context['store'], 'products'), [
            'title' => 'First Product',
            'handle' => 'duplicate-handle',
            'variant' => ['sku' => 'DUP-SKU', 'price_amount' => 1000],
        ])->assertCreated();

        $this->withToken($token)->postJson(adminStoreUrl($context['store'], 'products'), [
            'title' => 'Duplicate Handle',
            'handle' => 'duplicate-handle',
            'variant' => ['sku' => 'OTHER-SKU', 'price_amount' => 1000],
        ])->assertUnprocessable()->assertJsonValidationErrors('handle');
        $this->withToken($token)->postJson(adminStoreUrl($context['store'], 'products'), [
            'title' => 'Duplicate SKU',
            'variant' => ['sku' => 'DUP-SKU', 'price_amount' => 1000],
        ])->assertUnprocessable()->assertJsonValidationErrors('variant.sku');
    });
});

describe('admin collection discount settings and content APIs', function () {
    it('manages ordered collections and discounts inside the route tenant', function () {
        $context = createStoreContext();
        $token = adminApiToken($context, [
            'read-collections', 'write-collections', 'read-discounts', 'write-discounts',
        ]);
        $products = Product::factory()->count(3)->for($context['store'])->create();

        $collection = $this->withToken($token)->postJson(adminStoreUrl($context['store'], 'collections'), [
            'title' => 'API Collection',
            'status' => 'active',
            'product_ids' => $products->modelKeys(),
        ])->assertCreated()
            ->assertJsonPath('data.handle', 'api-collection')
            ->assertJsonCount(3, 'data.products');
        $collectionId = $collection->json('data.id');
        expect(Collection::query()->findOrFail($collectionId)->products->pluck('pivot.position')->all())->toBe([0, 1, 2]);

        $this->withToken($token)->putJson(adminStoreUrl($context['store'], "collections/{$collectionId}"), [
            'title' => 'Updated Collection',
            'product_ids' => [$products[2]->id, $products[0]->id],
        ])->assertOk()->assertJsonCount(2, 'data.products');
        expect(Collection::query()->findOrFail($collectionId)->products->modelKeys())->toBe([$products[2]->id, $products[0]->id]);

        $discount = $this->withToken($token)->postJson(adminStoreUrl($context['store'], 'discounts'), [
            'type' => 'code',
            'code' => 'API20',
            'value_type' => 'percent',
            'value_amount' => 20,
            'starts_at' => now()->subDay()->toIso8601String(),
            'ends_at' => now()->addMonth()->toIso8601String(),
            'status' => 'active',
        ])->assertCreated()->assertJsonPath('data.value_amount', 20);
        $discountId = $discount->json('data.id');

        $this->withToken($token)->postJson(adminStoreUrl($context['store'], 'discounts'), [
            'type' => 'code', 'code' => 'API20', 'value_type' => 'fixed', 'value_amount' => 500,
            'starts_at' => now()->toIso8601String(),
        ])->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->withToken($token)->putJson(adminStoreUrl($context['store'], "discounts/{$discountId}"), [
            'value_amount' => 25, 'status' => 'disabled',
        ])->assertOk()->assertJsonPath('data.status', 'disabled');
        $this->withToken($token)->deleteJson(adminStoreUrl($context['store'], "discounts/{$discountId}"))->assertOk();
        $this->withToken($token)->deleteJson(adminStoreUrl($context['store'], "collections/{$collectionId}"))->assertOk();
    });

    it('configures shipping tax pages themes and search indexing', function () {
        $context = createStoreContext();
        $token = adminApiToken($context, [
            'read-settings', 'write-settings', 'read-content', 'write-content', 'write-themes',
        ]);

        $zone = $this->withToken($token)->postJson(adminStoreUrl($context['store'], 'shipping/zones'), [
            'name' => 'Domestic', 'countries_json' => ['DE'], 'regions_json' => ['BE'],
        ])->assertCreated()->assertJsonPath('data.name', 'Domestic');
        $zoneId = $zone->json('data.id');
        $this->withToken($token)->postJson(adminStoreUrl($context['store'], "shipping/zones/{$zoneId}/rates"), [
            'name' => 'Standard', 'type' => 'flat', 'config_json' => ['amount' => 499], 'is_active' => true,
        ])->assertCreated()->assertJsonPath('data.config_json.amount', 499);
        $this->withToken($token)->getJson(adminStoreUrl($context['store'], 'shipping/zones'))
            ->assertOk()->assertJsonPath('data.0.rates.0.name', 'Standard');

        $this->withToken($token)->putJson(adminStoreUrl($context['store'], 'tax/settings'), [
            'mode' => 'manual', 'provider' => 'none', 'prices_include_tax' => true,
            'config_json' => ['rate_bps' => 1900, 'label' => 'VAT'],
        ])->assertOk()->assertJsonPath('data.prices_include_tax', true);
        $this->withToken($token)->getJson(adminStoreUrl($context['store'], 'tax/settings'))
            ->assertOk()->assertJsonPath('data.config_json.rate_bps', 1900);

        $page = $this->withToken($token)->postJson(adminStoreUrl($context['store'], 'pages'), [
            'title' => 'Shipping FAQ', 'body_html' => '<p>Answers.</p>', 'status' => 'published', 'published_at' => now(),
        ])->assertCreated()->assertJsonPath('data.handle', 'shipping-faq');
        $pageId = $page->json('data.id');
        $this->withToken($token)->putJson(adminStoreUrl($context['store'], "pages/{$pageId}"), ['title' => 'Delivery FAQ'])
            ->assertOk()->assertJsonPath('data.title', 'Delivery FAQ');

        $theme = $this->withToken($token)->post(adminStoreUrl($context['store'], 'themes'), [
            'name' => 'API Theme',
            'file' => themeArchive(),
        ], ['Accept' => 'application/json'])->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.version', '1.2.3')
            ->assertJsonCount(2, 'data.files');
        $themeId = $theme->json('data.id');
        $this->withToken($token)->post(adminStoreUrl($context['store'], 'themes'), [
            'file' => themeArchive(false),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('file');
        $this->withToken($token)->putJson(adminStoreUrl($context['store'], "themes/{$themeId}/settings"), [
            'settings' => ['colors' => ['primary' => '#123456']],
        ])->assertOk()->assertJsonPath('data.settings_json.colors.primary', '#123456');
        $this->withToken($token)->postJson(adminStoreUrl($context['store'], "themes/{$themeId}/publish"))
            ->assertOk()->assertJsonPath('data.status', 'published');

        Product::factory()->for($context['store'])->create(['title' => 'Index Me', 'handle' => 'index-me']);
        $this->withToken($token)->postJson(adminStoreUrl($context['store'], 'search/reindex'))->assertStatus(202);
        $this->withToken($token)->getJson(adminStoreUrl($context['store'], 'search/status'))
            ->assertOk()->assertJsonPath('data.index_status', 'ready')->assertJsonPath('data.documents_count', 1);
        $this->withToken($token)->deleteJson(adminStoreUrl($context['store'], "pages/{$pageId}"))->assertOk();
    });
});

describe('admin order analytics and platform APIs', function () {
    it('lists shows fulfills refunds and confirms orders', function () {
        $fixture = paidOrderFixture();
        $context = [
            'store' => $fixture['store'],
            'user' => $fixture['store']->users()->firstOrFail(),
        ];
        $token = adminApiToken($context, ['read-orders', 'write-orders']);

        $this->withToken($token)->getJson(adminStoreUrl($fixture['store'], 'orders?status=paid'))
            ->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', $fixture['order']->id);
        $this->withToken($token)->getJson(adminStoreUrl($fixture['store'], "orders/{$fixture['order']->id}"))
            ->assertOk()
            ->assertJsonPath('data.lines.0.id', $fixture['line']->id)
            ->assertJsonPath('data.payments.0.id', $fixture['payment']->id);
        $this->withToken($token)->postJson(adminStoreUrl($fixture['store'], "orders/{$fixture['order']->id}/fulfillments"), [
            'lines' => [$fixture['line']->id => 1],
            'tracking_company' => 'DHL',
            'tracking_number' => 'TRACK-1',
            'tracking_url' => 'https://tracking.example/TRACK-1',
        ])->assertCreated()->assertJsonPath('data.lines.0.quantity', 1);

        $refundFixture = paidOrderFixture();
        // Add the first token owner to the second fixture store so the same endpoint contract is exercised.
        DB::table('store_users')->insert([
            'store_id' => $refundFixture['store']->id, 'user_id' => $context['user']->id, 'role' => 'admin', 'created_at' => now(),
        ]);
        $refundToken = adminApiToken(['store' => $refundFixture['store'], 'user' => $context['user']], ['write-orders']);
        app('auth')->forgetGuards();
        $this->withToken($refundToken)->postJson(adminStoreUrl($refundFixture['store'], "orders/{$refundFixture['order']->id}/refunds"), [
            'amount' => 2000, 'reason' => 'API return', 'restock' => true,
            'lines' => [$refundFixture['line']->id => 1],
        ])->assertCreated()->assertJsonPath('data.amount', 2000)->assertJsonPath('data.status', 'processed');

        $bank = paidOrderFixture();
        DB::table('store_users')->insert([
            'store_id' => $bank['store']->id, 'user_id' => $context['user']->id, 'role' => 'admin', 'created_at' => now(),
        ]);
        $bank['order']->update(['payment_method' => 'bank_transfer', 'status' => 'pending', 'financial_status' => 'pending']);
        $bank['payment']->update(['method' => 'bank_transfer', 'status' => 'pending']);
        $bank['variant']->inventoryItem->update(['quantity_on_hand' => 10, 'quantity_reserved' => 2]);
        $bankToken = adminApiToken(['store' => $bank['store'], 'user' => $context['user']], ['write-orders']);
        app('auth')->forgetGuards();
        $this->withToken($bankToken)->postJson(adminStoreUrl($bank['store'], "orders/{$bank['order']->id}/confirm-payment"))
            ->assertOk()->assertJsonPath('data.financial_status', 'paid');
        expect($bank['variant']->inventoryItem->refresh()->quantity_on_hand)->toBe(8)
            ->and($bank['variant']->inventoryItem->quantity_reserved)->toBe(0);
    });

    it('rejects read-only order mutations and over-refunds as API errors', function () {
        $fixture = paidOrderFixture();
        $context = ['store' => $fixture['store'], 'user' => $fixture['store']->users()->firstOrFail()];
        $reader = adminApiToken($context, ['read-orders']);

        $this->withToken($reader)->postJson(adminStoreUrl($fixture['store'], "orders/{$fixture['order']->id}/refunds"), ['amount' => 100])
            ->assertForbidden();

        $writerUser = User::factory()->create();
        DB::table('store_users')->insert([
            'store_id' => $fixture['store']->id, 'user_id' => $writerUser->id, 'role' => 'admin', 'created_at' => now(),
        ]);
        $writer = $writerUser->createToken('writer', [
            'write-orders',
            'store:'.$fixture['store']->id,
        ])->plainTextToken;
        app('auth')->forgetGuards();
        $this->withToken($writer)->postJson(adminStoreUrl($fixture['store'], "orders/{$fixture['order']->id}/refunds"), ['amount' => 999999])
            ->assertUnprocessable()->assertJsonValidationErrors('amount');
    });

    it('summarizes analytics and streams a tenant-only order CSV', function () {
        $context = createStoreContext();
        $token = adminApiToken($context, ['read-analytics', 'read-orders']);
        DB::table('analytics_daily')->insert([
            [
                'store_id' => $context['store']->id, 'date' => '2026-07-10', 'orders_count' => 2,
                'revenue_amount' => 5000, 'aov_amount' => 2500, 'visits_count' => 10,
                'add_to_cart_count' => 4, 'checkout_started_count' => 3, 'checkout_completed_count' => 2,
            ],
            [
                'store_id' => $context['store']->id, 'date' => '2026-07-11', 'orders_count' => 1,
                'revenue_amount' => 4000, 'aov_amount' => 4000, 'visits_count' => 8,
                'add_to_cart_count' => 3, 'checkout_started_count' => 2, 'checkout_completed_count' => 1,
            ],
        ]);
        Order::factory()->for($context['store'])->create(['customer_id' => null, 'order_number' => '#CSV1001']);

        $this->withToken($token)->getJson(adminStoreUrl($context['store'], 'analytics/summary?from=2026-07-10&to=2026-07-11'))
            ->assertOk()
            ->assertJsonPath('data.revenue_amount', 9000)
            ->assertJsonPath('data.orders_count', 3)
            ->assertJsonPath('data.visits_count', 18)
            ->assertJsonCount(2, 'data.series');

        $queued = $this->withToken($token)->postJson(adminStoreUrl($context['store'], 'exports/orders'), ['format' => 'csv'])
            ->assertStatus(202)
            ->assertJsonPath('status', 'queued');
        $exportId = $queued->json('export_id');
        $ready = $this->withToken($token)->getJson(adminStoreUrl($context['store'], "exports/{$exportId}"))
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.row_count', 1);
        $downloadUrl = $ready->json('data.download_url');
        expect($downloadUrl)->toBeString()->not->toBeEmpty();
        $download = $this->get($downloadUrl);
        $download->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        expect($download->streamedContent())->toContain('order_number,created_at,status,financial_status')
            ->and($download->streamedContent())->toContain('#CSV1001');
    });

    it('creates platform records invites members and reports token permissions', function () {
        $context = createStoreContext();
        $token = adminApiToken($context, ['manage-platform']);

        $organization = $this->withToken($token)->postJson('/api/admin/v1/platform/organizations', [
            'name' => 'API Organization', 'billing_email' => 'billing@api.example',
        ])->assertCreated()->json('data');
        $newStore = $this->withToken($token)->postJson('/api/admin/v1/platform/stores', [
            'organization_id' => $organization['id'],
            'name' => 'API Store',
            'handle' => 'api-store',
            'default_currency' => 'EUR',
            'default_locale' => 'en',
            'timezone' => 'Europe/Berlin',
        ])->assertCreated()->assertJsonPath('data.status', 'active');

        $this->withToken($token)->postJson(adminStoreUrl($context['store'], 'invites'), [
            'email' => 'new-staff@example.test', 'role' => 'staff',
        ])->assertCreated()->assertJsonPath('data.role', 'staff');
        $this->withToken($token)->postJson(adminStoreUrl($context['store'], 'invites'), [
            'email' => 'new-staff@example.test', 'role' => 'staff',
        ])->assertConflict();
        $this->withToken($token)->getJson(adminStoreUrl($context['store'], 'me'))
            ->assertOk()
            ->assertJsonPath('data.role', 'owner')
            ->assertJsonPath('data.permissions.0', 'manage-platform');
    });
});
