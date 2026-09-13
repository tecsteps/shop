<?php

use App\Enums\ThemeStatus;
use App\Livewire\Admin\Themes\Editor;
use App\Livewire\Admin\Themes\Index;
use App\Models\Theme;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    ['store' => $this->store, 'user' => $this->user] = $this->createStoreContext();
    $this->actingAsAdmin($this->user, $this->store);
});

it('lists themes scoped to the current store', function () {
    Theme::factory()->create(['store_id' => $this->store->getKey(), 'name' => 'Default Theme']);

    $this->get(route('admin.themes.index'))
        ->assertOk()
        ->assertSee('Default Theme');
});

it('publishes a theme and demotes the previous one', function () {
    Theme::factory()->create(['store_id' => $this->store->getKey(), 'status' => ThemeStatus::Published]);
    $draft = Theme::factory()->create(['store_id' => $this->store->getKey(), 'status' => ThemeStatus::Draft]);

    Livewire::test(Index::class)
        ->call('publishTheme', $draft->getKey())
        ->assertDispatched('toast');

    expect($draft->refresh()->status)->toBe(ThemeStatus::Published)
        ->and(Theme::query()->where('store_id', $this->store->getKey())->where('status', ThemeStatus::Published)->count())->toBe(1);
});

it('duplicates a theme with its settings', function () {
    $theme = Theme::factory()->create(['store_id' => $this->store->getKey(), 'status' => ThemeStatus::Draft]);
    $theme->themeSetting()->create(['settings_json' => ['primary_color' => '#000000']]);

    Livewire::test(Index::class)
        ->call('duplicateTheme', $theme->getKey())
        ->assertDispatched('toast');

    $copy = Theme::query()->where('store_id', $this->store->getKey())->where('name', $theme->name.' (copy)')->firstOrFail();

    expect($copy->status)->toBe(ThemeStatus::Draft)
        ->and($copy->themeSetting->settings_json['primary_color'])->toBe('#000000');
});

it('refuses to delete the published theme', function () {
    $theme = Theme::factory()->create(['store_id' => $this->store->getKey(), 'status' => ThemeStatus::Published]);

    Livewire::test(Index::class)
        ->call('confirmDelete', $theme->getKey())
        ->call('deleteTheme')
        ->assertDispatched('toast');

    expect(Theme::query()->whereKey($theme->getKey())->exists())->toBeTrue();
});

it('edits section settings in the theme editor', function () {
    $theme = Theme::factory()->create(['store_id' => $this->store->getKey(), 'status' => ThemeStatus::Draft]);

    $this->get(route('admin.themes.editor', $theme))->assertOk();

    Livewire::test(Editor::class, ['theme' => $theme])
        ->call('selectSection', 'hero')
        ->call('updateSetting', 'heading', 'Hello World')
        ->call('save')
        ->assertDispatched('toast');

    expect($theme->themeSetting()->first()->settings_json['hero']['heading'])->toBe('Hello World');
});

it('returns 404 for a theme from another store', function () {
    ['store' => $otherStore] = $this->createStoreContext();
    $theme = Theme::factory()->create(['store_id' => $otherStore->getKey()]);

    $this->get(route('admin.themes.editor', $theme))->assertNotFound();
});
