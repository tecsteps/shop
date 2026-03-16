<?php

it('returns a successful response', function () {
    $context = createStoreContext();

    $response = $this->get('http://acme-fashion.test/');

    $response->assertStatus(200);
});
