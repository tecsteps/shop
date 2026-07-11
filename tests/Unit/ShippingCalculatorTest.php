<?php

use App\Models\Cart;
use App\Models\CartLine;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Services\ShippingCalculator;
use Illuminate\Database\Eloquent\Collection;

function unitCartWithLine(int $subtotal = 5000, int $weight = 750): Cart
{
    $variant = new ProductVariant(['weight_g' => $weight, 'requires_shipping' => true]);
    $line = new CartLine(['quantity' => 1, 'line_total_amount' => $subtotal]);
    $line->setRelation('variant', $variant);
    $cart = new Cart;
    $cart->setRelation('lines', new Collection([$line]));

    return $cart;
}

it('calculates flat weight and price shipping rates', function () {
    $calculator = new ShippingCalculator;
    $cart = unitCartWithLine();

    expect($calculator->calculate(new ShippingRate(['type' => 'flat', 'config_json' => ['amount' => 499]]), $cart))->toBe(499)
        ->and($calculator->calculate(new ShippingRate(['type' => 'weight', 'config_json' => ['ranges' => [['min_g' => 501, 'max_g' => 2000, 'amount' => 899]]]]), $cart))->toBe(899)
        ->and($calculator->calculate(new ShippingRate(['type' => 'price', 'config_json' => ['ranges' => [['min_amount' => 5000, 'amount' => 0]]]]), $cart))->toBe(0);
});

it('returns null when no configured range matches', function () {
    $rate = new ShippingRate(['type' => 'weight', 'config_json' => ['ranges' => [['min_g' => 0, 'max_g' => 100, 'amount' => 499]]]]);

    expect((new ShippingCalculator)->calculate($rate, unitCartWithLine()))->toBeNull();
});
