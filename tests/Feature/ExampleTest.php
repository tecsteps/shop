<?php

use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\Theme;
use App\Models\ThemeSettings;

it('returns a successful response', function () {
    $store = Store::factory()->create();
    StoreDomain::factory()->create([
        'store_id' => $store->id,
        'hostname' => 'example-store.test',
        'type' => 'storefront',
    ]);
    $theme = Theme::factory()->published()->create(['store_id' => $store->id]);
    ThemeSettings::factory()->create(['theme_id' => $theme->id]);

    $response = $this->get('https://example-store.test/');

    $response->assertStatus(200);
});
