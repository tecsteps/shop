<?php

use App\Livewire\Admin\Themes\Editor;
use App\Models\Store;
use App\Models\Theme;
use App\Models\ThemeSettings;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function themeEditorAdmin(): array
{
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    app()->instance('current_store', $store);

    return [$user, $store];
}

test('it renders the theme editor page', function () {
    [$user, $store] = themeEditorAdmin();

    $theme = Theme::factory()->create([
        'store_id' => $store->id,
        'name' => 'Test Theme',
    ]);

    Livewire::actingAs($user)
        ->test(Editor::class, ['theme' => $theme])
        ->assertSee('Test Theme')
        ->assertSuccessful();
});

test('it loads default sections when no settings exist', function () {
    [$user, $store] = themeEditorAdmin();

    $theme = Theme::factory()->create([
        'store_id' => $store->id,
    ]);

    $component = Livewire::actingAs($user)
        ->test(Editor::class, ['theme' => $theme]);

    expect($component->get('sections'))->not->toBeEmpty();
});

test('it selects a section', function () {
    [$user, $store] = themeEditorAdmin();

    $theme = Theme::factory()->create([
        'store_id' => $store->id,
    ]);

    Livewire::actingAs($user)
        ->test(Editor::class, ['theme' => $theme])
        ->call('selectSection', 'header')
        ->assertSet('selectedSection', 'header');
});

test('it updates a section setting', function () {
    [$user, $store] = themeEditorAdmin();

    $theme = Theme::factory()->create([
        'store_id' => $store->id,
    ]);

    Livewire::actingAs($user)
        ->test(Editor::class, ['theme' => $theme])
        ->call('selectSection', 'header')
        ->call('updateSetting', 'logo_text', 'My Store')
        ->assertSet('sectionSettings.header.logo_text', 'My Store');
});

test('it saves theme settings', function () {
    [$user, $store] = themeEditorAdmin();

    $theme = Theme::factory()->create([
        'store_id' => $store->id,
    ]);

    Livewire::actingAs($user)
        ->test(Editor::class, ['theme' => $theme])
        ->call('selectSection', 'header')
        ->call('updateSetting', 'logo_text', 'Saved Store')
        ->call('save')
        ->assertDispatched('toast');

    $settings = ThemeSettings::where('theme_id', $theme->id)->first();

    expect($settings)->not->toBeNull()
        ->and($settings->settings_json['values']['header']['logo_text'])->toBe('Saved Store');
});

test('it publishes a theme', function () {
    [$user, $store] = themeEditorAdmin();

    $existingPublished = Theme::factory()->create([
        'store_id' => $store->id,
        'status' => 'published',
    ]);

    $draft = Theme::factory()->draft()->create([
        'store_id' => $store->id,
    ]);

    Livewire::actingAs($user)
        ->test(Editor::class, ['theme' => $draft])
        ->call('publish')
        ->assertDispatched('toast');

    $draft->refresh();
    $existingPublished->refresh();

    expect($draft->status->value)->toBe('published')
        ->and($existingPublished->status->value)->toBe('draft');
});

test('it aborts if theme belongs to a different store', function () {
    [$user, $store] = themeEditorAdmin();

    $otherStore = Store::factory()->create();
    $theme = Theme::factory()->create([
        'store_id' => $otherStore->id,
    ]);

    Livewire::actingAs($user)
        ->test(Editor::class, ['theme' => $theme])
        ->assertNotFound();
});

test('it does not update setting when no section is selected', function () {
    [$user, $store] = themeEditorAdmin();

    $theme = Theme::factory()->create([
        'store_id' => $store->id,
    ]);

    Livewire::actingAs($user)
        ->test(Editor::class, ['theme' => $theme])
        ->assertSet('selectedSection', null)
        ->call('updateSetting', 'logo_text', 'Should Not Save')
        ->assertSet('sectionSettings', []);
});
