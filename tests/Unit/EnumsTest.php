<?php

use App\Enums\CheckoutStatus;
use App\Enums\DiscountValueType;
use App\Enums\FinancialStatus;
use App\Enums\StoreUserRole;

test('store user role exposes lowercase string values', function () {
    expect(StoreUserRole::cases())->toHaveCount(4)
        ->and(StoreUserRole::Owner->value)->toBe('owner')
        ->and(StoreUserRole::Admin->value)->toBe('admin')
        ->and(StoreUserRole::Staff->value)->toBe('staff')
        ->and(StoreUserRole::Support->value)->toBe('support')
        ->and(StoreUserRole::from('support'))->toBe(StoreUserRole::Support);
});

test('checkout status uses payment_selected', function () {
    expect(CheckoutStatus::cases())->toHaveCount(6)
        ->and(CheckoutStatus::PaymentSelected->value)->toBe('payment_selected')
        ->and(CheckoutStatus::ShippingSelected->value)->toBe('shipping_selected');
});

test('discount value type values', function () {
    expect(DiscountValueType::Percent->value)->toBe('percent')
        ->and(DiscountValueType::Fixed->value)->toBe('fixed')
        ->and(DiscountValueType::FreeShipping->value)->toBe('free_shipping');
});

test('financial status values', function () {
    expect(FinancialStatus::cases())->toHaveCount(6)
        ->and(FinancialStatus::PartiallyRefunded->value)->toBe('partially_refunded');
});
