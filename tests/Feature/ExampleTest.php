<?php

it('returns a successful response for the storefront home', function () {
    $context = createStoreContext();
    $hostname = $context['domain']->hostname;

    $response = $this->get("http://{$hostname}/");

    $response->assertSuccessful();
});
