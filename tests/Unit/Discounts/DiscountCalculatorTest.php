<?php

use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\DiscountService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
    $this->service = new DiscountService;
    $this->cart = Cart::factory()->for($this->store)->create();
});

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

function makeCartLine(Cart $cart, int $price = 2000, int $qty = 1): CartLine
{
    $variant = ProductVariant::factory()
        ->for(Product::factory()->for($cart->store))
        ->create(['price_amount' => $price]);

    return CartLine::factory()->for($cart)->for($variant, 'variant')->create([
        'quantity' => $qty,
        'unit_price_amount' => $price,
        'line_subtotal_amount' => $price * $qty,
        'line_total_amount' => $price * $qty,
    ]);
}

it('validates an active code', function (): void {
    $discount = Discount::factory()->for($this->store)->create(['code' => 'SAVE10']);
    makeCartLine($this->cart);

    $result = $this->service->validate('SAVE10', $this->store, $this->cart->fresh('lines'));

    expect($result->id)->toBe($discount->id);
});

it('rejects expired discount', function (): void {
    Discount::factory()->for($this->store)->expired()->create(['code' => 'OLD']);

    expect(fn () => $this->service->validate('OLD', $this->store, $this->cart))
        ->toThrow(InvalidDiscountException::class);
});

it('rejects not yet active discount', function (): void {
    Discount::factory()->for($this->store)->notYetActive()->create(['code' => 'FUTURE']);

    $this->service->validate('FUTURE', $this->store, $this->cart);
})->throws(InvalidDiscountException::class, 'not yet active');

it('rejects usage limit reached', function (): void {
    Discount::factory()->for($this->store)->create([
        'code' => 'LIMITED',
        'usage_limit' => 5,
        'usage_count' => 5,
    ]);

    $this->service->validate('LIMITED', $this->store, $this->cart);
})->throws(InvalidDiscountException::class, 'usage limit');

it('rejects unknown code', function (): void {
    $this->service->validate('NOPE', $this->store, $this->cart);
})->throws(InvalidDiscountException::class, 'not found');

it('performs case insensitive lookup', function (): void {
    Discount::factory()->for($this->store)->create(['code' => 'SaveMe']);
    makeCartLine($this->cart);

    $result = $this->service->validate('SAVEME', $this->store, $this->cart->fresh('lines'));

    expect($result->code)->toBe('SaveMe');
});

it('enforces minimum purchase rule', function (): void {
    Discount::factory()->for($this->store)->create([
        'code' => 'BIG',
        'rules_json' => ['min_purchase_amount' => 10000],
    ]);
    makeCartLine($this->cart, price: 1000, qty: 1);

    $this->service->validate('BIG', $this->store, $this->cart->fresh('lines'));
})->throws(InvalidDiscountException::class, 'minimum');

it('passes when minimum purchase is met', function (): void {
    $discount = Discount::factory()->for($this->store)->create([
        'code' => 'BIG2',
        'rules_json' => ['min_purchase_amount' => 1000],
    ]);
    makeCartLine($this->cart, price: 2000, qty: 1);

    $result = $this->service->validate('BIG2', $this->store, $this->cart->fresh('lines'));

    expect($result->id)->toBe($discount->id);
});

it('rejects disabled discount', function (): void {
    Discount::factory()->for($this->store)->disabled()->create(['code' => 'OFF']);

    $this->service->validate('OFF', $this->store, $this->cart);
})->throws(InvalidDiscountException::class);

it('calculates percent discount amount', function (): void {
    $discount = Discount::factory()->for($this->store)->percent10()->create(['code' => 'P10']);
    makeCartLine($this->cart, price: 10000, qty: 1);
    $lines = $this->cart->fresh('lines')->lines;

    $result = $this->service->calculate($discount, 10000, $lines);

    expect($result->amount)->toBe(1000);
});

it('calculates fixed discount amount', function (): void {
    $discount = Discount::factory()->for($this->store)->fixed500()->create(['code' => 'F500']);
    makeCartLine($this->cart, price: 10000, qty: 1);
    $lines = $this->cart->fresh('lines')->lines;

    $result = $this->service->calculate($discount, 10000, $lines);

    expect($result->amount)->toBe(500);
});

it('caps fixed discount at subtotal', function (): void {
    $discount = Discount::factory()->for($this->store)->fixed500()->create(['code' => 'F500B']);
    makeCartLine($this->cart, price: 300, qty: 1);
    $lines = $this->cart->fresh('lines')->lines;

    $result = $this->service->calculate($discount, 300, $lines);

    expect($result->amount)->toBe(300);
});

it('flags free shipping discount', function (): void {
    $discount = Discount::factory()->for($this->store)->freeShipping()->create(['code' => 'FREESHIP']);
    makeCartLine($this->cart, price: 5000);
    $lines = $this->cart->fresh('lines')->lines;

    $result = $this->service->calculate($discount, 5000, $lines);

    expect($result->freeShipping)->toBeTrue()
        ->and($result->amount)->toBe(0);
});

it('allocates proportionally across lines with remainder on last', function (): void {
    $discount = Discount::factory()->for($this->store)->percent10()->create(['code' => 'P10B']);
    $line1 = makeCartLine($this->cart, price: 3333, qty: 1);
    $line2 = makeCartLine($this->cart, price: 3333, qty: 1);
    $line3 = makeCartLine($this->cart, price: 3334, qty: 1);
    $subtotal = 10000;
    $lines = $this->cart->fresh('lines')->lines;

    $result = $this->service->calculate($discount, $subtotal, $lines);

    expect($result->amount)->toBe(1000)
        ->and(array_sum($result->allocations))->toBe(1000)
        ->and($result->allocations)->toHaveCount(3);
});
