<?php

use App\Models\Organization;
use App\Models\Store;

it('has many stores', function () {
    $organization = Organization::factory()->create();
    Store::factory()->count(2)->create(['organization_id' => $organization->id]);

    expect($organization->stores)->toHaveCount(2);
});

it('factory creates valid records', function () {
    $organization = Organization::factory()->create();

    expect($organization->name)->not->toBeEmpty();
    expect($organization->billing_email)->not->toBeEmpty();
    expect(filter_var($organization->billing_email, FILTER_VALIDATE_EMAIL))->not->toBeFalse();
});
