<?php

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('the legacy dashboard route redirects to the admin home', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect('/admin');
});

test('authenticated users land on the admin home from the legacy dashboard route', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertRedirect('/admin');
});
