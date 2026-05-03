<?php

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns a successful response', function () {
    $this->seed(DatabaseSeeder::class);

    $response = $this->get('http://shop.test/');

    $response->assertOk();
});
