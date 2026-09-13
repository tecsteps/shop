<?php

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('returns 501 for oauth endpoints', function () {
    ['user' => $user] = $this->createStoreContext();
    $this->actingAs($user);

    $this->get('/oauth/authorize?client_id=x&redirect_uri=https://app.example.com/cb&response_type=code&scope=read-products&state=y')
        ->assertStatus(501);

    $this->postJson('/oauth/token', ['grant_type' => 'authorization_code'])->assertStatus(501);
});

it('returns 501 for app api endpoints', function () {
    $this->getJson('/api/apps/v1/stores/1/products')->assertStatus(501);
    $this->getJson('/api/apps/v1/stores/1/orders')->assertStatus(501);
    $this->getJson('/api/apps/v1/stores/1/customers')->assertStatus(501);
});
