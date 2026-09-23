<?php

use App\Livewire\Admin\Themes\Editor;
use App\Livewire\Admin\Themes\Index;
use App\Models\Store;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

function themeAdminUser(Store $store, string $role = 'owner'): User
{
    $user = User::factory()->create();
    $user->stores()->attach($store->getKey(), ['role' => $role]);
    app()->instance('current_store', $store);

    return $user;
}

function createAdminTheme(Store $store, string $name, string $status = 'draft', bool $active = false): Theme
{
    return Theme::query()->create([
        'store_id' => $store->getKey(),
        'name' => $name,
        'version' => '1.0.0',
        'status' => $status,
        'is_active' => $active,
    ]);
}

it('lists only current-store themes and keeps theme management behind its role gate', function () {
    $store = shopStore('theme-list.test');
    $otherStore = shopStore('theme-list-other.test');
    $owner = themeAdminUser($store);
    createAdminTheme($store, 'Store theme', 'published', true);
    createAdminTheme($otherStore, 'Private theme', 'draft');

    Livewire::actingAs($owner)->test(Index::class)
        ->assertSee('Store theme')
        ->assertDontSee('Private theme')
        ->assertSee('Customize');

    $staff = themeAdminUser($store, 'staff');

    $this->actingAs($staff)
        ->withSession(['current_store_id' => $store->getKey()])
        ->get(route('admin.themes'))
        ->assertForbidden();
});

it('publishes, duplicates, and deletes themes without crossing store boundaries', function () {
    $store = shopStore('theme-actions.test');
    $otherStore = shopStore('theme-actions-other.test');
    $owner = themeAdminUser($store);
    $activeTheme = createAdminTheme($store, 'Current theme', 'published', true);
    $draftTheme = createAdminTheme($store, 'New theme');
    $foreignTheme = createAdminTheme($otherStore, 'Foreign theme', 'published', true);
    $draftTheme->settings()->create([
        'settings_json' => ['colors' => ['primary' => '#123456']],
        'updated_at' => now(),
    ]);

    $content = '{"name":"Fixture theme"}';
    DB::table('theme_files')->insert([
        'theme_id' => $draftTheme->getKey(),
        'path' => 'theme.json',
        'content' => $content,
        'storage_key' => 'themes/'.$draftTheme->getKey().'/theme.json',
        'sha256' => hash('sha256', $content),
        'byte_size' => strlen($content),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => Livewire::actingAs($owner)->test(Index::class)
        ->call('publishTheme', $foreignTheme->getKey()))
        ->toThrow(ModelNotFoundException::class);

    expect($foreignTheme->fresh()->is_active)->toBeTrue();

    Livewire::actingAs($owner)->test(Index::class)
        ->call('publishTheme', $draftTheme->getKey())
        ->assertHasNoErrors();

    expect($draftTheme->fresh()->status)->toBe('published')
        ->and($draftTheme->fresh()->is_active)->toBeTrue()
        ->and($draftTheme->fresh()->published_at)->not->toBeNull()
        ->and($activeTheme->fresh()->status)->toBe('draft')
        ->and($activeTheme->fresh()->is_active)->toBeFalse()
        ->and($foreignTheme->fresh()->is_active)->toBeTrue();

    Livewire::actingAs($owner)->test(Index::class)
        ->call('duplicateTheme', $draftTheme->getKey())
        ->assertHasNoErrors();

    $copy = Theme::query()->where('store_id', $store->getKey())->where('name', 'New theme (Copy)')->firstOrFail();
    $copyFile = DB::table('theme_files')->where('theme_id', $copy->getKey())->where('path', 'theme.json')->first();

    expect($copy->status)->toBe('draft')
        ->and($copy->is_active)->toBeFalse()
        ->and($copy->published_at)->toBeNull()
        ->and($copy->settings->settings_json)->toBe(['colors' => ['primary' => '#123456']])
        ->and($copyFile->content)->toBe($content)
        ->and($copyFile->storage_key)->toBe('themes/'.$copy->getKey().'/theme.json')
        ->and($copyFile->sha256)->toBe(hash('sha256', $content))
        ->and((int) $copyFile->byte_size)->toBe(strlen($content));

    Livewire::actingAs($owner)->test(Index::class)
        ->call('deleteTheme', $copy->getKey())
        ->assertHasNoErrors();

    expect(Theme::query()->whereKey($copy->getKey())->exists())->toBeFalse()
        ->and(DB::table('theme_files')->where('theme_id', $copy->getKey())->exists())->toBeFalse();

    expect(fn () => Livewire::actingAs($owner)->test(Index::class)
        ->call('deleteTheme', $foreignTheme->getKey()))
        ->toThrow(ModelNotFoundException::class);

    expect(Theme::withoutGlobalScopes()->whereKey($foreignTheme->getKey())->exists())->toBeTrue();
});

it('edits settings from a theme schema and rejects values with the wrong type', function () {
    $store = shopStore('theme-editor.test');
    $owner = themeAdminUser($store);
    $theme = createAdminTheme($store, 'Schema theme');
    $manifest = json_encode([
        'name' => 'Schema theme',
        'version' => '1.0.0',
        'templates' => ['templates/home.blade.php'],
        'settings_schema' => [
            'type' => 'object',
            'required' => ['header'],
            'properties' => [
                'header' => [
                    'type' => 'object',
                    'title' => 'Header',
                    'required' => ['store_name'],
                    'properties' => [
                        'store_name' => ['type' => 'string', 'title' => 'Store name', 'default' => 'My store'],
                        'background' => ['type' => 'string', 'format' => 'color', 'title' => 'Background color', 'default' => '#112233'],
                    ],
                ],
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    DB::table('theme_files')->insert([
        'theme_id' => $theme->getKey(),
        'path' => 'theme.json',
        'content' => $manifest,
        'storage_key' => 'themes/'.$theme->getKey().'/theme.json',
        'sha256' => hash('sha256', $manifest),
        'byte_size' => strlen($manifest),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $theme->settings()->create([
        'settings_json' => ['header' => ['store_name' => 'Old name', 'background' => '#112233'], 'legacy' => 'keep'],
        'updated_at' => now(),
    ]);

    $component = Livewire::actingAs($owner)->test(Editor::class, ['theme' => $theme]);
    $component->assertSee('Header')
        ->assertSee('Store name')
        ->assertSet('selectedSection', 'header')
        ->assertSet('sectionSettings.setting_header_store_name', 'Old name')
        ->set('sectionSettings.setting_header_store_name', 'Updated shop')
        ->assertSet('sectionSettings.setting_header_store_name', 'Updated shop')
        ->call('save')
        ->assertHasNoErrors();

    expect($theme->fresh()->settings->settings_json)->toBe([
        'header' => ['store_name' => 'Updated shop', 'background' => '#112233'],
        'legacy' => 'keep',
    ]);

    $component->call('saveAndPublish')->assertHasNoErrors();
    expect($theme->fresh()->status)->toBe('published')
        ->and($theme->fresh()->is_active)->toBeTrue()
        ->and($theme->fresh()->published_at)->not->toBeNull();

    $component->set('sectionSettings.setting_header_background', 'invalid')
        ->call('save')
        ->assertHasErrors('settings.header.background');

    expect($theme->fresh()->settings->settings_json['header']['background'])->toBe('#112233');
});

it('renders the full-page editor route for an authorized store manager', function () {
    $store = shopStore('theme-editor-route.test');
    $owner = themeAdminUser($store);
    $theme = createAdminTheme($store, 'Route theme');

    $this->actingAs($owner)
        ->withSession(['current_store_id' => $store->getKey()])
        ->get(route('admin.themes.editor', ['theme' => $theme->getKey()]))
        ->assertOk()
        ->assertSee('Back to themes')
        ->assertSee('Live preview')
        ->assertSee('Save and publish');
});
