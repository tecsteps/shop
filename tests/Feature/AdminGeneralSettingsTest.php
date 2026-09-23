<?php

use App\Livewire\Admin\Settings\General;
use App\Models\Store;
use App\Models\User;
use Livewire\Livewire;

function generalSettingsOwner(Store $store): User
{
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    app()->instance('current_store', $store);

    return $user;
}

it('saves locale and timezone defaults while showing the immutable store handle', function () {
    $store = shopStore('general-settings.test');
    $user = generalSettingsOwner($store);

    Livewire::actingAs($user)->test(General::class)
        ->assertSet('storeHandle', $store->handle)
        ->assertSet('defaultLocale', 'en')
        ->assertSeeHtml('id="settings-store-handle"')
        ->assertSeeHtml('disabled')
        ->assertSee('The store handle cannot be changed after creation.')
        ->set('storeName', 'Updated Store')
        ->set('contactEmail', 'support@example.test')
        ->set('defaultCurrency', 'USD')
        ->set('defaultLocale', 'de')
        ->set('timezone', 'America/New_York')
        ->call('save')
        ->assertHasNoErrors();

    expect($store->fresh()->name)->toBe('Updated Store')
        ->and($store->fresh()->handle)->toBe($store->handle)
        ->and($store->fresh()->default_currency)->toBe('USD')
        ->and($store->fresh()->default_locale)->toBe('de')
        ->and($store->fresh()->timezone)->toBe('America/New_York');
});

it('rejects invalid locale and timezone values', function () {
    $store = shopStore('general-settings-invalid.test');
    $user = generalSettingsOwner($store);

    Livewire::actingAs($user)->test(General::class)
        ->set('contactEmail', 'support@example.test')
        ->set('defaultLocale', 'zzzzzzz')
        ->set('timezone', 'Mars/Nope')
        ->call('save')
        ->assertHasErrors(['defaultLocale', 'timezone']);

    expect($store->fresh()->default_locale)->toBe('en')
        ->and($store->fresh()->timezone)->toBe('Europe/Berlin');
});

it('shows navigable checkout and notifications settings tabs', function () {
    $store = shopStore('general-settings-tabs.test');
    $user = generalSettingsOwner($store);

    Livewire::withQueryParams(['tab' => 'checkout'])
        ->actingAs($user)
        ->test(General::class)
        ->assertSee('Checkout settings')
        ->assertSee('Manage shipping')
        ->assertSee('Manage taxes')
        ->assertSee('Notifications')
        ->assertSee('tab=notifications');

    Livewire::withQueryParams(['tab' => 'notifications'])
        ->actingAs($user)
        ->test(General::class)
        ->assertSee('Notification settings')
        ->assertSee('No additional notification preferences are available for this store yet.')
        ->assertSee('General');
});
