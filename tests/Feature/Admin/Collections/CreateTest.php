<?php

use App\Livewire\Admin\Collections\Create;
use App\Models\Collection;
use App\Models\Store;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function collectionCreateAdmin(): array
{
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    app()->instance('current_store', $store);

    return [$user, $store];
}

test('it renders the create collection page', function () {
    [$user, $store] = collectionCreateAdmin();

    Livewire::actingAs($user)
        ->test(Create::class)
        ->assertSee('Create collection')
        ->assertSuccessful();
});

test('it auto-generates handle from title', function () {
    [$user, $store] = collectionCreateAdmin();

    Livewire::actingAs($user)
        ->test(Create::class)
        ->set('title', 'Summer Collection')
        ->assertSet('handle', 'summer-collection');
});

test('it creates a collection', function () {
    [$user, $store] = collectionCreateAdmin();

    Livewire::actingAs($user)
        ->test(Create::class)
        ->set('title', 'New Summer Collection')
        ->set('descriptionHtml', '<p>Summer items</p>')
        ->set('type', 'manual')
        ->set('status', 'draft')
        ->call('save')
        ->assertDispatched('toast')
        ->assertRedirect();

    $collection = Collection::where('title', 'New Summer Collection')->first();

    expect($collection)->not->toBeNull()
        ->and($collection->store_id)->toBe($store->id)
        ->and($collection->handle)->toBe('new-summer-collection')
        ->and($collection->description_html)->toBe('<p>Summer items</p>')
        ->and($collection->type->value)->toBe('manual')
        ->and($collection->status->value)->toBe('draft');
});

test('it validates required fields', function () {
    [$user, $store] = collectionCreateAdmin();

    Livewire::actingAs($user)
        ->test(Create::class)
        ->set('title', '')
        ->call('save')
        ->assertHasErrors(['title']);
});

test('it validates type values', function () {
    [$user, $store] = collectionCreateAdmin();

    Livewire::actingAs($user)
        ->test(Create::class)
        ->set('title', 'Test')
        ->set('type', 'invalid')
        ->call('save')
        ->assertHasErrors(['type']);
});
