<?php

use App\Enums\ThemeStatus;
use App\Models\Store;
use App\Models\Theme;
use App\Models\User;
use App\Services\ApiTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
    $this->seed();
    $this->store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $this->otherStore = Store::query()->where('handle', 'acme-electronics')->firstOrFail();
    $this->user = User::query()->where('email', 'admin@example.com')->firstOrFail();
});

function adminThemeTokenFor($test, array $abilities): string
{
    return app(ApiTokenService::class)->create($test->store, $test->user, 'Theme API test', $abilities)['plain_text_token'];
}

function adminThemeArchive(bool $withManifest = true): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'theme_archive_');
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    if ($withManifest) {
        $zip->addFromString('theme.json', json_encode([
            'name' => 'Manifest Dawn',
            'version' => '2.1.0',
            'settings' => [
                'home' => [
                    'hero_heading' => 'API Theme',
                ],
            ],
        ], JSON_THROW_ON_ERROR));
    }

    $zip->addFromString('templates/home.blade.php', '<div>Home</div>');
    $zip->addFromString('sections/featured-products.blade.php', '<div>Featured</div>');
    $zip->close();

    return new UploadedFile($path, 'theme.zip', 'application/zip', null, true);
}

test('admin theme api imports publishes and updates theme settings', function (): void {
    Storage::fake('local');

    $publishedTheme = Theme::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('status', ThemeStatus::Published)
        ->firstOrFail();
    $storeUrl = route('api.admin.themes.store', $this->store);

    $this->postJson($storeUrl)->assertUnauthorized();

    $this->withToken(adminThemeTokenFor($this, ['read-themes']))
        ->postJson($storeUrl)
        ->assertForbidden();

    $response = $this->withToken(adminThemeTokenFor($this, ['write-themes']))
        ->withHeader('Accept', 'application/json')
        ->post($storeUrl, [
            'name' => 'API Dawn',
            'file' => adminThemeArchive(),
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'API Dawn')
        ->assertJsonPath('data.version', '2.1.0')
        ->assertJsonPath('data.status', ThemeStatus::Draft->value)
        ->assertJsonPath('data.settings_json.home.hero_heading', 'API Theme')
        ->assertJsonPath('data.files_count', 2);

    $theme = Theme::withoutGlobalScopes()
        ->with('settings', 'files')
        ->whereKey($response->json('data.id'))
        ->firstOrFail();

    expect($theme->files)->toHaveCount(2)
        ->and($theme->settings?->settings_json['home']['hero_heading'])->toBe('API Theme');

    Storage::disk('local')->assertExists("themes/{$this->store->id}/{$theme->id}/templates/home.blade.php");

    $this->withToken(adminThemeTokenFor($this, ['write-themes']))
        ->putJson(route('api.admin.themes.settings.update', [$this->store, $theme]), [
            'settings_json' => [
                'announcement' => [
                    'enabled' => true,
                    'text' => 'API launch',
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('data.settings_json.announcement.text', 'API launch');

    $this->withToken(adminThemeTokenFor($this, ['write-themes']))
        ->postJson(route('api.admin.themes.publish', [$this->store, $theme]))
        ->assertOk()
        ->assertJsonPath('data.status', ThemeStatus::Published->value);

    expect($theme->refresh()->status)->toBe(ThemeStatus::Published)
        ->and($theme->published_at)->not->toBeNull()
        ->and($publishedTheme->refresh()->status)->toBe(ThemeStatus::Draft)
        ->and($publishedTheme->published_at)->toBeNull();
});

test('admin theme api rejects invalid theme archives', function (): void {
    Storage::fake('local');

    $this->withToken(adminThemeTokenFor($this, ['write-themes']))
        ->withHeader('Accept', 'application/json')
        ->post(route('api.admin.themes.store', $this->store), [
            'file' => adminThemeArchive(withManifest: false),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file');
});

test('admin theme api enforces store scoped token access', function (): void {
    $otherStoreTheme = Theme::factory()
        ->for($this->otherStore)
        ->create();

    $this->withToken(adminThemeTokenFor($this, ['write-themes']))
        ->postJson(route('api.admin.themes.publish', [$this->otherStore, $otherStoreTheme]))
        ->assertForbidden();

    $this->withToken(adminThemeTokenFor($this, ['write-themes']))
        ->postJson(route('api.admin.themes.publish', [$this->store, $otherStoreTheme]))
        ->assertNotFound();
});
