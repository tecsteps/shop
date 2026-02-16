<?php

beforeEach(function () {
    $this->ctx = createStoreContext();
});

it('renders the settings page', function () {
    $response = actingAsAdmin($this->ctx['user'])
        ->withSession(['current_store_id' => $this->ctx['store']->id])
        ->get('/admin/settings');

    $response->assertStatus(200);
});

it('renders shipping settings', function () {
    $response = actingAsAdmin($this->ctx['user'])
        ->withSession(['current_store_id' => $this->ctx['store']->id])
        ->get('/admin/settings/shipping');

    $response->assertStatus(200);
});

it('renders tax settings', function () {
    $response = actingAsAdmin($this->ctx['user'])
        ->withSession(['current_store_id' => $this->ctx['store']->id])
        ->get('/admin/settings/taxes');

    $response->assertStatus(200);
});

it('restricts settings to authenticated admins', function () {
    $response = $this->get('/admin/settings');
    $response->assertRedirect();
});
