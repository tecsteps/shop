<?php

use App\Models\Store;

it('falls back to the first store in testing when hostname resolution misses', function () {
    $firstStore = Store::factory()->create();
    Store::factory()->create();

    $response = $this->withServerVariables([
        'HTTP_HOST' => 'unmapped-store.test',
    ])->get('/');

    $response->assertOk()
        ->assertViewHas('store', fn (Store $store): bool => $store->id === $firstStore->id);
});

it('blocks admin access when authenticated user is not assigned to current store', function () {
    $allowedStore = Store::factory()->create();
    $blockedStore = Store::factory()->create();

    $user = createStoreAdmin($allowedStore);

    $this->actingAs($user)
        ->withSession(['current_store_id' => $blockedStore->id])
        ->get('/admin')
        ->assertForbidden();
});
