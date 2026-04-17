<?php

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guests are redirected from dashboard', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect('/admin');
});

test('authenticated users are redirected from dashboard to admin', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertRedirect('/admin');
});
