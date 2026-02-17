<?php

it('returns a successful response', function () {
    $ctx = createStoreContext();

    $response = $this->get('http://acme-fashion.test/');

    $response->assertStatus(200);
});
