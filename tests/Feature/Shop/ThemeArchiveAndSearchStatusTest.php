<?php

use App\Jobs\ReindexProducts;
use App\Livewire\Admin\Search\Settings as SearchSettingsComponent;
use App\Livewire\Admin\Themes\Index as ThemesIndex;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreSettings;
use App\Models\Theme;
use App\Services\SearchService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * @param  array<string, string>  $entries
 */
function acceptanceThemeArchive(array $entries): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'acceptance-theme-');
    $zip = new ZipArchive;
    $result = $zip->open($path, ZipArchive::OVERWRITE);
    expect($result)->toBeTrue();

    foreach ($entries as $name => $contents) {
        $zip->addFromString($name, $contents);
    }

    $zip->close();

    return new UploadedFile($path, 'theme.zip', 'application/zip', null, true);
}

/** @return array<string, string> */
function validAcceptanceThemeEntries(): array
{
    return [
        'theme.json' => json_encode([
            'name' => 'Acceptance Theme',
            'version' => '2.4.1',
            'required_templates' => ['templates/index.blade.php'],
            'settings' => [
                'colors' => ['primary' => '#123456'],
                'layout' => ['width' => 'wide'],
            ],
        ], JSON_THROW_ON_ERROR),
        'templates/index.blade.php' => '<main>{{ $slot ?? "Acceptance" }}</main>',
        'assets/theme.css' => 'body { color: #123456; }',
        'assets/theme.js' => 'window.themeReady = true;',
    ];
}

/** @param list<string> $abilities */
function themeSearchAdminToken(array $context, array $abilities): string
{
    $abilities[] = 'store:'.$context['store']->id;

    return $context['user']->createToken('theme-search-test', array_values(array_unique($abilities)))->plainTextToken;
}

function themeSearchAdminUrl(Store $store, string $path): string
{
    return "/api/admin/v1/stores/{$store->id}/".ltrim($path, '/');
}

describe('theme archive installation and duplication', function () {
    it('validates and installs an archive with confined files and trustworthy metadata', function () {
        Storage::fake('local');
        $context = createStoreContext();
        $token = themeSearchAdminToken($context, ['write-themes']);
        $entries = validAcceptanceThemeEntries();

        $response = $this->withToken($token)->post(themeSearchAdminUrl($context['store'], 'themes'), [
            'file' => acceptanceThemeArchive($entries),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.store_id', $context['store']->id)
            ->assertJsonPath('data.name', 'Acceptance Theme')
            ->assertJsonPath('data.version', '2.4.1')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.settings.settings_json.colors.primary', '#123456')
            ->assertJsonCount(count($entries), 'data.files');

        $theme = Theme::withoutGlobalScopes()
            ->with(['files', 'settings'])
            ->findOrFail((int) $response->json('data.id'));
        $prefix = "themes/{$context['store']->id}/{$theme->id}/";

        expect($theme->files->pluck('path')->sort()->values()->all())
            ->toBe(collect(array_keys($entries))->sort()->values()->all())
            ->and($theme->settings?->settings_json)->toBe([
                'colors' => ['primary' => '#123456'],
                'layout' => ['width' => 'wide'],
            ]);

        foreach ($theme->files as $file) {
            expect($file->storage_key)->toStartWith($prefix)
                ->and($file->storage_key)->toBe($prefix.$file->path)
                ->and($file->sha256)->toBe(hash('sha256', $entries[$file->path]))
                ->and($file->byte_size)->toBe(strlen($entries[$file->path]));
            Storage::disk('local')->assertExists($file->storage_key);
            expect(Storage::disk('local')->get($file->storage_key))->toBe($entries[$file->path]);
        }
    });

    it('rejects malformed and unsafe archives atomically', function () {
        Storage::fake('local');
        $context = createStoreContext();
        $token = themeSearchAdminToken($context, ['write-themes']);
        $validManifest = validAcceptanceThemeEntries()['theme.json'];
        $template = ['templates/index.blade.php' => '<main>Safe</main>'];
        $archives = [
            'missing manifest' => [
                ...$template,
                'assets/theme.css' => 'body {}',
            ],
            'invalid manifest JSON' => [
                'theme.json' => '{invalid',
                ...$template,
            ],
            'non-object manifest' => [
                'theme.json' => '[]',
                ...$template,
            ],
            'invalid semantic version' => [
                'theme.json' => json_encode([
                    'name' => 'Bad Version',
                    'version' => 'version two',
                ], JSON_THROW_ON_ERROR),
                ...$template,
            ],
            'missing required template' => [
                'theme.json' => $validManifest,
                'assets/theme.css' => 'body {}',
            ],
            'disallowed executable extension' => [
                'theme.json' => $validManifest,
                ...$template,
                'assets/payload.php' => '<?php echo "unsafe";',
            ],
            'parent traversal with slash' => [
                'theme.json' => $validManifest,
                ...$template,
                '../escape.css' => 'unsafe',
            ],
            'parent traversal with backslash' => [
                'theme.json' => $validManifest,
                ...$template,
                '..\\escape.css' => 'unsafe',
            ],
            'absolute path' => [
                'theme.json' => $validManifest,
                ...$template,
                '/escape.css' => 'unsafe',
            ],
        ];

        foreach ($archives as $case => $entries) {
            $this->withToken($token)->post(themeSearchAdminUrl($context['store'], 'themes'), [
                'file' => acceptanceThemeArchive($entries),
            ], ['Accept' => 'application/json'])
                ->assertUnprocessable($case)
                ->assertJsonValidationErrors('file');

            expect(Theme::withoutGlobalScopes()->where('store_id', $context['store']->id)->count())
                ->toBe(0, $case)
                ->and(Storage::disk('local')->allFiles())->toBe([], $case);
        }
    });

    it('duplicates the settings and every physical file into an independent draft theme', function () {
        Storage::fake('local');
        $context = createStoreContext();
        $token = themeSearchAdminToken($context, ['write-themes']);
        $entries = validAcceptanceThemeEntries();

        $response = $this->withToken($token)->post(themeSearchAdminUrl($context['store'], 'themes'), [
            'file' => acceptanceThemeArchive($entries),
        ], ['Accept' => 'application/json'])->assertCreated();
        $source = Theme::withoutGlobalScopes()->with(['files', 'settings'])
            ->findOrFail((int) $response->json('data.id'));
        $source->update(['status' => 'published', 'published_at' => now()]);

        actingAsAdmin($context['user'], $context['store']);
        Livewire::test(ThemesIndex::class)
            ->call('duplicateTheme', $source->id)
            ->assertDispatched('toast');

        $source->refresh()->load(['files', 'settings']);
        $copy = Theme::withoutGlobalScopes()
            ->where('store_id', $context['store']->id)
            ->whereKeyNot($source->id)
            ->with(['files', 'settings'])
            ->sole();

        expect($source->status->value)->toBe('published')
            ->and($copy->name)->toBe('Acceptance Theme Copy')
            ->and($copy->version)->toBe($source->version)
            ->and($copy->status->value)->toBe('draft')
            ->and($copy->published_at)->toBeNull()
            ->and($copy->settings?->settings_json)->toBe($source->settings?->settings_json)
            ->and($copy->files->pluck('path')->sort()->values()->all())
            ->toBe($source->files->pluck('path')->sort()->values()->all());

        $sourceFiles = $source->files->keyBy('path');
        foreach ($copy->files as $copyFile) {
            $sourceFile = $sourceFiles->get($copyFile->path);
            expect($sourceFile)->not->toBeNull()
                ->and($copyFile->storage_key)->not->toBe($sourceFile->storage_key)
                ->and($copyFile->storage_key)->toBe("themes/{$context['store']->id}/{$copy->id}/{$copyFile->path}")
                ->and($copyFile->sha256)->toBe($sourceFile->sha256)
                ->and($copyFile->byte_size)->toBe($sourceFile->byte_size);
            Storage::disk('local')->assertExists($copyFile->storage_key);
            expect(Storage::disk('local')->get($copyFile->storage_key))
                ->toBe(Storage::disk('local')->get($sourceFile->storage_key));
        }

        $copyCss = $copy->files->firstWhere('path', 'assets/theme.css');
        $sourceCss = $sourceFiles->get('assets/theme.css');
        Storage::disk('local')->put($copyCss->storage_key, 'copy-only change');
        $copy->settings()->update(['settings_json' => ['colors' => ['primary' => '#abcdef']]]);

        expect(Storage::disk('local')->get($sourceCss->storage_key))->toBe($entries['assets/theme.css'])
            ->and($source->settings?->refresh()->settings_json)->toBe([
                'colors' => ['primary' => '#123456'],
                'layout' => ['width' => 'wide'],
            ]);
    });
});

describe('persistent search reindex status', function () {
    it('persists queued status before dispatch and preserves unrelated settings', function () {
        Queue::fake();
        $context = createStoreContext();
        $record = StoreSettings::withoutGlobalScopes()->findOrFail($context['store']->id);
        $record->update(['settings_json' => [
            ...$record->settings_json,
            'brand' => ['color' => '#112233'],
            'search' => [
                'status' => 'ready',
                'progress' => 100,
                'documents_count' => 8,
                'last_reindex_at' => '2026-07-01T08:00:00+00:00',
                'last_reindex_duration_seconds' => 12,
                'pending_updates' => 0,
            ],
        ]]);
        $token = themeSearchAdminToken($context, ['read-settings', 'write-settings']);

        $this->withToken($token)
            ->postJson(themeSearchAdminUrl($context['store'], 'search/reindex'))
            ->assertAccepted();

        $settings = StoreSettings::withoutGlobalScopes()->findOrFail($context['store']->id)->settings_json;
        expect(data_get($settings, 'search.status'))->toBe('queued')
            ->and(data_get($settings, 'search.progress'))->toBe(0)
            ->and(data_get($settings, 'search.documents_count'))->toBe(8)
            ->and(data_get($settings, 'search.last_reindex_at'))->toBe('2026-07-01T08:00:00+00:00')
            ->and(data_get($settings, 'brand.color'))->toBe('#112233');
        Queue::assertPushed(ReindexProducts::class, 1);

        $this->withToken($token)
            ->getJson(themeSearchAdminUrl($context['store'], 'search/status'))
            ->assertOk()
            ->assertJsonPath('data.store_id', $context['store']->id)
            ->assertJsonPath('data.index_status', 'queued')
            ->assertJsonPath('data.documents_count', 8)
            ->assertJsonPath('data.last_reindex_at', '2026-07-01T08:00:00+00:00')
            ->assertJsonPath('data.pending_updates', 0);
    });

    it('hydrates persisted processing state when the admin screen is reopened', function () {
        $context = createStoreContext();
        $record = StoreSettings::withoutGlobalScopes()->findOrFail($context['store']->id);
        $record->update(['settings_json' => [
            ...$record->settings_json,
            'search' => [
                'status' => 'processing',
                'progress' => 45,
                'documents_count' => 12,
                'last_reindex_at' => '2026-07-01T08:00:00+00:00',
            ],
        ]]);
        actingAsAdmin($context['user'], $context['store']);

        Livewire::test(SearchSettingsComponent::class)
            ->assertSet('isReindexing', true)
            ->assertSet('reindexProgress', 45)
            ->assertSet('lastIndexedAt', '2026-07-01T08:00:00+00:00');
    });

    it('persists completion from the job and restores the previous tenant binding', function () {
        $first = createStoreContext();
        Product::factory()->count(2)->for($first['store'])->create();
        $second = createStoreContext();
        Product::factory()->count(3)->for($second['store'])->create();
        bindStore($second['store']);

        (new ReindexProducts($first['store']->id))->handle(app(SearchService::class));

        expect(app('current_store')->is($second['store']))->toBeTrue();
        $firstSettings = StoreSettings::withoutGlobalScopes()->findOrFail($first['store']->id)->settings_json;
        $secondSettings = StoreSettings::withoutGlobalScopes()->findOrFail($second['store']->id)->settings_json;
        expect(data_get($firstSettings, 'search.status'))->toBe('ready')
            ->and(data_get($firstSettings, 'search.progress'))->toBe(100)
            ->and(data_get($firstSettings, 'search.documents_count'))->toBe(2)
            ->and(data_get($firstSettings, 'search.last_reindex_at'))->not->toBeNull()
            ->and(data_get($firstSettings, 'search.last_reindex_duration_seconds'))->toBeInt()
            ->and(data_get($firstSettings, 'search.pending_updates'))->toBe(0)
            ->and(data_get($secondSettings, 'search.status'))->toBeNull();
    });

    it('rejects a second reindex while one is already queued', function () {
        Queue::fake();
        $context = createStoreContext();
        $token = themeSearchAdminToken($context, ['write-settings']);

        $this->withToken($token)
            ->postJson(themeSearchAdminUrl($context['store'], 'search/reindex'))
            ->assertAccepted();
        $this->withToken($token)
            ->postJson(themeSearchAdminUrl($context['store'], 'search/reindex'))
            ->assertConflict();
        Queue::assertPushed(ReindexProducts::class, 1);
    });

    it('returns the documented persistent status schema', function () {
        $context = createStoreContext();
        $record = StoreSettings::withoutGlobalScopes()->findOrFail($context['store']->id);
        $record->update(['settings_json' => [
            ...$record->settings_json,
            'search' => [
                'status' => 'ready',
                'last_reindex_at' => '2026-07-01T08:00:00+00:00',
                'last_reindex_duration_seconds' => 17,
                'documents_count' => 23,
                'pending_updates' => 0,
            ],
        ]]);
        $token = themeSearchAdminToken($context, ['read-settings']);

        $this->withToken($token)
            ->getJson(themeSearchAdminUrl($context['store'], 'search/status'))
            ->assertOk()
            ->assertExactJson(['data' => [
                'store_id' => $context['store']->id,
                'index_status' => 'ready',
                'last_reindex_at' => '2026-07-01T08:00:00+00:00',
                'last_reindex_duration_seconds' => 17,
                'documents_count' => 23,
                'pending_updates' => 0,
            ]]);
    });
});
