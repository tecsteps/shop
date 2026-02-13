<?php

it('returns a successful response', function () {
    $context = createStoreContext();

    $response = $this->get('http://'.$context['domain']->hostname.'/');

    $response->assertStatus(200);
});
