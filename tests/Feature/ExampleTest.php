<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the storefront home page for a resolved tenant', function () {
    $context = createStoreContext();
    $response = $this->get("http://{$context['domain']->hostname}/");

    $response->assertStatus(200);
});
