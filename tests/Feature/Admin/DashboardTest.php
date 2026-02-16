<?php

beforeEach(function () {
    $this->ctx = createStoreContext();
});

it('renders the admin dashboard', function () {
    $response = actingAsAdmin($this->ctx['user'])
        ->withSession(['current_store_id' => $this->ctx['store']->id])
        ->get('/admin');

    $response->assertStatus(200);
});

it('restricts dashboard to authenticated admins', function () {
    $response = $this->get('/admin');
    $response->assertRedirect();
});
