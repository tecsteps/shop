<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('returns a successful response', function () {
    $this->seed();

    $response = $this->withServerVariables(['HTTP_HOST' => 'shop.test'])->get('/');

    $response->assertStatus(200);
});
