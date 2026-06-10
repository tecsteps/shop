<?php

use App\Enums\StoreUserRole;
use App\Enums\ThemeStatus;
use App\Livewire\Admin\Themes\Editor as ThemeEditor;
use App\Livewire\Admin\Themes\Index as ThemesIndex;
use App\Models\Theme;
use App\Models\ThemeSettings;
use App\Services\ThemeSettingsService;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->user = $this->context['user'];
});

it('lists themes as cards', function () {
    Theme::factory()->for($this->store)->create(['name' => 'Live Theme']);
    Theme::factory()->draft()->for($this->store)->create(['name' => 'Draft Theme']);

    actingAsAdmin($this->user)
        ->get('/admin/themes')
        ->assertOk()
        ->assertSee('Live Theme')
        ->assertSee('Draft Theme');
});

it('publishes a theme and invalidates the cached settings', function () {
    $oldTheme = Theme::factory()->for($this->store)->create(['published_at' => now()->subDay()]);
    ThemeSettings::factory()->for($oldTheme, 'theme')->create(['settings_json' => ['hero_heading' => 'Old Heading']]);

    $newTheme = Theme::factory()->draft()->for($this->store)->create();
    ThemeSettings::factory()->for($newTheme, 'theme')->create(['settings_json' => ['hero_heading' => 'New Heading']]);

    $service = app(ThemeSettingsService::class);

    expect($service->all($this->store)['hero_heading'])->toBe('Old Heading');

    actingAsAdmin($this->user);

    Livewire::test(ThemesIndex::class)
        ->call('publishTheme', $newTheme->getKey())
        ->assertDispatched('toast');

    expect($newTheme->refresh()->status)->toBe(ThemeStatus::Published);
    expect($oldTheme->refresh()->status)->toBe(ThemeStatus::Draft);
    expect($service->all($this->store)['hero_heading'])->toBe('New Heading');
});

it('duplicates a theme including its settings', function () {
    $theme = Theme::factory()->for($this->store)->create(['name' => 'Original']);
    ThemeSettings::factory()->for($theme, 'theme')->create(['settings_json' => ['hero_heading' => 'Copied Heading']]);

    actingAsAdmin($this->user);

    Livewire::test(ThemesIndex::class)
        ->call('duplicateTheme', $theme->getKey())
        ->assertDispatched('toast');

    $copy = Theme::query()->where('name', 'Original (Copy)')->firstOrFail();

    expect($copy->status)->toBe(ThemeStatus::Draft);
    expect($copy->settings->settings_json['hero_heading'])->toBe('Copied Heading');
});

it('deletes a draft theme but refuses to delete the published theme', function () {
    $published = Theme::factory()->for($this->store)->create();
    $draft = Theme::factory()->draft()->for($this->store)->create();

    actingAsAdmin($this->user);

    Livewire::test(ThemesIndex::class)
        ->call('deleteTheme', $draft->getKey());

    $this->assertDatabaseMissing('themes', ['id' => $draft->getKey()]);

    Livewire::test(ThemesIndex::class)
        ->call('deleteTheme', $published->getKey())
        ->assertDispatched('toast', type: 'error');

    $this->assertDatabaseHas('themes', ['id' => $published->getKey()]);
});

it('saves settings from the theme editor', function () {
    $theme = Theme::factory()->for($this->store)->create();
    ThemeSettings::factory()->for($theme, 'theme')->create(['settings_json' => ['hero_heading' => 'Before']]);

    $service = app(ThemeSettingsService::class);

    expect($service->all($this->store)['hero_heading'])->toBe('Before');

    actingAsAdmin($this->user);

    Livewire::test(ThemeEditor::class, ['themeId' => $theme->getKey()])
        ->call('selectSection', 'hero')
        ->set('settings.hero_heading', 'After')
        ->call('save')
        ->assertDispatched('toast');

    expect($theme->settings()->first()->settings_json['hero_heading'])->toBe('After');
    expect($service->all($this->store)['hero_heading'])->toBe('After');
});

it('reorders and toggles home page sections in the editor', function () {
    $theme = Theme::factory()->for($this->store)->create();

    actingAsAdmin($this->user);

    Livewire::test(ThemeEditor::class, ['themeId' => $theme->getKey()])
        ->call('reorderSections', 'newsletter', 0)
        ->call('toggleSection', 'rich-text')
        ->call('save')
        ->assertDispatched('toast');

    $sections = $theme->settings()->first()->settings_json['sections'];

    expect($sections[0])->toBe('newsletter');
    expect($sections)->not->toContain('rich-text');
});

it('publishes from the editor via save and publish', function () {
    $theme = Theme::factory()->draft()->for($this->store)->create();

    actingAsAdmin($this->user);

    Livewire::test(ThemeEditor::class, ['themeId' => $theme->getKey()])
        ->call('publish')
        ->assertDispatched('toast');

    expect($theme->refresh()->status)->toBe(ThemeStatus::Published);
    expect($theme->published_at)->not->toBeNull();
});

it('restricts theme management to owner and admin roles', function () {
    Theme::factory()->for($this->store)->create();

    $staff = createStoreMember($this->store, StoreUserRole::Staff);

    actingAsAdmin($staff, $this->store)
        ->get('/admin/themes')
        ->assertForbidden();
});
