<?php

it('returns a successful response', function () {
    $ctx = createStoreContext('example.test');

    $response = $this->get('http://example.test/');

    $response->assertStatus(200);
});
