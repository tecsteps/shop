<?php

use App\Models\Page;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function storeConfigurationApiToken(User $user, Store $store, array $abilities): string
{
    $plainTextToken = 'shop_'.Str::random(64);
    $user->tokens()->create([
        'store_id' => $store->getKey(),
        'name' => 'store-configuration-api-test',
        'token' => hash('sha256', $plainTextToken),
        'abilities' => $abilities,
        'expires_at' => now()->addHour(),
    ]);

    return $plainTextToken;
}

/** @param array<string, mixed> $manifest
 * @return array{file: UploadedFile, path: string}
 */
function themeZipUpload(array $manifest, array $files = []): array
{
    $path = tempnam(sys_get_temp_dir(), 'shop-theme-');
    $zip = new \ZipArchive;
    $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
    $zip->addFromString('theme.json', json_encode($manifest, JSON_THROW_ON_ERROR));

    foreach ($files as $filePath => $content) {
        $zip->addFromString($filePath, $content);
    }

    $zip->close();

    return [
        'file' => new UploadedFile($path, 'theme.zip', 'application/zip', UPLOAD_ERR_OK, true),
        'path' => $path,
    ];
}

it('creates filters updates and deletes store pages with tenant-safe handles and metadata', function () {
    $store = shopStore();
    $otherStore = shopStore('other-pages.test');
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    $token = storeConfigurationApiToken($user, $store, ['read-content', 'write-content']);
    $otherPage = Page::withoutGlobalScopes()->create([
        'store_id' => $otherStore->id,
        'title' => 'Other Store Page',
        'handle' => 'other-store-page',
        'status' => 'published',
    ]);

    $created = $this->withToken($token)->postJson("/api/admin/v1/stores/{$store->id}/pages", [
        'title' => 'Shipping Policy',
        'body_html' => '<h2>Shipping</h2><p>Details</p>',
        'status' => 'published',
    ])->assertCreated()
        ->assertJsonPath('data.title', 'Shipping Policy')
        ->assertJsonPath('data.handle', 'shipping-policy')
        ->assertJsonPath('data.status', 'published');

    $pageId = $created->json('data.id');
    $page = Page::query()->findOrFail($pageId);
    expect($created->json('data.published_at'))->not->toBeNull()
        ->and($page->body_html)->toBe('<h2>Shipping</h2><p>Details</p>');

    $this->withToken($token)->postJson("/api/admin/v1/stores/{$store->id}/pages", [
        'title' => 'Duplicate Handle',
        'handle' => 'shipping-policy',
    ])->assertUnprocessable()->assertJsonValidationErrors('handle');

    $duplicateTitle = $this->withToken($token)->postJson("/api/admin/v1/stores/{$store->id}/pages", [
        'title' => 'Shipping Policy',
    ])->assertCreated();
    expect($duplicateTitle->json('data.handle'))->toBe('shipping-policy-1');

    Page::query()->create(['store_id' => $store->id, 'title' => 'Draft', 'handle' => 'draft', 'status' => 'draft']);
    $this->withToken($token)->getJson("/api/admin/v1/stores/{$store->id}/pages?status=published&per_page=1")
        ->assertOk()
        ->assertJsonPath('data.0.id', $pageId)
        ->assertJsonPath('meta.per_page', 1)
        ->assertJsonPath('meta.total', 1);

    $this->withToken($token)->putJson("/api/admin/v1/stores/{$store->id}/pages/{$pageId}", [
        'title' => 'Shipping and Returns',
        'status' => 'draft',
    ])->assertOk()
        ->assertJsonPath('data.title', 'Shipping and Returns')
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.published_at', null);

    $this->withToken($token)->putJson("/api/admin/v1/stores/{$store->id}/pages/{$otherPage->id}", [
        'title' => 'Cross Tenant Update',
    ])->assertNotFound();

    $this->withToken($token)->deleteJson("/api/admin/v1/stores/{$store->id}/pages/{$pageId}")
        ->assertOk()
        ->assertExactJson(['message' => 'Page deleted']);
    expect(Page::query()->whereKey($pageId)->exists())->toBeFalse();
});

it('writes non-overlapping shipping zones and type-specific rates for the selected store', function () {
    $store = shopStore();
    $otherStore = shopStore('other-shipping.test');
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    $token = storeConfigurationApiToken($user, $store, ['read-settings', 'write-settings']);
    $foreignZone = ShippingZone::withoutGlobalScopes()->create([
        'store_id' => $otherStore->id,
        'name' => 'Foreign zone',
        'countries' => ['FR'],
    ]);
    ShippingZone::query()->create(['store_id' => $store->id, 'name' => 'France', 'countries' => ['FR']]);

    $created = $this->withToken($token)->postJson("/api/admin/v1/stores/{$store->id}/shipping/zones", [
        'name' => 'Germany',
        'countries_json' => ['de'],
        'regions_json' => ['BE', 'BY'],
    ])->assertCreated()
        ->assertJsonPath('data.name', 'Germany')
        ->assertJsonPath('data.countries_json.0', 'DE')
        ->assertJsonPath('data.regions_json.1', 'BY');

    $zoneId = $created->json('data.id');
    expect(ShippingZone::query()->findOrFail($zoneId)->countries)->toBe(['DE']);

    $this->withToken($token)->postJson("/api/admin/v1/stores/{$store->id}/shipping/zones", [
        'name' => 'Duplicate Germany',
        'countries_json' => ['DE'],
    ])->assertUnprocessable()->assertJsonValidationErrors('countries_json');

    $this->withToken($token)->postJson("/api/admin/v1/stores/{$store->id}/shipping/zones", [
        'name' => 'Unknown country',
        'countries_json' => ['ZZ'],
    ])->assertUnprocessable()->assertJsonValidationErrors('countries_json.0');

    $flatRate = $this->withToken($token)->postJson("/api/admin/v1/stores/{$store->id}/shipping/zones/{$zoneId}/rates", [
        'name' => 'Standard',
        'type' => 'flat',
        'config_json' => ['price_amount' => 500, 'currency' => 'EUR'],
    ])->assertCreated()->assertJsonPath('data.config_json.price_amount', 500);

    $weightRate = $this->withToken($token)->postJson("/api/admin/v1/stores/{$store->id}/shipping/zones/{$zoneId}/rates", [
        'name' => 'Heavy parcel',
        'type' => 'weight',
        'config_json' => [
            'currency' => 'EUR',
            'tiers' => [['min_weight_g' => 0, 'max_weight_g' => 1000, 'price_amount' => 800]],
        ],
    ])->assertCreated()->assertJsonPath('data.type', 'weight');

    expect(ShippingRate::query()->findOrFail($flatRate->json('data.id'))->price_amount)->toBe(500)
        ->and(ShippingRate::query()->findOrFail($weightRate->json('data.id'))->price_amount)->toBe(800);

    $this->withToken($token)->putJson("/api/admin/v1/stores/{$store->id}/shipping/zones/{$zoneId}", [
        'name' => 'Germany South',
        'countries_json' => ['DE'],
        'regions_json' => ['BW'],
    ])->assertOk()->assertJsonPath('data.name', 'Germany South');

    $this->withToken($token)->putJson("/api/admin/v1/stores/{$store->id}/shipping/zones/{$zoneId}", [
        'name' => 'Overlapping Zone',
        'countries_json' => ['FR'],
    ])->assertUnprocessable()->assertJsonValidationErrors('countries_json');

    $this->withToken($token)->getJson("/api/admin/v1/stores/{$store->id}/shipping/zones")
        ->assertOk()
        ->assertJsonPath('data.1.name', 'Germany South')
        ->assertJsonPath('data.1.rates.0.name', 'Standard');

    $this->withToken($token)->postJson("/api/admin/v1/stores/{$store->id}/shipping/zones/{$foreignZone->id}/rates", [
        'name' => 'Should not save',
        'type' => 'flat',
        'config_json' => ['price_amount' => 100],
    ])->assertNotFound();
});

it('installs validates configures and publishes a store-scoped theme archive', function () {
    $store = shopStore();
    $otherStore = shopStore('other-themes.test');
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    $token = storeConfigurationApiToken($user, $store, ['write-themes']);
    $manifest = [
        'name' => 'Aurora',
        'version' => '1.2.3',
        'templates' => ['templates/home.html', 'templates/product.html'],
        'settings_schema' => [
            'type' => 'object',
            'required' => ['colors'],
            'properties' => [
                'colors' => [
                    'type' => 'object',
                    'required' => ['primary'],
                    'properties' => ['primary' => ['type' => 'string']],
                ],
            ],
            'additionalProperties' => false,
        ],
    ];
    $archive = themeZipUpload($manifest, [
        'templates/home.html' => '<main>Home</main>',
        'templates/product.html' => '<main>Product</main>',
        'assets/theme.css' => 'body { color: #222; }',
    ]);
    $invalidArchive = themeZipUpload($manifest, ['templates/home.html' => '<main>Home</main>']);

    try {
        $created = $this->withToken($token)->post("/api/admin/v1/stores/{$store->id}/themes", [
            'file' => $archive['file'],
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Aurora')
            ->assertJsonPath('data.version', '1.2.3')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.published_at', null);

        $themeId = $created->json('data.id');
        $theme = Theme::query()->findOrFail($themeId);
        expect($theme->store_id)->toBe($store->id)
            ->and($theme->version)->toBe('1.2.3')
            ->and(DB::table('theme_files')->where('theme_id', $themeId)->count())->toBe(4);

        $this->withToken($token)->putJson("/api/admin/v1/stores/{$store->id}/themes/{$themeId}/settings", [
            'settings_json' => ['colors' => ['primary' => '#1a73e8']],
        ])->assertOk()->assertJsonPath('data.settings_json.colors.primary', '#1a73e8');

        $this->withToken($token)->putJson("/api/admin/v1/stores/{$store->id}/themes/{$themeId}/settings", [
            'settings_json' => ['colors' => ['primary' => 7]],
        ])->assertUnprocessable()->assertJsonValidationErrors('settings_json.colors.primary');

        $this->withToken($token)->post("/api/admin/v1/stores/{$store->id}/themes", [
            'file' => $invalidArchive['file'],
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('file');

        $previousTheme = Theme::query()->create([
            'store_id' => $store->id,
            'name' => 'Previous theme',
            'status' => 'published',
            'is_active' => true,
        ]);
        $otherTheme = Theme::withoutGlobalScopes()->create([
            'store_id' => $otherStore->id,
            'name' => 'Private Theme',
            'status' => 'draft',
        ]);
        $this->withToken($token)->postJson("/api/admin/v1/stores/{$store->id}/themes/{$otherTheme->id}/publish")
            ->assertNotFound();

        $this->withToken($token)->postJson("/api/admin/v1/stores/{$store->id}/themes/{$themeId}/publish")
            ->assertOk()
            ->assertJsonPath('data.id', $themeId)
            ->assertJsonPath('data.status', 'published');

        expect($theme->fresh()->status)->toBe('published')
            ->and($theme->fresh()->is_active)->toBeTrue()
            ->and($theme->fresh()->published_at)->not->toBeNull()
            ->and($previousTheme->fresh()->status)->toBe('draft')
            ->and($previousTheme->fresh()->is_active)->toBeFalse();
    } finally {
        @unlink($archive['path']);
        @unlink($invalidArchive['path']);
    }
});

it('returns 403 when staff or support users attempt store configuration writes', function (string $role) {
    $store = shopStore();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => $role]);
    $token = storeConfigurationApiToken($user, $store, ['write-content', 'write-settings', 'write-themes']);

    $this->withToken($token)->postJson("/api/admin/v1/stores/{$store->id}/pages", ['title' => 'Blocked'])
        ->assertForbidden();
    $this->withToken($token)->postJson("/api/admin/v1/stores/{$store->id}/shipping/zones", [
        'name' => 'Blocked',
        'countries_json' => ['DE'],
    ])->assertForbidden();
    $this->withToken($token)->post("/api/admin/v1/stores/{$store->id}/themes", [], ['Accept' => 'application/json'])
        ->assertForbidden();

    expect(Page::query()->exists())->toBeFalse()
        ->and(ShippingZone::query()->exists())->toBeFalse()
        ->and(Theme::query()->exists())->toBeFalse();
})->with(['staff' => 'staff', 'support' => 'support']);
