<?php

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\StoreUser;
use App\Models\User;

it('is a custom Pivot class with role attribute', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();

    $store->users()->attach($user->id, ['role' => 'admin']);

    $pivot = $user->stores->first()->pivot;

    expect($pivot)->toBeInstanceOf(StoreUser::class);
    expect($pivot->role)->toBe(StoreUserRole::Admin);
});
