<?php

use Illuminate\Support\Facades\Cache;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('returns a successful storefront response', function () {
    Cache::flush();
    $this->seed();

    $this->get('http://shop.test/')
        ->assertOk();
});
