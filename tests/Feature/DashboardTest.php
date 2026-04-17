<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $context = createStoreContext();
    $this->actingAs($context['user']);

    $response = $this->get(route('admin.dashboard'));
    $response->assertOk();
});
