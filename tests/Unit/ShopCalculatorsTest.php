<?php

use App\Enums\PaymentMethod;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\TaxSettings;
use App\Services\DiscountService;
use App\Services\Payments\MockPaymentProvider;
use App\Services\PricingEngine;
use App\Services\ShippingCalculator;
use App\Services\Tax\ManualTaxProvider;
use App\Services\Tax\StripeTaxProvider;
use App\Services\TaxCalculator;
use App\Services\WebhookService;
use App\ValueObjects\Address;
use App\ValueObjects\PricingResult;
use App\ValueObjects\TaxLine;
use Illuminate\Support\Collection;
use Tests\TestCase;

uses(TestCase::class);

function unitTaxCalculator(): TaxCalculator
{
    return new TaxCalculator(new ManualTaxProvider, new StripeTaxProvider);
}

function unitPricingEngine(): PricingEngine
{
    return new PricingEngine(new DiscountService, new ShippingCalculator, unitTaxCalculator());
}

it('normalizes checkout addresses without losing optional fields', function () {
    $address = Address::fromArray([
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'address1' => 'Example Street 1',
        'city' => 'Berlin',
        'country' => 'de',
        'zip' => '10115',
        'company' => '',
        'province_code' => 'BE',
    ]);

    expect($address->countryCode)->toBe('DE')
        ->and($address->postalCode)->toBe('10115')
        ->and($address->company)->toBeNull()
        ->and($address->toArray())->toMatchArray([
            'first_name' => 'Ada',
            'country_code' => 'DE',
            'postal_code' => '10115',
            'province_code' => 'BE',
        ]);
});

it('calculates deterministic integer subtotals', function () {
    $lines = [
        ['unit_price_amount' => 2499, 'quantity' => 2],
        ['unit_price_amount' => 7999, 'quantity' => 1],
    ];

    expect(unitPricingEngine()->calculateSubtotal($lines))->toBe(12997)
        ->and(unitPricingEngine()->calculateSubtotal([]))->toBe(0)
        ->and(unitPricingEngine()->calculateSubtotal($lines))->toBe(unitPricingEngine()->calculateSubtotal($lines));
});

it('serializes a complete pricing snapshot in minor units', function () {
    $result = new PricingResult(10000, 1000, 499, [new TaxLine('VAT', 1900, 1805)], 1805, 11304, 'EUR');

    expect($result->toArray())->toBe([
        'subtotal' => 10000,
        'discount' => 1000,
        'discounted_subtotal' => 9000,
        'shipping' => 499,
        'tax_lines' => [['name' => 'VAT', 'rate' => 1900, 'amount' => 1805]],
        'tax_total' => 1805,
        'tax' => 1805,
        'total' => 11304,
        'currency' => 'EUR',
    ]);
});

it('calculates tax-exclusive values per line and inclusive extraction', function () {
    $exclusive = new TaxSettings([
        'mode' => 'manual',
        'provider' => 'none',
        'prices_include_tax' => false,
        'config_json' => ['rate_bps' => 1900, 'label' => 'VAT'],
    ]);
    $inclusive = new TaxSettings([
        'mode' => 'manual',
        'provider' => 'none',
        'prices_include_tax' => true,
        'config_json' => ['rate_bps' => 1900, 'label' => 'VAT'],
    ]);
    $calculator = unitTaxCalculator();

    expect($calculator->calculateLines([5000, 499], 0, $exclusive)->totalAmount)->toBe(1045)
        ->and($calculator->calculate(11900, $inclusive)->totalAmount)->toBe(1900)
        ->and($calculator->extractInclusive(11900, 1900))->toBe(1900)
        ->and($calculator->addExclusive(8999, 700))->toBe(630)
        ->and($calculator->calculate(10000, null)->totalAmount)->toBe(0);
});

it('calculates discount values and preserves allocation cents', function () {
    $service = new DiscountService;
    $lines = collect([
        ['id' => 11, 'product_id' => 1, 'line_subtotal_amount' => 7500],
        ['id' => 12, 'product_id' => 2, 'line_subtotal_amount' => 2500],
    ]);
    $percent = new Discount(['value_type' => 'percent', 'value_amount' => 10, 'rules_json' => []]);
    $fixed = new Discount(['value_type' => 'fixed', 'value_amount' => 12000, 'rules_json' => []]);
    $shipping = new Discount(['value_type' => 'free_shipping', 'value_amount' => 0, 'rules_json' => []]);

    $percentResult = $service->calculate($percent, 10000, $lines);
    $fixedResult = $service->calculate($fixed, 10000, $lines);
    $shippingResult = $service->calculate($shipping, 10000, $lines);

    expect($percentResult->amount)->toBe(1000)
        ->and($percentResult->allocations)->toBe([11 => 750, 12 => 250])
        ->and(array_sum($percentResult->allocations))->toBe($percentResult->amount)
        ->and($fixedResult->amount)->toBe(10000)
        ->and($shippingResult->amount)->toBe(0)
        ->and($shippingResult->freeShipping)->toBeTrue();
});

it('assigns a discount rounding remainder to the final qualifying line', function () {
    $discount = new Discount(['value_type' => 'percent', 'value_amount' => 10, 'rules_json' => []]);
    $lines = [
        ['id' => 1, 'line_subtotal_amount' => 333],
        ['id' => 2, 'line_subtotal_amount' => 333],
        ['id' => 3, 'line_subtotal_amount' => 334],
    ];

    $result = (new DiscountService)->calculate($discount, 1000, $lines);

    expect($result->amount)->toBe(100)
        ->and(array_sum($result->allocations))->toBe(100)
        ->and($result->allocations)->toBe([1 => 33, 2 => 33, 3 => 34]);
});

it('calculates flat weight price and digital shipping', function () {
    $physical = new ProductVariant(['requires_shipping' => true, 'weight_g' => 750]);
    $digital = new ProductVariant(['requires_shipping' => false, 'weight_g' => 0]);
    $physicalLine = new CartLine(['quantity' => 1, 'line_subtotal_amount' => 7500]);
    $physicalLine->setRelation('variant', $physical);
    $digitalLine = new CartLine(['quantity' => 1, 'line_subtotal_amount' => 2500]);
    $digitalLine->setRelation('variant', $digital);
    $cart = new Cart;
    $cart->setRelation('lines', new Collection([$physicalLine, $digitalLine]));
    $digitalCart = new Cart;
    $digitalCart->setRelation('lines', new Collection([$digitalLine]));
    $calculator = new ShippingCalculator;

    $flat = new ShippingRate(['type' => 'flat', 'config_json' => ['amount' => 499]]);
    $weight = new ShippingRate(['type' => 'weight', 'config_json' => ['ranges' => [
        ['min_g' => 0, 'max_g' => 500, 'amount' => 499],
        ['min_g' => 501, 'max_g' => 2000, 'amount' => 899],
    ]]]);
    $price = new ShippingRate(['type' => 'price', 'config_json' => ['ranges' => [
        ['min_amount' => 0, 'max_amount' => 5000, 'amount' => 799],
        ['min_amount' => 5001, 'amount' => 399],
    ]]]);

    expect($calculator->calculate($flat, $cart))->toBe(499)
        ->and($calculator->calculate($weight, $cart))->toBe(899)
        ->and($calculator->calculate($price, $cart))->toBe(399)
        ->and($calculator->calculate($flat, $digitalCart))->toBe(0);
});

it('simulates each payment outcome', function (PaymentMethod $method, array $details, bool $success, string $status, ?string $errorCode) {
    $result = (new MockPaymentProvider)->charge(new Checkout, $method, $details);

    expect($result->success)->toBe($success)
        ->and($result->status)->toBe($status)
        ->and($result->errorCode)->toBe($errorCode)
        ->and($result->referenceId)->toStartWith('mock_');
})->with([
    'credit card captured' => [PaymentMethod::CreditCard, ['card_number' => '4242 4242 4242 4242'], true, 'captured', null],
    'credit card declined' => [PaymentMethod::CreditCard, ['card_number' => '4000 0000 0000 0002'], false, 'failed', 'card_declined'],
    'insufficient funds' => [PaymentMethod::CreditCard, ['card_number' => '4000 0000 0000 9995'], false, 'failed', 'insufficient_funds'],
    'paypal captured' => [PaymentMethod::Paypal, [], true, 'captured', null],
    'bank transfer pending' => [PaymentMethod::BankTransfer, [], true, 'pending', null],
]);

it('signs and verifies webhook payloads without timing-sensitive comparisons', function () {
    $payload = '{"event":"order.created"}';
    $service = new WebhookService;
    $signature = $service->sign($payload, 'test-secret');

    expect($signature)->toBe(hash_hmac('sha256', $payload, 'test-secret'))
        ->and($service->verify($payload, $signature, 'test-secret'))->toBeTrue()
        ->and($service->verify('{"event":"tampered"}', $signature, 'test-secret'))->toBeFalse()
        ->and($service->verify($payload, $signature, 'wrong-secret'))->toBeFalse();
});
