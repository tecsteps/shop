<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::query()->where('email', 'admin@acme.test')->firstOrFail();
    $this->actingAs($user);

    $response = $this->get(route('admin.dashboard'));
    $response->assertOk();
});
