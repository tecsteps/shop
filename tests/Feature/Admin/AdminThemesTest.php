<?php

use App\Enums\ThemeStatus;
use App\Livewire\Admin\Themes\Editor;
use App\Livewire\Admin\Themes\Index;
use App\Models\Theme;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ctx = createStoreContext();
});

it('requires authentication for themes', function () {
    $this->get(route('admin.themes.index'))
        ->assertRedirect(route('admin.login'));
});

it('renders the themes index', function () {
    $this->actingAs($this->ctx['user']);

    $this->get(route('admin.themes.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('lists themes', function () {
    Theme::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'name' => 'Default Theme',
    ]);

    Livewire::actingAs($this->ctx['user'])
        ->test(Index::class)
        ->assertSee('Default Theme');
});

it('publishes a theme', function () {
    $theme = Theme::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'name' => 'My Theme',
        'status' => ThemeStatus::Draft,
        'is_active' => false,
    ]);

    Livewire::actingAs($this->ctx['user'])
        ->test(Index::class)
        ->call('publishTheme', $theme->id)
        ->assertDispatched('toast');

    $theme->refresh();
    expect($theme->is_active)->toBeTrue();
    expect($theme->status)->toBe(ThemeStatus::Published);
});

it('duplicates a theme', function () {
    $theme = Theme::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'name' => 'Original',
    ]);

    Livewire::actingAs($this->ctx['user'])
        ->test(Index::class)
        ->call('duplicateTheme', $theme->id)
        ->assertDispatched('toast');

    expect(Theme::where('name', 'Original (Copy)')->exists())->toBeTrue();
});

it('prevents deleting active theme', function () {
    $theme = Theme::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'is_active' => true,
    ]);

    Livewire::actingAs($this->ctx['user'])
        ->test(Index::class)
        ->call('deleteTheme', $theme->id)
        ->assertDispatched('toast');

    expect(Theme::find($theme->id))->not->toBeNull();
});

it('deletes an inactive theme', function () {
    $theme = Theme::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'is_active' => false,
    ]);

    Livewire::actingAs($this->ctx['user'])
        ->test(Index::class)
        ->call('deleteTheme', $theme->id)
        ->assertDispatched('toast');

    expect(Theme::find($theme->id))->toBeNull();
});

it('renders the theme editor', function () {
    $theme = Theme::factory()->create([
        'store_id' => $this->ctx['store']->id,
    ]);

    $this->actingAs($this->ctx['user']);

    $this->get(route('admin.themes.editor', $theme))
        ->assertOk()
        ->assertSeeLivewire(Editor::class);
});

it('loads theme sections in the editor', function () {
    $theme = Theme::factory()->create([
        'store_id' => $this->ctx['store']->id,
    ]);

    Livewire::actingAs($this->ctx['user'])
        ->test(Editor::class, ['theme' => $theme])
        ->assertSet('selectedSection', 'header');
});

it('saves theme settings from the editor', function () {
    $theme = Theme::factory()->create([
        'store_id' => $this->ctx['store']->id,
    ]);

    Livewire::actingAs($this->ctx['user'])
        ->test(Editor::class, ['theme' => $theme])
        ->set('sectionSettings.logo_text', 'My Store')
        ->call('save')
        ->assertDispatched('toast');

    $theme->refresh();
    $settings = $theme->settings?->settings_json ?? [];
    expect($settings['values']['header']['logo_text'])->toBe('My Store');
});
