<?php

use App\Enums\DiscountValueType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Checkout;
use App\Models\Discount;
use App\Services\DiscountService;
use App\Services\MockPaymentProvider;
use App\Services\TaxCalculator;

test('tax calculations use integer minor-unit arithmetic', function (): void {
    $calculator = new TaxCalculator;

    expect($calculator->addExclusive(2499, 1900))->toBe(474)
        ->and($calculator->extractInclusive(2973, 1900))->toBe(474);
});

test('discount calculations cap fixed discounts and allocate percentages', function (): void {
    $service = new DiscountService;
    $discount = new Discount(['value_type' => DiscountValueType::Percent, 'value_amount' => 10]);

    $result = $service->calculate($discount, 10000, [
        ['line_id' => 1, 'amount' => 6000],
        ['line_id' => 2, 'amount' => 4000],
    ]);

    expect($result->amount)->toBe(1000)
        ->and($result->allocations)->toBe([1 => 600, 2 => 400]);

    $fixed = new Discount(['value_type' => DiscountValueType::Fixed, 'value_amount' => 5000]);

    expect($service->calculate($fixed, 300, [['line_id' => 1, 'amount' => 300]])->amount)->toBe(300);
});

test('free shipping discounts do not reduce merchandise totals', function (): void {
    $discount = new Discount(['value_type' => DiscountValueType::FreeShipping, 'value_amount' => 0]);
    $result = (new DiscountService)->calculate($discount, 2500, [['line_id' => 1, 'amount' => 2500]]);

    expect($result->amount)->toBe(0)->and($result->freeShipping)->toBeTrue();
});

test('mock payment provider supports the documented magic cards and deferred transfers', function (): void {
    $provider = new MockPaymentProvider;
    $checkout = new Checkout;

    expect($provider->charge($checkout, PaymentMethod::CreditCard, ['card_number' => '4242424242424242'])->status)
        ->toBe(PaymentStatus::Captured)
        ->and($provider->charge($checkout, PaymentMethod::CreditCard, ['card_number' => '4000000000000002'])->status)
        ->toBe(PaymentStatus::Failed)
        ->and($provider->charge($checkout, PaymentMethod::BankTransfer, [])->status)
        ->toBe(PaymentStatus::Pending);
});
