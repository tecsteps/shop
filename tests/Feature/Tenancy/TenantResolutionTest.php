<?php

beforeEach(function () {
    //
});

it('resolves store from hostname for storefront requests', function () {
    $ctx = createStoreContext();

    $response = $this->withHeader('Host', 'shop.test')
        ->get('/');

    $response->assertStatus(200);
});

it('returns 404 for unknown hostname', function () {
    $response = $this->withHeader('Host', 'nonexistent.test')
        ->get('/');

    $response->assertStatus(404);
});

it('resolves store from session for admin requests', function () {
    $ctx = createStoreContext();

    $response = actingAsAdmin($ctx['user'])
        ->withSession(['current_store_id' => $ctx['store']->id])
        ->get('/admin');

    $response->assertStatus(200);
});

it('redirects unauthenticated users to login', function () {
    $response = $this->get('/admin');
    $response->assertRedirect();
});
