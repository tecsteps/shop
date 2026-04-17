<?php

use App\Enums\StoreDomainType;
use App\Models\Store;
use App\Models\StoreDomain;

it('belongs to a store', function () {
    $domain = StoreDomain::factory()->create();

    expect($domain->store)->toBeInstanceOf(Store::class);
});

it('factory creates valid records', function () {
    $domain = StoreDomain::factory()->create();

    expect($domain->hostname)->not->toBeEmpty();
    expect($domain->type)->toBeInstanceOf(StoreDomainType::class);
});

it('casts type to StoreDomainType enum', function () {
    $domain = StoreDomain::factory()->create(['type' => 'storefront']);

    expect($domain->type)->toBeInstanceOf(StoreDomainType::class);
    expect($domain->type)->toBe(StoreDomainType::Storefront);
});
