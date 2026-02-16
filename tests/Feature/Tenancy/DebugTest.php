<?php

use Illuminate\Support\Facades\Cache;

it('debug hostname resolution', function () {
    $ctx = createStoreContext();

    $domain = \App\Models\StoreDomain::where('domain', 'test-store.test')->first();
    expect($domain)->not->toBeNull();
    expect($domain->store_id)->toBe($ctx['store']->id);

    Cache::flush();

    $response = $this->withHeader('Host', 'test-store.test')
        ->get('/');

    $response->assertStatus(200);
});
