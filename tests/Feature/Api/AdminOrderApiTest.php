<?php

it('returns 401 without token', function () {
    $ctx = createStoreContext();

    $response = $this->getJson('/api/admin/v1/stores/'.$ctx['store']->id.'/orders');

    $response->assertUnauthorized();
})->skip(! class_exists(\Laravel\Sanctum\SanctumServiceProvider::class), 'Sanctum not installed');
