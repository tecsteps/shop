<?php

it('returns a successful response for storefront with valid host', function () {
    $ctx = createStoreContext('example.test');

    $response = $this->get('http://example.test/');

    $response->assertStatus(200);
});
