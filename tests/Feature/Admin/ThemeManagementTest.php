<?php

use App\Enums\ThemeStatus;
use App\Livewire\Admin\Themes\Editor;
use App\Livewire\Admin\Themes\Index;
use App\Models\Theme;
use App\Models\ThemeFile;
use App\Models\ThemeSettings;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(function () {
    $this->store = $this->createStore();
    $this->user = $this->createUserWithRole($this->store, 'owner');
    $this->bindStore($this->store);
});

test('renders the themes page', function () {
    Theme::factory()->create(['store_id' => $this->store->id, 'name' => 'My Theme']);

    $this->actingAs($this->user)
        ->withSession(['current_store_id' => $this->store->id])
        ->get('/admin/themes')
        ->assertOk()
        ->assertSee('My Theme');
});

test('creates a new draft theme', function () {
    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->set('newThemeName', 'Fresh Theme')
        ->call('createTheme')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    $theme = Theme::query()->where('name', 'Fresh Theme')->sole();

    expect($theme->status)->toBe(ThemeStatus::Draft)
        ->and($theme->settings()->exists())->toBeTrue();
});

test('publishing a theme demotes the others', function () {
    $old = Theme::factory()->published()->create(['store_id' => $this->store->id]);
    $new = Theme::factory()->create(['store_id' => $this->store->id]);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->call('publishTheme', $new->id)
        ->assertDispatched('toast');

    expect($new->refresh()->status)->toBe(ThemeStatus::Published)
        ->and($new->published_at)->not->toBeNull()
        ->and($old->refresh()->status)->toBe(ThemeStatus::Draft);
});

test('duplicating a theme copies settings and files', function () {
    $theme = Theme::factory()->create(['store_id' => $this->store->id, 'name' => 'Base']);
    ThemeSettings::factory()->create([
        'theme_id' => $theme->id,
        'settings_json' => ['hero' => ['heading' => 'Hello']],
    ]);
    ThemeFile::factory()->count(2)->create(['theme_id' => $theme->id]);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->call('duplicateTheme', $theme->id)
        ->assertDispatched('toast');

    $copy = Theme::query()->where('name', 'Base (copy)')->sole();

    expect($copy->status)->toBe(ThemeStatus::Draft)
        ->and($copy->files)->toHaveCount(2)
        ->and($copy->settings->settings_json)->toBe(['hero' => ['heading' => 'Hello']]);
});

test('the published theme cannot be deleted', function () {
    $published = Theme::factory()->published()->create(['store_id' => $this->store->id]);
    $draft = Theme::factory()->create(['store_id' => $this->store->id]);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->call('confirmDelete', $published->id)
        ->call('deleteTheme')
        ->assertDispatched('toast', type: 'error');

    $this->assertDatabaseHas('themes', ['id' => $published->id]);

    Livewire::test(Index::class)
        ->call('confirmDelete', $draft->id)
        ->call('deleteTheme')
        ->assertDispatched('toast', type: 'success');

    $this->assertDatabaseMissing('themes', ['id' => $draft->id]);
});

test('editor saves settings and invalidates the cache', function () {
    $theme = Theme::factory()->published()->create(['store_id' => $this->store->id]);

    // Pre-populate the resolved settings cache for this store.
    Cache::put("theme_settings:{$this->store->id}", ['cached' => true], 300);

    Livewire::actingAs($this->user);
    Livewire::test(Editor::class, ['theme' => $theme])
        ->set('settings.hero.heading', 'Welcome to the shop')
        ->set('settings.colors.primary', '#ff0000')
        ->set('featuredCollectionHandles', 'summer, new-arrivals')
        ->call('save')
        ->assertDispatched('toast');

    $settings = $theme->settings()->sole()->settings_json;

    expect($settings['hero']['heading'])->toBe('Welcome to the shop')
        ->and($settings['colors']['primary'])->toBe('#ff0000')
        ->and($settings['featured_collections']['collection_handles'])->toBe(['summer', 'new-arrivals'])
        ->and(Cache::has("theme_settings:{$this->store->id}"))->toBeFalse();
});

test('editor save and publish publishes the theme', function () {
    $theme = Theme::factory()->create(['store_id' => $this->store->id]);

    Livewire::actingAs($this->user);
    Livewire::test(Editor::class, ['theme' => $theme])
        ->call('saveAndPublish')
        ->assertDispatched('toast');

    expect($theme->refresh()->status)->toBe(ThemeStatus::Published);
});

test('staff cannot manage themes', function () {
    $staff = $this->createUserWithRole($this->store, 'staff');
    $theme = Theme::factory()->create(['store_id' => $this->store->id]);

    $this->actingAs($staff)
        ->withSession(['current_store_id' => $this->store->id])
        ->get('/admin/themes')
        ->assertForbidden();

    $this->actingAs($staff)
        ->withSession(['current_store_id' => $this->store->id])
        ->get("/admin/themes/{$theme->id}/editor")
        ->assertForbidden();
});
