<?php

use App\Enums\StoreDomainType;
use App\Enums\StoreStatus;
use App\Enums\StoreUserRole;

it('StoreStatus is a backed string enum with correct cases', function () {
    expect(StoreStatus::Active->value)->toBe('active');
    expect(StoreStatus::Suspended->value)->toBe('suspended');
    expect(StoreStatus::cases())->toHaveCount(2);
});

it('StoreUserRole is a backed string enum with correct cases', function () {
    expect(StoreUserRole::Owner->value)->toBe('owner');
    expect(StoreUserRole::Admin->value)->toBe('admin');
    expect(StoreUserRole::Staff->value)->toBe('staff');
    expect(StoreUserRole::Support->value)->toBe('support');
    expect(StoreUserRole::cases())->toHaveCount(4);
});

it('StoreDomainType is a backed string enum with correct cases', function () {
    expect(StoreDomainType::Storefront->value)->toBe('storefront');
    expect(StoreDomainType::Admin->value)->toBe('admin');
    expect(StoreDomainType::Api->value)->toBe('api');
    expect(StoreDomainType::cases())->toHaveCount(3);
});
