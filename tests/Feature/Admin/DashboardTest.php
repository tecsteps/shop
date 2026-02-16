<?php

beforeEach(function () {
    $this->ctx = createStoreContext();
});

it('renders the admin dashboard', function () {
    $response = adminRequest($this->ctx)->get('/admin');

    $response->assertStatus(200);
});

it('restricts dashboard to authenticated admins', function () {
    $response = $this->get('/admin');
    $response->assertRedirect();
});
