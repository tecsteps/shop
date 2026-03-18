<?php

use App\Enums\ThemeStatus;
use App\Livewire\Admin\Themes\Editor as ThemeEditor;
use App\Livewire\Admin\Themes\Index as ThemeIndex;
use App\Models\Theme;
use App\Models\ThemeSettings;
use Livewire\Livewire;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->actingAs($this->ctx['user']);
    session(['current_store_id' => $this->ctx['store']->id]);
});

it('requires authentication to access themes page', function () {
    auth()->logout();
    $this->get('/admin/themes')->assertRedirect('/admin/login');
});

it('renders the themes index page', function () {
    $this->get('/admin/themes')
        ->assertStatus(200)
        ->assertSee('Themes');
});

it('lists themes for the current store', function () {
    Theme::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'name' => 'My Custom Theme',
    ]);

    $component = Livewire::test(ThemeIndex::class);
    $component->assertSee('My Custom Theme');
});

it('publishes a theme and unpublishes others', function () {
    $published = Theme::factory()->published()->create([
        'store_id' => $this->ctx['store']->id,
        'name' => 'Old Theme',
    ]);

    $draft = Theme::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'name' => 'New Theme',
    ]);

    $component = Livewire::test(ThemeIndex::class);
    $component->call('publish', $draft->id);

    $published->refresh();
    $draft->refresh();

    expect($published->status)->toBe(ThemeStatus::Draft);
    expect($draft->status)->toBe(ThemeStatus::Published);
    expect($draft->published_at)->not->toBeNull();
});

it('duplicates a theme', function () {
    $theme = Theme::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'name' => 'Original',
    ]);

    ThemeSettings::create([
        'theme_id' => $theme->id,
        'settings_json' => ['hero_heading' => 'Test'],
    ]);

    $component = Livewire::test(ThemeIndex::class);
    $component->call('duplicate', $theme->id);

    $copy = Theme::where('store_id', $this->ctx['store']->id)
        ->where('name', 'Original (Copy)')
        ->first();

    expect($copy)->not->toBeNull();
    expect($copy->status)->toBe(ThemeStatus::Draft);
    expect($copy->settings->settings_json)->toBe(['hero_heading' => 'Test']);
});

it('deletes a draft theme', function () {
    $theme = Theme::factory()->create([
        'store_id' => $this->ctx['store']->id,
    ]);

    $component = Livewire::test(ThemeIndex::class);
    $component->call('deleteTheme', $theme->id);

    expect(Theme::find($theme->id))->toBeNull();
});

it('prevents deleting the published theme', function () {
    $theme = Theme::factory()->published()->create([
        'store_id' => $this->ctx['store']->id,
    ]);

    $component = Livewire::test(ThemeIndex::class);
    $component->call('deleteTheme', $theme->id);

    $component->assertDispatched('toast', fn ($name, $data) => $data['type'] === 'error');
    expect(Theme::find($theme->id))->not->toBeNull();
});

it('shows empty state when no themes exist', function () {
    $component = Livewire::test(ThemeIndex::class);
    $component->assertSee('No themes found');
});

it('renders the theme editor', function () {
    $theme = Theme::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'name' => 'Test Theme',
    ]);

    ThemeSettings::create([
        'theme_id' => $theme->id,
        'settings_json' => [],
    ]);

    $this->get("/admin/themes/{$theme->id}/editor")
        ->assertStatus(200)
        ->assertSee('Test Theme');
});

it('loads theme settings into the editor', function () {
    $theme = Theme::factory()->create([
        'store_id' => $this->ctx['store']->id,
    ]);

    ThemeSettings::create([
        'theme_id' => $theme->id,
        'settings_json' => ['hero_heading' => 'Custom Heading'],
    ]);

    $component = Livewire::test(ThemeEditor::class, ['theme' => $theme]);
    expect($component->get('settings.hero_heading'))->toBe('Custom Heading');
});

it('saves theme settings', function () {
    $theme = Theme::factory()->create([
        'store_id' => $this->ctx['store']->id,
    ]);

    ThemeSettings::create([
        'theme_id' => $theme->id,
        'settings_json' => [],
    ]);

    $component = Livewire::test(ThemeEditor::class, ['theme' => $theme]);
    $component->set('settings.hero_heading', 'Updated Heading');
    $component->call('save');

    $theme->refresh();
    expect($theme->settings->settings_json['hero_heading'])->toBe('Updated Heading');
});

it('switches between sections in the editor', function () {
    $theme = Theme::factory()->create([
        'store_id' => $this->ctx['store']->id,
    ]);

    ThemeSettings::create([
        'theme_id' => $theme->id,
        'settings_json' => [],
    ]);

    $component = Livewire::test(ThemeEditor::class, ['theme' => $theme]);
    expect($component->get('selectedSection'))->toBe('announcement_bar');

    $component->call('selectSection', 'hero');
    expect($component->get('selectedSection'))->toBe('hero');
});

it('prevents accessing another store theme editor', function () {
    $otherStore = \App\Models\Store::factory()->create();
    $theme = Theme::factory()->create([
        'store_id' => $otherStore->id,
    ]);

    $component = Livewire::test(ThemeEditor::class, ['theme' => $theme]);
    $component->assertStatus(404);
});
