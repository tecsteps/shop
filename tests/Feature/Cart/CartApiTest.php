<?php

it('returns 404 for nonexistent cart', function () {
    $ctx = createStoreContext();

    $response = $this->withHeader('Host', 'shop.test')
        ->getJson('/api/storefront/v1/carts/999');

    $response->assertStatus(404);
});
