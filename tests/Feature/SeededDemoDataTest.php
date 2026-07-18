<?php

use App\Models\Product;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('seeds the Acme Fashion demo foundation', function () {
    $this->seed();

    $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $admin = User::query()->where('email', 'admin@acme.test')->firstOrFail();

    expect($store->name)->toBe('Acme Fashion')
        ->and($store->default_currency)->toBe('EUR')
        ->and(StoreDomain::query()->where('store_id', $store->id)->where('hostname', 'acme-fashion.test')->exists())->toBeTrue()
        ->and(Hash::check('password', $admin->password))->toBeTrue()
        ->and(Product::query()->where('store_id', $store->id)->where('handle', 'classic-cotton-t-shirt')->exists())->toBeTrue();
});
