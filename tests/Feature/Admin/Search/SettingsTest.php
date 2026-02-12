<?php

use App\Livewire\Admin\Search\Settings;
use App\Models\SearchSettings;
use App\Models\Store;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function searchSettingsAdmin(): array
{
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    app()->instance('current_store', $store);

    return [$user, $store];
}

test('it renders the search settings page', function () {
    [$user, $store] = searchSettingsAdmin();

    Livewire::actingAs($user)
        ->test(Settings::class)
        ->assertSee('Search')
        ->assertSuccessful();
});

test('it pre-populates from existing search settings', function () {
    [$user, $store] = searchSettingsAdmin();

    SearchSettings::create([
        'store_id' => $store->id,
        'synonyms_json' => [['sneaker', 'shoe']],
        'stop_words_json' => ['the', 'and'],
        'boost_fields_json' => ['title' => 2],
    ]);

    $component = Livewire::actingAs($user)
        ->test(Settings::class);

    $synonyms = json_decode($component->get('synonymsJson'), true);
    $stopWords = json_decode($component->get('stopWordsJson'), true);
    $boostFields = json_decode($component->get('boostFieldsJson'), true);

    expect($synonyms)->toBe([['sneaker', 'shoe']])
        ->and($stopWords)->toBe(['the', 'and'])
        ->and($boostFields)->toBe(['title' => 2]);
});

test('it saves search settings', function () {
    [$user, $store] = searchSettingsAdmin();

    Livewire::actingAs($user)
        ->test(Settings::class)
        ->set('synonymsJson', json_encode([['boots', 'footwear']]))
        ->set('stopWordsJson', json_encode(['a', 'the']))
        ->set('boostFieldsJson', json_encode(['title' => 3]))
        ->call('save')
        ->assertDispatched('toast');

    $settings = SearchSettings::where('store_id', $store->id)->first();

    expect($settings)->not->toBeNull()
        ->and($settings->synonyms_json)->toBe([['boots', 'footwear']])
        ->and($settings->stop_words_json)->toBe(['a', 'the'])
        ->and($settings->boost_fields_json)->toBe(['title' => 3]);
});

test('it updates existing search settings via upsert', function () {
    [$user, $store] = searchSettingsAdmin();

    SearchSettings::create([
        'store_id' => $store->id,
        'synonyms_json' => [],
        'stop_words_json' => [],
        'boost_fields_json' => [],
    ]);

    Livewire::actingAs($user)
        ->test(Settings::class)
        ->set('synonymsJson', json_encode([['shirt', 'top']]))
        ->call('save')
        ->assertDispatched('toast');

    $settings = SearchSettings::where('store_id', $store->id)->first();
    expect($settings->synonyms_json)->toBe([['shirt', 'top']]);
});

test('it validates JSON fields are valid JSON', function () {
    [$user, $store] = searchSettingsAdmin();

    Livewire::actingAs($user)
        ->test(Settings::class)
        ->set('synonymsJson', 'not valid json')
        ->call('save')
        ->assertHasErrors(['synonymsJson']);
});

test('it defaults to empty arrays when no settings exist', function () {
    [$user, $store] = searchSettingsAdmin();

    $component = Livewire::actingAs($user)
        ->test(Settings::class);

    expect($component->get('synonymsJson'))->toBe('[]')
        ->and($component->get('stopWordsJson'))->toBe('[]')
        ->and($component->get('boostFieldsJson'))->toBe('[]');
});
