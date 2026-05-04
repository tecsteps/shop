<?php

use App\Enums\ThemeStatus;
use App\Models\Store;
use App\Models\Theme;
use App\Models\ThemeFile;
use App\Models\ThemeSettings;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function adminThemeApiStore(): Store
{
    return Store::query()->where('handle', 'acme-fashion')->firstOrFail();
}

function adminThemeApiUser(): User
{
    return User::query()->where('email', 'admin@acme.test')->firstOrFail();
}

/**
 * @param  list<string>  $abilities
 * @return array{token: \App\Models\PersonalAccessToken, plain_text: string}
 */
function adminThemeApiToken(Store $store, array $abilities): array
{
    return adminApiToken($store, $abilities);
}

/**
 * @param  array<string, string|null>  $overrides
 */
function adminThemeApiArchiveUpload(array $overrides = []): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'theme-api-');
    $zip = new ZipArchive;

    $zip->open($path, ZipArchive::OVERWRITE);

    foreach (array_merge([
        'theme.json' => json_encode([
            'name' => 'API Dawn',
            'version' => '2.0.0',
            'settings_json' => [
                'home' => [
                    'hero' => [
                        'heading' => 'Archive Hero',
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR),
        'layouts/storefront.blade.php' => '<x-layouts.storefront>{{ $slot }}</x-layouts.storefront>',
        'sections/hero.blade.php' => '<section>API Hero</section>',
        'sections/featured-products.blade.php' => '<section>API Products</section>',
    ], $overrides) as $file => $contents) {
        if ($contents === null) {
            continue;
        }

        $zip->addFromString("api-theme/{$file}", $contents);
    }

    $zip->close();

    return new UploadedFile($path, 'theme.zip', 'application/zip', null, true);
}

function adminThemeApiDraftTheme(Store $store): Theme
{
    $theme = Theme::factory()->create([
        'store_id' => $store->getKey(),
        'name' => 'API Draft Theme',
    ]);

    foreach ([
        'layouts/storefront.blade.php',
        'sections/hero.blade.php',
        'sections/featured-products.blade.php',
    ] as $path) {
        ThemeFile::factory()->create([
            'theme_id' => $theme->getKey(),
            'path' => $path,
        ]);
    }

    ThemeSettings::factory()->create([
        'theme_id' => $theme->getKey(),
    ]);

    return $theme;
}

test('admin theme api installs uploaded archives', function (): void {
    $store = adminThemeApiStore();
    $user = adminThemeApiUser();
    $writeToken = adminApiBearerToken($store, ['write-themes'], $user);

    $response = $this->withToken($writeToken)
        ->post("/api/admin/v1/stores/{$store->getKey()}/themes", [
            'name' => 'Uploaded API Theme',
            'file' => adminThemeApiArchiveUpload(),
        ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Uploaded API Theme')
        ->assertJsonPath('data.version', '2.0.0')
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.files_count', 4)
        ->assertJsonPath('data.settings_json.home.hero.heading', 'Archive Hero');

    $theme = Theme::withoutGlobalScopes()->findOrFail($response->json('data.id'));
    $hero = $theme->files()->withoutGlobalScopes()->where('path', 'sections/hero.blade.php')->firstOrFail();

    expect($theme->status)->toBe(ThemeStatus::Draft)
        ->and(Storage::disk('local')->get($hero->storage_key))->toBe('<section>API Hero</section>');
});

test('admin theme api updates settings and publishes one active theme', function (): void {
    $store = adminThemeApiStore();
    $published = Theme::withoutGlobalScopes()->where('store_id', $store->getKey())->where('status', ThemeStatus::Published)->firstOrFail();
    $draft = adminThemeApiDraftTheme($store);
    $writeToken = adminApiBearerToken($store, ['write-themes'], adminThemeApiUser());

    $this->withToken($writeToken)
        ->putJson("/api/admin/v1/stores/{$store->getKey()}/themes/{$draft->getKey()}/settings", [
            'settings_json' => [
                'home' => [
                    'hero' => [
                        'heading' => 'API Saved Hero',
                    ],
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('data.settings_json.home.hero.heading', 'API Saved Hero');

    $this->withToken($writeToken)
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/themes/{$draft->getKey()}/publish")
        ->assertOk()
        ->assertJsonPath('data.status', 'published');

    expect(ThemeSettings::withoutGlobalScopes()->where('theme_id', $draft->getKey())->first()?->settings_json['home']['hero']['heading'])->toBe('API Saved Hero')
        ->and($draft->refresh()->status)->toBe(ThemeStatus::Published)
        ->and($published->refresh()->status)->toBe(ThemeStatus::Draft)
        ->and(Theme::withoutGlobalScopes()->where('store_id', $store->getKey())->where('status', ThemeStatus::Published)->count())->toBe(1);
});

test('admin theme api enforces token abilities and store scope', function (): void {
    $store = adminThemeApiStore();
    $otherStore = Store::factory()->create();
    $draft = adminThemeApiDraftTheme($store);
    $readToken = adminThemeApiToken($store, ['read-settings']);
    $writeToken = adminThemeApiToken($store, ['write-themes']);
    $otherStoreToken = adminThemeApiToken($otherStore, ['write-themes']);

    $this->withToken($readToken['plain_text'])
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/themes/{$draft->getKey()}/publish")
        ->assertForbidden();

    $this->withToken($otherStoreToken['plain_text'])
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/themes/{$draft->getKey()}/publish")
        ->assertForbidden();

    $this->withToken($writeToken['plain_text'])
        ->putJson("/api/admin/v1/stores/{$store->getKey()}/themes/{$draft->getKey()}/settings", [
            'settings_json' => [
                'announcement' => [
                    'enabled' => false,
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('data.settings_json.announcement.enabled', false);
});

test('admin theme api validates archives settings and publishable files', function (): void {
    $store = adminThemeApiStore();
    $draft = Theme::factory()->create([
        'store_id' => $store->getKey(),
        'name' => 'Incomplete API Theme',
    ]);

    $this->withToken(adminApiBearerToken($store, ['write-themes'], adminThemeApiUser()))
        ->post("/api/admin/v1/stores/{$store->getKey()}/themes", [
            'file' => adminThemeApiArchiveUpload([
                'sections/hero.blade.php' => null,
            ]),
        ], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['file']);

    $this->withToken(adminApiBearerToken($store, ['write-themes'], adminThemeApiUser()))
        ->putJson("/api/admin/v1/stores/{$store->getKey()}/themes/{$draft->getKey()}/settings", [
            'settings_json' => ['invalid-list-value'],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['settings_json']);

    $this->withToken(adminApiBearerToken($store, ['write-themes'], adminThemeApiUser()))
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/themes/{$draft->getKey()}/publish")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['theme']);
});
