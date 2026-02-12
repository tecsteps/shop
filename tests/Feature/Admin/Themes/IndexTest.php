<?php

use App\Livewire\Admin\Themes\Index;
use App\Models\Store;
use App\Models\Theme;
use App\Models\ThemeSettings;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function themesAdmin(): array
{
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    app()->instance('current_store', $store);

    return [$user, $store];
}

test('it renders the themes index page', function () {
    [$user, $store] = themesAdmin();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('Themes')
        ->assertSuccessful();
});

test('it lists themes for the current store', function () {
    [$user, $store] = themesAdmin();

    Theme::factory()->create([
        'store_id' => $store->id,
        'name' => 'Dawn Theme',
    ]);

    Theme::factory()->draft()->create([
        'store_id' => $store->id,
        'name' => 'Custom Theme',
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('Dawn Theme')
        ->assertSee('Custom Theme');
});

test('it activates a theme and deactivates others', function () {
    [$user, $store] = themesAdmin();

    $published = Theme::factory()->create([
        'store_id' => $store->id,
        'name' => 'Current Theme',
        'status' => 'published',
    ]);

    $draft = Theme::factory()->draft()->create([
        'store_id' => $store->id,
        'name' => 'New Theme',
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('activate', $draft->id)
        ->assertDispatched('toast');

    $published->refresh();
    $draft->refresh();

    expect($published->status->value)->toBe('draft')
        ->and($draft->status->value)->toBe('published');
});

test('it deactivates a theme', function () {
    [$user, $store] = themesAdmin();

    $theme = Theme::factory()->create([
        'store_id' => $store->id,
        'status' => 'published',
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('deactivate', $theme->id)
        ->assertDispatched('toast');

    $theme->refresh();
    expect($theme->status->value)->toBe('draft');
});

test('it duplicates a theme', function () {
    [$user, $store] = themesAdmin();

    $theme = Theme::factory()->create([
        'store_id' => $store->id,
        'name' => 'Original Theme',
        'status' => 'published',
    ]);

    ThemeSettings::factory()->create([
        'theme_id' => $theme->id,
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('duplicateTheme', $theme->id)
        ->assertDispatched('toast');

    $copy = Theme::where('store_id', $store->id)
        ->where('name', 'Original Theme (Copy)')
        ->first();

    expect($copy)->not->toBeNull()
        ->and($copy->status->value)->toBe('draft')
        ->and($copy->settings)->not->toBeNull();
});

test('it deletes a theme', function () {
    [$user, $store] = themesAdmin();

    $theme = Theme::factory()->draft()->create([
        'store_id' => $store->id,
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('deleteTheme', $theme->id)
        ->assertDispatched('toast');

    expect(Theme::find($theme->id))->toBeNull();
});

test('it does not show themes from other stores', function () {
    [$user, $store] = themesAdmin();

    $otherStore = Store::factory()->create();

    Theme::factory()->create([
        'store_id' => $store->id,
        'name' => 'My Theme',
    ]);

    Theme::factory()->create([
        'store_id' => $otherStore->id,
        'name' => 'Other Theme',
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('My Theme')
        ->assertDontSee('Other Theme');
});
